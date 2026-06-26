import { NextRequest } from "next/server";

import { apiError, apiInternalError, apiSuccess } from "@/lib/api/response";
import { db } from "@/lib/db";
import { projectReviewItems } from "@/lib/db/schema/projects";
import { requireProjectReviewAccess } from "@/lib/projects/review-access";
import { getS3Credentials } from "@/lib/storage/get-s3-credentials";
import { putS3Object } from "@/lib/storage/s3";
import { generateFilePath } from "@/lib/storage/utils";

type RouteContext = { params: Promise<{ id: string }> };

const MAX_REVIEW_IMAGE_BYTES = 15 * 1024 * 1024;

export async function POST(request: NextRequest, { params }: RouteContext) {
  const { id } = await params;

  try {
    const guard = await requireProjectReviewAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const formData = await request.formData();
    const file = formData.get("file");
    const title = String(formData.get("title") || "").trim();

    if (!(file instanceof File) || !title) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Image and title are required",
      });
    }

    if (!file.type.startsWith("image/")) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Only image uploads are supported for image reviews",
      });
    }

    if (file.size > MAX_REVIEW_IMAGE_BYTES) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Review images must be 15MB or less",
      });
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

    const storagePath = generateFilePath(
      guard.project.clientId,
      `project-reviews/${id}`,
      file.name,
    );
    const uploadResult = await putS3Object({
      credentials: s3Credentials,
      key: storagePath,
      body: await file.arrayBuffer(),
      contentType: file.type || "application/octet-stream",
    });

    if (uploadResult.error) {
      return apiInternalError(request, uploadResult.error);
    }

    const [item] = await db
      .insert(projectReviewItems)
      .values({
        projectId: id,
        type: "image",
        title,
        imageStoragePath: uploadResult.key,
        imageFileName: file.name,
        imageMimeType: file.type,
        imageSize: file.size,
        createdBy: guard.user.id,
      })
      .returning();

    const payload = {
      id: item.id,
      projectId: item.projectId,
      type: item.type,
      title: item.title,
      websiteUrl: item.websiteUrl,
      imageUrl: `/api/projects/${item.projectId}/reviews/${item.id}/asset`,
      imageFileName: item.imageFileName,
      imageMimeType: item.imageMimeType,
      imageSize: item.imageSize,
      status: item.status,
      createdAt: item.createdAt.toISOString(),
      updatedAt: item.updatedAt.toISOString(),
      comments: [],
    };

    return apiSuccess(request, payload, { status: 201, extra: { review: payload } });
  } catch (error) {
    console.error("Error uploading project review image:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to upload review image",
    );
  }
}
