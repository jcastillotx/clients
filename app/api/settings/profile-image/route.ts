import { NextRequest, NextResponse } from "next/server";
import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { getS3Credentials } from "@/lib/storage/get-s3-credentials";

const ALLOWED_TYPES: Record<string, string> = {
  "image/jpeg": "jpg",
  "image/png": "png",
  "image/webp": "webp",
  "image/gif": "gif",
};
const MAX_BYTES = 5 * 1024 * 1024;

// ---------------------------------------------------------------------------
// SigV4 helpers for server-side PUT to S3
// ---------------------------------------------------------------------------

function toHex(buf: ArrayBuffer): string {
  return Array.from(new Uint8Array(buf))
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}

function toArrayBuffer(input: ArrayBuffer | Uint8Array): ArrayBuffer {
  if (input instanceof Uint8Array) {
    return input.buffer.slice(input.byteOffset, input.byteOffset + input.byteLength) as ArrayBuffer;
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

async function sha256Hex(data: string | Uint8Array): Promise<string> {
  const raw = typeof data === "string" ? new TextEncoder().encode(data) : data;
  return toHex(await crypto.subtle.digest("SHA-256", toArrayBuffer(raw)));
}

async function sha256HexBuffer(buf: ArrayBuffer): Promise<string> {
  return toHex(await crypto.subtle.digest("SHA-256", buf));
}

type S3PutHeaders = Record<string, string>;

async function buildS3PutHeaders(
  bucket: string,
  region: string,
  accessKeyId: string,
  secretAccessKey: string,
  objectKey: string,
  contentType: string,
  bodyBuffer: ArrayBuffer,
): Promise<{ url: string; headers: S3PutHeaders }> {
  const now = new Date();
  const dateStr = now.toISOString().slice(0, 10).replace(/-/g, "");
  const datetimeStr = now.toISOString().replace(/[-:]/g, "").slice(0, 15) + "Z";
  const host = `${bucket}.s3.${region}.amazonaws.com`;
  const credentialScope = `${dateStr}/${region}/s3/aws4_request`;

  const encodedKey = objectKey.split("/").map(encodeURIComponent).join("/");
  const url = `https://${host}/${encodedKey}`;

  const payloadHash = await sha256HexBuffer(bodyBuffer);

  const canonicalRequest = [
    "PUT",
    `/${encodedKey}`,
    "", // no query string
    `content-type:${contentType}\nhost:${host}\nx-amz-content-sha256:${payloadHash}\nx-amz-date:${datetimeStr}\n`,
    "content-type;host;x-amz-content-sha256;x-amz-date",
    payloadHash,
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

  return {
    url,
    headers: {
      Authorization: `AWS4-HMAC-SHA256 Credential=${accessKeyId}/${credentialScope}, SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date, Signature=${signature}`,
      "x-amz-date": datetimeStr,
      "x-amz-content-sha256": payloadHash,
      "Content-Type": contentType,
    },
  };
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function asRecord(value: unknown): Record<string, unknown> {
  if (value && typeof value === "object") return value as Record<string, unknown>;
  return {};
}

// ---------------------------------------------------------------------------
// POST /api/settings/profile-image
// Body: FormData with "file" field
// Returns: { avatarUrl }
// ---------------------------------------------------------------------------

export async function POST(req: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();
    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const formData = await req.formData();
    const file = formData.get("file") as File | null;
    if (!file) {
      return NextResponse.json({ error: "No file provided" }, { status: 400 });
    }

    const extension = ALLOWED_TYPES[file.type];
    if (!extension) {
      return NextResponse.json(
        { error: "Only JPG, PNG, WEBP, and GIF are allowed" },
        { status: 400 },
      );
    }
    if (file.size > MAX_BYTES) {
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

    // Upload file server-side to S3 (no CORS required)
    const key = `avatars/${user.id}/${Date.now()}.${extension}`;
    const bodyBuffer = await file.arrayBuffer();

    const { url, headers } = await buildS3PutHeaders(
      bucket,
      region,
      accessKeyId,
      secretAccessKey,
      key,
      file.type,
      bodyBuffer,
    );

    const s3Res = await fetch(url, {
      method: "PUT",
      headers,
      body: bodyBuffer,
    });

    if (!s3Res.ok) {
      const errText = await s3Res.text().catch(() => s3Res.status.toString());
      return NextResponse.json(
        { error: `S3 upload failed (${s3Res.status}): ${errText}` },
        { status: 500 },
      );
    }

    // Store as proxy URL so the bucket stays private
    const avatarUrl = `/api/avatar?key=${encodeURIComponent(key)}`;

    const adminClient = createAdminClientIfAvailable();
    const dbClient = adminClient ?? supabase;

    const { error: userUpdateError } = await dbClient
      .from("users")
      .update({ avatar: avatarUrl, updated_at: new Date().toISOString() })
      .eq("id", user.id);

    if (userUpdateError) {
      return NextResponse.json({ error: userUpdateError.message }, { status: 500 });
    }

    const mergedMetadata = { ...asRecord(user.user_metadata), avatar: avatarUrl };

    if (adminClient) {
      const { error } = await adminClient.auth.admin.updateUserById(user.id, {
        user_metadata: mergedMetadata,
      });
      if (error) return NextResponse.json({ error: error.message }, { status: 500 });
    } else {
      const { error } = await supabase.auth.updateUser({ data: mergedMetadata });
      if (error) return NextResponse.json({ error: error.message }, { status: 500 });
    }

    return NextResponse.json({ success: true, avatarUrl });
  } catch (error) {
    console.error("Error saving profile image:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to save profile image" },
      { status: 500 },
    );
  }
}
