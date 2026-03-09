import { NextResponse } from "next/server";
import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";

const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024; // 5MB
const ALLOWED_IMAGE_TYPES: Record<string, string> = {
  "image/jpeg": "jpg",
  "image/png": "png",
  "image/webp": "webp",
  "image/gif": "gif",
};

function asRecord(value: unknown): Record<string, unknown> {
  if (value && typeof value === "object") return value as Record<string, unknown>;
  return {};
}

// ---------------------------------------------------------------------------
// AWS Signature V4 helpers — no SDK required
// ---------------------------------------------------------------------------

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

async function sha256Hex(data: ArrayBuffer | Uint8Array | string): Promise<string> {
  const buf =
    typeof data === "string"
      ? toArrayBuffer(new TextEncoder().encode(data))
      : toArrayBuffer(data);
  const hash = await crypto.subtle.digest("SHA-256", buf);
  return Array.from(new Uint8Array(hash))
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}

function toHex(buf: ArrayBuffer): string {
  return Array.from(new Uint8Array(buf))
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}

async function signS3Request({
  method,
  bucket,
  region,
  key,
  accessKeyId,
  secretAccessKey,
  contentType,
  bodyHash,
}: {
  method: string;
  bucket: string;
  region: string;
  key: string;
  accessKeyId: string;
  secretAccessKey: string;
  contentType: string;
  bodyHash: string;
}): Promise<Record<string, string>> {
  const now = new Date();
  const dateStr = now.toISOString().replace(/[:\-]|\.\d{3}/g, "").slice(0, 8); // YYYYMMDD
  const datetimeStr = now.toISOString().replace(/[:\-]|\.\d{3}/g, "").slice(0, 15) + "Z"; // YYYYMMDDTHHmmssZ
  const host = `${bucket}.s3.${region}.amazonaws.com`;
  const canonicalUri = `/${encodeURIComponent(key).replace(/%2F/g, "/")}`;

  const headers: Record<string, string> = {
    host,
    "content-type": contentType,
    "x-amz-content-sha256": bodyHash,
    "x-amz-date": datetimeStr,
  };

  const signedHeaders = Object.keys(headers).sort().join(";");
  const canonicalHeaders = Object.keys(headers)
    .sort()
    .map((k) => `${k}:${headers[k]}\n`)
    .join("");

  const canonicalRequest = [
    method,
    canonicalUri,
    "", // no query string
    canonicalHeaders,
    signedHeaders,
    bodyHash,
  ].join("\n");

  const scope = `${dateStr}/${region}/s3/aws4_request`;
  const stringToSign = [
    "AWS4-HMAC-SHA256",
    datetimeStr,
    scope,
    await sha256Hex(new TextEncoder().encode(canonicalRequest)),
  ].join("\n");

  const signingKey = await (async () => {
    const kDate = await hmacSha256(new TextEncoder().encode(`AWS4${secretAccessKey}`), dateStr);
    const kRegion = await hmacSha256(kDate, region);
    const kService = await hmacSha256(kRegion, "s3");
    return hmacSha256(kService, "aws4_request");
  })();

  const signature = toHex(await hmacSha256(signingKey, stringToSign));

  const authHeader =
    `AWS4-HMAC-SHA256 Credential=${accessKeyId}/${scope}, ` +
    `SignedHeaders=${signedHeaders}, Signature=${signature}`;

  return {
    Authorization: authHeader,
    "Content-Type": contentType,
    "x-amz-content-sha256": bodyHash,
    "x-amz-date": datetimeStr,
  };
}

// ---------------------------------------------------------------------------
// Route handler
// ---------------------------------------------------------------------------

export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Authentication required" }, { status: 401 });
    }

    const formData = await request.formData();
    const file = formData.get("file");

    if (!(file instanceof File)) {
      return NextResponse.json({ error: "No file provided" }, { status: 400 });
    }

    const extension = ALLOWED_IMAGE_TYPES[file.type];
    if (!extension) {
      return NextResponse.json(
        { error: "Only JPG, PNG, WEBP, and GIF files are allowed" },
        { status: 400 },
      );
    }

    if (file.size > MAX_FILE_SIZE_BYTES) {
      return NextResponse.json({ error: "File size must be 5MB or less" }, { status: 400 });
    }

    // -----------------------------------------------------------------------
    // Upload to AWS S3
    // -----------------------------------------------------------------------
    const accessKeyId = process.env.AWS_ACCESS_KEY_ID;
    const secretAccessKey = process.env.AWS_SECRET_ACCESS_KEY;
    const region = process.env.AWS_REGION ?? "us-east-1";
    const bucket = process.env.AWS_S3_BUCKET;

    if (!accessKeyId || !secretAccessKey || !bucket) {
      return NextResponse.json(
        { error: "AWS S3 is not configured. Set AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, and AWS_S3_BUCKET." },
        { status: 503 },
      );
    }

    const s3Key = `avatars/${user.id}/${Date.now()}.${extension}`;
    const fileBuffer = await file.arrayBuffer();
    const bodyHash = await sha256Hex(fileBuffer);

    const signedHeaders = await signS3Request({
      method: "PUT",
      bucket,
      region,
      key: s3Key,
      accessKeyId,
      secretAccessKey,
      contentType: file.type,
      bodyHash,
    });

    const s3Url = `https://${bucket}.s3.${region}.amazonaws.com/${s3Key}`;

    const s3Response = await fetch(s3Url, {
      method: "PUT",
      headers: signedHeaders,
      body: fileBuffer,
    });

    if (!s3Response.ok) {
      const errText = await s3Response.text();
      console.error("[profile-image] S3 upload failed:", errText);
      return NextResponse.json({ error: "Failed to upload image to S3" }, { status: 500 });
    }

    // Public URL (assumes bucket has public-read or a CDN in front)
    const avatarUrl = s3Url;

    // -----------------------------------------------------------------------
    // Save URL to users table and auth metadata
    // -----------------------------------------------------------------------
    const adminClient = createAdminClientIfAvailable();
    const dbClient = adminClient ?? supabase;

    const { error: userUpdateError } = await dbClient
      .from("users")
      .update({
        avatar: avatarUrl,
        updated_at: new Date().toISOString(),
      })
      .eq("id", user.id);

    if (userUpdateError) {
      return NextResponse.json({ error: userUpdateError.message }, { status: 500 });
    }

    const mergedMetadata = { ...asRecord(user.user_metadata), avatar: avatarUrl };

    if (adminClient) {
      const { error: authUpdateError } = await adminClient.auth.admin.updateUserById(user.id, {
        user_metadata: mergedMetadata,
      });
      if (authUpdateError) {
        return NextResponse.json({ error: authUpdateError.message }, { status: 500 });
      }
    } else {
      const { error: authUpdateError } = await supabase.auth.updateUser({ data: mergedMetadata });
      if (authUpdateError) {
        return NextResponse.json({ error: authUpdateError.message }, { status: 500 });
      }
    }

    return NextResponse.json({ success: true, avatarUrl });
  } catch (error) {
    console.error("Error uploading profile image:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to upload profile image" },
      { status: 500 },
    );
  }
}
