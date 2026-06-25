import { NextRequest, NextResponse } from "next/server";
import {
  apiError,
  apiForbidden,
} from "@/lib/api/response";
import { getS3Credentials } from "@/lib/storage/get-s3-credentials";

// ---------------------------------------------------------------------------
// AWS Signature V4 presigned GET URL generator (no auth – public endpoint)
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

async function hmacSha256(
  key: ArrayBuffer | Uint8Array,
  data: string,
): Promise<ArrayBuffer> {
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
  const hash = await crypto.subtle.digest(
    "SHA-256",
    toArrayBuffer(new TextEncoder().encode(data)),
  );
  return toHex(hash);
}

async function buildPresignedGetUrl(
  bucket: string,
  region: string,
  accessKeyId: string,
  secretAccessKey: string,
  objectKey: string,
  expiresInSeconds = 3000,
): Promise<string> {
  const now = new Date();
  const dateStr = now.toISOString().slice(0, 10).replace(/-/g, "");
  const datetimeStr = now.toISOString().replace(/[-:]/g, "").slice(0, 15) + "Z";
  const host = `${bucket}.s3.${region}.amazonaws.com`;
  const credentialScope = `${dateStr}/${region}/s3/aws4_request`;
  const credential = `${accessKeyId}/${credentialScope}`;

  const canonicalUri = `/${objectKey.split("/").map(encodeURIComponent).join("/")}`;

  const queryParams = new URLSearchParams({
    "X-Amz-Algorithm": "AWS4-HMAC-SHA256",
    "X-Amz-Credential": credential,
    "X-Amz-Date": datetimeStr,
    "X-Amz-Expires": String(expiresInSeconds),
    "X-Amz-SignedHeaders": "host",
  });

  const sortedQuery = Array.from(queryParams.entries())
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join("&");

  const canonicalRequest = [
    "GET",
    canonicalUri,
    sortedQuery,
    `host:${host}\n`,
    "host",
    "UNSIGNED-PAYLOAD",
  ].join("\n");

  const stringToSign = [
    "AWS4-HMAC-SHA256",
    datetimeStr,
    credentialScope,
    await sha256Hex(canonicalRequest),
  ].join("\n");

  const signingKey = await (async () => {
    const kDate = await hmacSha256(
      new TextEncoder().encode(`AWS4${secretAccessKey}`),
      dateStr,
    );
    const kRegion = await hmacSha256(kDate, region);
    const kService = await hmacSha256(kRegion, "s3");
    return hmacSha256(kService, "aws4_request");
  })();

  const signature = toHex(await hmacSha256(signingKey, stringToSign));

  return `https://${host}${canonicalUri}?${sortedQuery}&X-Amz-Signature=${signature}`;
}

// ---------------------------------------------------------------------------
// Route: GET /api/branding/image?key=branding/...
// Public endpoint (no auth). Only serves keys that start with "branding/".
// Redirects to a presigned S3 GET URL, cached for 50 minutes.
// ---------------------------------------------------------------------------

export async function GET(request: NextRequest) {
  const key = request.nextUrl.searchParams.get("key");

  if (!key) {
    return apiError(request, { status: 400, code: "BAD_REQUEST", message: "Missing key" });
  }

  // Security: only serve objects under the branding/ prefix
  if (!key.startsWith("branding/")) {
    return apiForbidden(request);
  }

  // getS3Credentials ignores the userId and searches platform-level connections.
  const s3 = await getS3Credentials(undefined).catch(() => null);
  if (!s3) {
    return apiError(request, {
      status: 503,
      code: "SERVICE_UNAVAILABLE",
      message: "S3 not configured",
    });
  }

  const { accessKeyId, secretAccessKey, bucket, region } = s3;

  const presignedUrl = await buildPresignedGetUrl(
    bucket,
    region,
    accessKeyId,
    secretAccessKey,
    key,
    3000, // 50 minutes, matching cache header
  );

  // Proxy the image server-side to avoid CORS issues with S3 redirects
  const s3Res = await fetch(presignedUrl);
  if (!s3Res.ok) {
    return apiError(request, {
      status: 502,
      code: "INTERNAL_ERROR",
      message: `S3 fetch failed: ${s3Res.status}`,
    });
  }

  const contentType = s3Res.headers.get("content-type") ?? "image/png";
  const buffer = await s3Res.arrayBuffer();

  return new NextResponse(buffer, {
    headers: {
      "Content-Type": contentType,
      "Cache-Control": "public, max-age=3000",
    },
  });
}
