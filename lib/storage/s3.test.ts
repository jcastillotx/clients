import { afterEach, describe, expect, it, vi } from "vitest";
import { createS3SignedGetUrl, putS3Object } from "@/lib/storage/s3";
import type { S3Credentials } from "@/lib/storage/get-s3-credentials";

const credentials: S3Credentials = {
  accessKeyId: "AKIATEST",
  secretAccessKey: "secret",
  bucket: "client-files",
  region: "us-east-2",
};

describe("S3 storage helpers", () => {
  afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
  });

  it("creates presigned GET URLs for private S3 objects", async () => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date("2026-06-25T12:34:56.000Z"));

    const url = await createS3SignedGetUrl({
      credentials,
      key: "client-1/documents/file name.pdf",
      expiresInSeconds: 900,
    });

    expect(url).toContain(
      "https://client-files.s3.us-east-2.amazonaws.com/client-1/documents/file%20name.pdf",
    );
    expect(url).toContain("X-Amz-Algorithm=AWS4-HMAC-SHA256");
    expect(url).toContain("X-Amz-Date=20260625T123456Z");
    expect(url).toContain("X-Amz-Expires=900");
    expect(url).toContain("X-Amz-Signature=");
  });

  it("uploads objects with AWS Signature V4 headers", async () => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date("2026-06-25T12:34:56.000Z"));

    const fetchMock = vi.fn().mockResolvedValue(new Response("", { status: 200 }));
    vi.stubGlobal("fetch", fetchMock);

    const result = await putS3Object({
      credentials,
      key: "client-1/documents/logo.svg",
      body: new TextEncoder().encode("<svg />").buffer,
      contentType: "image/svg+xml",
    });

    expect(result).toEqual({ key: "client-1/documents/logo.svg" });
    expect(fetchMock).toHaveBeenCalledWith(
      "https://client-files.s3.us-east-2.amazonaws.com/client-1/documents/logo.svg",
      expect.objectContaining({
        method: "PUT",
        headers: expect.objectContaining({
          Authorization: expect.stringContaining(
            "AWS4-HMAC-SHA256 Credential=AKIATEST/20260625/us-east-2/s3/aws4_request",
          ),
          "Content-Type": "image/svg+xml",
          "x-amz-date": "20260625T123456Z",
        }),
      }),
    );
  });
});
