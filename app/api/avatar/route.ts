import { createS3SignedGetUrl } from "@/lib/storage/s3";
import { NextRequest, NextResponse } from "next/server";
import { apiError, apiForbidden, apiUnauthorized } from "@/lib/api/response";
import { getS3Credentials } from "@/lib/storage/get-s3-credentials";
import { createClient } from "@/lib/supabase/server";

export async function GET(request: NextRequest) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(request);
  }

  const key = request.nextUrl.searchParams.get("key");
  if (!key) {
    return apiError(request, {
      status: 400,
      code: "BAD_REQUEST",
      message: "Missing key",
    });
  }

  if (
    !/^avatars\/[0-9a-f-]{36}\/[A-Za-z0-9_-]+\.(?:jpg|png|webp|gif)$/.test(key)
  ) {
    return apiForbidden(request, "Invalid avatar key");
  }

  const s3 = await getS3Credentials(user.id);
  if (!s3) {
    return apiError(request, {
      status: 503,
      code: "SERVICE_UNAVAILABLE",
      message: "S3 not configured",
    });
  }
  const presignedUrl = await createS3SignedGetUrl({
    credentials: s3,
    key,
    expiresInSeconds: 3600,
  });

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
      "Cache-Control": "private, max-age=3000",
      "Content-Type": contentType,
      "Cross-Origin-Resource-Policy": "same-origin",
    },
  });
}
