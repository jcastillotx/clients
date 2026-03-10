import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { getS3Credentials } from "@/lib/storage/get-s3-credentials";

const ALLOWED_TYPES: Record<string, string> = {
  "image/jpeg": "jpg",
  "image/png": "png",
  "image/webp": "webp",
  "image/gif": "gif",
};
const MAX_BYTES = 5 * 1024 * 1024;

// ---------------------------------------------------------------------------
// Shared crypto helpers
// ---------------------------------------------------------------------------

function toHex(buf: ArrayBuffer): string {
  return Array.from(new Uint8Array(buf))
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}

function toArrayBuffer(input: ArrayBuffer | Uint8Array): ArrayBuffer {
  if (input instanceof Uint8Array) {
    return input.buffer.slice(
      input.byteOffset,
      input.byteOffset + input.byteLength,
    ) as ArrayBuffer;
  }
  return input as ArrayBuffer;
}

async function hmacSha256(key: ArrayBuffer | Uint8Array, data: string): Promise<ArrayBuffer> {
  const cryptoKey = await crypto.subtle.importKey(
    "raw",
    toArrayBuffer(key),
    { name: "HMAC", hash: "SHA-256" },
    false,
    ["sign"],
  );
  return crypto.subtle.sign("HMAC", cryptoKey, new TextEncoder().encode(data));
}

async function sha256Hex(data: string): Promise<string> {
  const hash = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(data));
  return toHex(hash);
}

// ---------------------------------------------------------------------------
// Generate a presigned PUT URL so the browser can upload directly to S3
// ---------------------------------------------------------------------------

async function buildPresignedPutUrl(
  bucket: string,
  region: string,
  accessKeyId: string,
  secretAccessKey: string,
  objectKey: string,
  contentType: string,
  expiresInSeconds = 300,
): Promise<string> {
  const now = new Date();
  const dateStr = now.toISOString().slice(0, 10).replace(/-/g, "");
  const datetimeStr = now.toISOString().replace(/[-:]/g, "").slice(0, 15) + "Z";
  const host = `${bucket}.s3.${region}.amazonaws.com`;
  const credentialScope = `${dateStr}/${region}/s3/aws4_request`;
  const credential = `${accessKeyId}/${credentialScope}`;

  const canonicalUri = `/${objectKey.split("/").map(encodeURIComponent).join("/")}`;

  const queryParams: [string, string][] = [
    ["X-Amz-Algorithm", "AWS4-HMAC-SHA256"],
    ["X-Amz-Credential", credential],
    ["X-Amz-Date", datetimeStr],
    ["X-Amz-Expires", String(expiresInSeconds)],
    ["X-Amz-SignedHeaders", "content-type;host"],
  ];
  queryParams.sort(([a], [b]) => a.localeCompare(b));
  const sortedQuery = queryParams
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join("&");

  const canonicalRequest = [
    "PUT",
    canonicalUri,
    sortedQuery,
    `content-type:${contentType}\nhost:${host}\n`,
    "content-type;host",
    "UNSIGNED-PAYLOAD",
  ].join("\n");

  const stringToSign = [
    "AWS4-HMAC-SHA256",
    datetimeStr,
    credentialScope,
    await sha256Hex(canonicalRequest),
  ].join("\n");

  const signingKey = await (async () => {
    const kDate = await hmacSha256(new TextEncoder().encode(`AWS4${secretAccessKey}`), dateStr);
    const kRegion = await hmacSha256(kDate, region);
    const kService = await hmacSha256(kRegion, "s3");
    return hmacSha256(kService, "aws4_request");
  })();

  const signature = toHex(await hmacSha256(signingKey, stringToSign));

  return `https://${host}${canonicalUri}?${sortedQuery}&X-Amz-Signature=${signature}`;
}

// ---------------------------------------------------------------------------
// POST /api/settings/profile-image/presign
// Body: { contentType: string, fileSize: number }
// Returns: { presignedUrl, key }
// ---------------------------------------------------------------------------

export async function POST(req: NextRequest) {
  const supabase = await createClient();
  const { data: { user }, error: authError } = await supabase.auth.getUser();
  if (authError || !user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const { contentType, fileSize } = await req.json();

  const extension = ALLOWED_TYPES[contentType as string];
  if (!extension) {
    return NextResponse.json({ error: "Only JPG, PNG, WEBP, and GIF are allowed" }, { status: 400 });
  }
  if (typeof fileSize !== "number" || fileSize > MAX_BYTES) {
    return NextResponse.json({ error: "File must be 5MB or less" }, { status: 400 });
  }

  const s3 = await getS3Credentials(user.id);
  if (!s3) {
    return NextResponse.json(
      { error: "No S3 storage connection configured. Add a company S3 connection in Storage settings." },
      { status: 503 },
    );
  }
  const { accessKeyId, secretAccessKey, bucket, region } = s3;

  const key = `avatars/${user.id}/${Date.now()}.${extension}`;
  const presignedUrl = await buildPresignedPutUrl(
    bucket, region, accessKeyId, secretAccessKey, key, contentType, 300,
  );

  return NextResponse.json({ presignedUrl, key });
}
