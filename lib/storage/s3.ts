import type { S3Credentials } from "@/lib/storage/get-s3-credentials";

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

  return input;
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

async function sha256Hex(data: string | ArrayBuffer): Promise<string> {
  const input =
    typeof data === "string" ? toArrayBuffer(new TextEncoder().encode(data)) : data;

  return toHex(await crypto.subtle.digest("SHA-256", input));
}

async function buildSigningKey(
  secretAccessKey: string,
  dateStr: string,
  region: string,
): Promise<ArrayBuffer> {
  const kDate = await hmacSha256(
    new TextEncoder().encode(`AWS4${secretAccessKey}`),
    dateStr,
  );
  const kRegion = await hmacSha256(kDate, region);
  const kService = await hmacSha256(kRegion, "s3");

  return hmacSha256(kService, "aws4_request");
}

function createSigningContext(credentials: S3Credentials) {
  const now = new Date();
  const dateStr = now.toISOString().slice(0, 10).replace(/-/g, "");
  const datetimeStr = now.toISOString().replace(/[-:]/g, "").slice(0, 15) + "Z";
  const host = `${credentials.bucket}.s3.${credentials.region}.amazonaws.com`;
  const credentialScope = `${dateStr}/${credentials.region}/s3/aws4_request`;

  return { credentialScope, dateStr, datetimeStr, host };
}

function encodeObjectKey(objectKey: string): string {
  return objectKey.split("/").map(encodeURIComponent).join("/");
}

export async function putS3Object(options: {
  credentials: S3Credentials;
  key: string;
  body: ArrayBuffer;
  contentType: string;
}): Promise<{ key: string; error?: string }> {
  const { credentials, key, body, contentType } = options;
  const { accessKeyId, secretAccessKey } = credentials;
  const { credentialScope, dateStr, datetimeStr, host } =
    createSigningContext(credentials);
  const encodedKey = encodeObjectKey(key);
  const url = `https://${host}/${encodedKey}`;
  const payloadHash = await sha256Hex(body);

  const canonicalRequest = [
    "PUT",
    `/${encodedKey}`,
    "",
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

  const signingKey = await buildSigningKey(
    secretAccessKey,
    dateStr,
    credentials.region,
  );
  const signature = toHex(await hmacSha256(signingKey, stringToSign));

  const response = await fetch(url, {
    method: "PUT",
    headers: {
      Authorization: `AWS4-HMAC-SHA256 Credential=${accessKeyId}/${credentialScope}, SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date, Signature=${signature}`,
      "Content-Type": contentType,
      "x-amz-content-sha256": payloadHash,
      "x-amz-date": datetimeStr,
    },
    body,
  });

  if (!response.ok) {
    const message = await response.text().catch(() => response.statusText);
    return { key, error: `S3 upload failed (${response.status}): ${message}` };
  }

  return { key };
}

export async function deleteS3Object(options: {
  credentials: S3Credentials;
  key: string;
}): Promise<{ error?: string }> {
  const { credentials, key } = options;
  const { accessKeyId, secretAccessKey } = credentials;
  const { credentialScope, dateStr, datetimeStr, host } =
    createSigningContext(credentials);
  const encodedKey = encodeObjectKey(key);
  const url = `https://${host}/${encodedKey}`;
  const payloadHash =
    "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855";

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

  const signingKey = await buildSigningKey(
    secretAccessKey,
    dateStr,
    credentials.region,
  );
  const signature = toHex(await hmacSha256(signingKey, stringToSign));

  const response = await fetch(url, {
    method: "DELETE",
    headers: {
      Authorization: `AWS4-HMAC-SHA256 Credential=${accessKeyId}/${credentialScope}, SignedHeaders=host;x-amz-content-sha256;x-amz-date, Signature=${signature}`,
      "x-amz-content-sha256": payloadHash,
      "x-amz-date": datetimeStr,
    },
  });

  if (!response.ok && response.status !== 404) {
    const message = await response.text().catch(() => response.statusText);
    return { error: `S3 delete failed (${response.status}): ${message}` };
  }

  return {};
}

export async function createS3SignedGetUrl(options: {
  credentials: S3Credentials;
  key: string;
  expiresInSeconds?: number;
}): Promise<string> {
  const { credentials, key, expiresInSeconds = 3600 } = options;
  const { accessKeyId, secretAccessKey } = credentials;
  const { credentialScope, dateStr, datetimeStr, host } =
    createSigningContext(credentials);
  const encodedKey = encodeObjectKey(key);
  const credential = `${accessKeyId}/${credentialScope}`;

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
    `/${encodedKey}`,
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

  const signingKey = await buildSigningKey(
    secretAccessKey,
    dateStr,
    credentials.region,
  );
  const signature = toHex(await hmacSha256(signingKey, stringToSign));

  return `https://${host}/${encodedKey}?${sortedQuery}&X-Amz-Signature=${signature}`;
}
