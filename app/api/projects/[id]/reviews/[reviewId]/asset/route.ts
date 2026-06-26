import { NextRequest, NextResponse } from "next/server";
import { and, eq } from "drizzle-orm";

import { apiError, apiInternalError, apiNotFound } from "@/lib/api/response";
import { db } from "@/lib/db";
import { projectReviewItems } from "@/lib/db/schema/projects";
import { requireProjectReviewAccess } from "@/lib/projects/review-access";
import { getS3Credentials } from "@/lib/storage/get-s3-credentials";
import { createS3SignedGetUrl } from "@/lib/storage/s3";

type RouteContext = { params: Promise<{ id: string; reviewId: string }> };

export async function GET(request: NextRequest, { params }: RouteContext) {
  const { id, reviewId } = await params;

  try {
    const guard = await requireProjectReviewAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const [item] = await db
      .select()
      .from(projectReviewItems)
      .where(
        and(
          eq(projectReviewItems.id, reviewId),
          eq(projectReviewItems.projectId, id),
        ),
      )
      .limit(1);

    if (!item || item.type !== "image" || !item.imageStoragePath) {
      return apiNotFound(request, "Review image not found");
    }

    const s3Credentials = await getS3Credentials(guard.user.id);
    if (!s3Credentials) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message:
          "No S3 storage connection configured. Add a platform S3 connection in Storage settings.",
      });
    }

    const signedUrl = await createS3SignedGetUrl({
      credentials: s3Credentials,
      key: item.imageStoragePath,
      expiresInSeconds: 600,
    });

    return NextResponse.redirect(signedUrl);
  } catch (error) {
    console.error("Error loading project review asset:", error);
    return apiInternalError(request, "Failed to load review image");
  }
}
