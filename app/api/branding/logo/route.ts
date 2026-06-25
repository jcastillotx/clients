import { NextRequest } from "next/server";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

import { createClient } from "@/lib/supabase/server";
import { getS3Credentials } from "@/lib/storage/get-s3-credentials";

const ALLOWED_TYPES: Record<string, string> = {
  "image/jpeg": "jpg",
  "image/png": "png",
  "image/webp": "webp",
  "image/svg+xml": "svg",
};
const MAX_BYTES = 5 * 1024 * 1024;

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

async function sha256Hex(data: string | ArrayBuffer): Promise<string> {
  const input = typeof data === "string" ? toArrayBuffer(new TextEncoder().encode(data)) : data;
  return toHex(await crypto.subtle.digest("SHA-256", input));
}

/**
 * POST /api/branding/logo
 * Body: FormData with "file" field
 * Returns: { logoUrl } — the /api/avatar proxy URL for the uploaded logo
 */
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
        message: "Only JPG, PNG, WEBP, and SVG are allowed",
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

    const key = `branding/${Date.now()}.${extension}`;
    const bodyBuffer = await file.arrayBuffer();
    const payloadHash = await sha256Hex(bodyBuffer);

    const now = new Date();
    const dateStr = now.toISOString().slice(0, 10).replace(/-/g, "");
    const datetimeStr = now.toISOString().replace(/[-:]/g, "").slice(0, 15) + "Z";
    const host = `${bucket}.s3.${region}.amazonaws.com`;
    const credentialScope = `${dateStr}/${region}/s3/aws4_request`;
    const encodedKey = key.split("/").map(encodeURIComponent).join("/");
    const url = `https://${host}/${encodedKey}`;

    const canonicalRequest = [
      "PUT",
      `/${encodedKey}`,
      "",
      `content-type:${file.type}\nhost:${host}\nx-amz-content-sha256:${payloadHash}\nx-amz-date:${datetimeStr}\n`,
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

    const s3Res = await fetch(url, {
      method: "PUT",
      headers: {
        Authorization: `AWS4-HMAC-SHA256 Credential=${accessKeyId}/${credentialScope}, SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date, Signature=${signature}`,
        "x-amz-date": datetimeStr,
        "x-amz-content-sha256": payloadHash,
        "Content-Type": file.type,
      } as Record<string, string>,
      body: bodyBuffer,
    });

    if (!s3Res.ok) {
      const errText = await s3Res.text().catch(() => s3Res.status.toString());
      return apiInternalError(request, `S3 upload failed (${s3Res.status}): ${errText}`);
    }

    const logoUrl = `/api/branding/image?key=${encodeURIComponent(key)}`;
    return apiSuccess(request, { logoUrl }, { extra: { success: true, logoUrl } });
  } catch (error) {
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to upload logo",
    );
  }
}
