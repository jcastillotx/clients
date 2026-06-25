import { NextRequest } from "next/server";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

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

export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();
    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const formData = await request.formData();
    const file = formData.get("file") as File | null;
    if (!file) {
      return apiError(request, { status: 400, code: "BAD_REQUEST", message: "No file provided" });
    }

    const extension = ALLOWED_TYPES[file.type];
    if (!extension) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Only JPG, PNG, WEBP, and GIF are allowed",
      });
    }
    if (file.size > MAX_BYTES) {
      return apiError(request, { status: 400, code: "BAD_REQUEST", message: "File must be 5MB or less" });
    }

    const s3 = await getS3Credentials(user.id);
    if (!s3) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "No S3 storage connection configured. Add a platform S3 connection in Storage settings.",
      });
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
      return apiInternalError(request, `S3 upload failed (${s3Res.status}): ${errText}`);
    }

    // Store as proxy URL so the bucket stays private
    const avatarUrl = `/api/avatar?key=${encodeURIComponent(key)}`;

    const adminClient = createAdminClientIfAvailable();

    // Update users table — use admin client to bypass RLS (admins have no client_id in JWT)
    // Non-fatal: auth metadata update below is the authoritative source
    const dbClient = adminClient ?? supabase;
    const { error: userUpdateError } = await dbClient
      .from("users")
      .update({ avatar: avatarUrl, updated_at: new Date().toISOString() })
      .eq("id", user.id);

    if (userUpdateError) {
      console.warn("Avatar DB update failed (non-fatal):", userUpdateError.message);
    }

    // Update auth user metadata — this always works regardless of RLS
    const mergedMetadata = { ...asRecord(user.user_metadata), avatar: avatarUrl };

    if (adminClient) {
      const { error } = await adminClient.auth.admin.updateUserById(user.id, {
        user_metadata: mergedMetadata,
      });
      if (error) {
        console.warn("Auth metadata update via admin failed:", error.message);
        // Fall back to user-scoped update
        const { error: fallbackError } = await supabase.auth.updateUser({ data: mergedMetadata });
        if (fallbackError) return apiInternalError(request, fallbackError.message);
      }
    } else {
      const { error } = await supabase.auth.updateUser({ data: mergedMetadata });
      if (error) return apiInternalError(request, error.message);
    }

    return apiSuccess(request, { avatarUrl }, { extra: { success: true, avatarUrl } });
  } catch (error) {
    console.error("Error saving profile image:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to save profile image",
    );
  }
}

// ---------------------------------------------------------------------------
// S3 DELETE helper
// ---------------------------------------------------------------------------

async function deleteS3Object(
  bucket: string,
  region: string,
  accessKeyId: string,
  secretAccessKey: string,
  objectKey: string,
): Promise<void> {
  const now = new Date();
  const dateStr = now.toISOString().slice(0, 10).replace(/-/g, "");
  const datetimeStr = now.toISOString().replace(/[-:]/g, "").slice(0, 15) + "Z";
  const host = `${bucket}.s3.${region}.amazonaws.com`;
  const credentialScope = `${dateStr}/${region}/s3/aws4_request`;
  const encodedKey = objectKey.split("/").map(encodeURIComponent).join("/");
  const url = `https://${host}/${encodedKey}`;
  const payloadHash = "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"; // SHA-256 of empty string

  const canonicalRequest = [
    "DELETE",
    `/${encodedKey}`,
    "",
    `host:${host}\nx-amz-content-sha256:${payloadHash}\nx-amz-date:${datetimeStr}\n`,
    "host;x-amz-content-sha256;x-amz-date",
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

  await fetch(url, {
    method: "DELETE",
    headers: {
      Authorization: `AWS4-HMAC-SHA256 Credential=${accessKeyId}/${credentialScope}, SignedHeaders=host;x-amz-content-sha256;x-amz-date, Signature=${signature}`,
      "x-amz-date": datetimeStr,
      "x-amz-content-sha256": payloadHash,
    } as Record<string, string>,
  });
  // S3 DELETE returns 204 on success, 404 if not found — both are acceptable
}

// ---------------------------------------------------------------------------
// DELETE /api/settings/profile-image
// Removes the avatar from S3, the users table, and auth metadata.
// ---------------------------------------------------------------------------

export async function DELETE(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();
    if (authError || !user) {
      return apiUnauthorized(request);
    }

    // Extract S3 key from stored avatar URL: /api/avatar?key=avatars%2F...
    const currentAvatar = (user.user_metadata?.avatar as string | undefined) ?? "";
    const keyParam = currentAvatar.startsWith("/api/avatar?key=")
      ? decodeURIComponent(currentAvatar.replace("/api/avatar?key=", ""))
      : null;

    // Delete from S3 if there's a stored object
    if (keyParam && keyParam.startsWith(`avatars/${user.id}/`)) {
      const s3 = await getS3Credentials(user.id);
      if (s3) {
        const { accessKeyId, secretAccessKey, bucket, region } = s3;
        await deleteS3Object(bucket, region, accessKeyId, secretAccessKey, keyParam).catch((err) => {
          console.warn("S3 delete failed (non-fatal):", err);
        });
      }
    }

    // Clear avatar in users table
    const adminClient = createAdminClientIfAvailable();
    const dbClient = adminClient ?? supabase;
    const { error: dbError } = await dbClient
      .from("users")
      .update({ avatar: null, updated_at: new Date().toISOString() })
      .eq("id", user.id);
    if (dbError) {
      console.warn("Avatar DB clear failed (non-fatal):", dbError.message);
    }

    // Clear avatar in auth metadata
    const metaWithoutAvatar = { ...asRecord(user.user_metadata) };
    delete metaWithoutAvatar.avatar;
    if (adminClient) {
      const { error } = await adminClient.auth.admin.updateUserById(user.id, {
        user_metadata: metaWithoutAvatar,
      });
      if (error) {
        const { error: fallbackError } = await supabase.auth.updateUser({ data: metaWithoutAvatar });
        if (fallbackError) return apiInternalError(request, fallbackError.message);
      }
    } else {
      const { error } = await supabase.auth.updateUser({ data: metaWithoutAvatar });
      if (error) return apiInternalError(request, error.message);
    }

    return apiSuccess(request, { deleted: true }, { extra: { success: true } });
  } catch (error) {
    console.error("Error deleting profile image:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to delete profile image",
    );
  }
}
