import { NextRequest } from "next/server";
import { desc, eq } from "drizzle-orm";
import { z } from "zod";

import { apiInternalError, apiSuccess, apiValidationError } from "@/lib/api/response";
import { db } from "@/lib/db";
import { projectReviewComments, projectReviewItems } from "@/lib/db/schema/projects";
import { users } from "@/lib/db/schema/users";
import { requireProjectReviewAccess } from "@/lib/projects/review-access";
import { createWebsiteReviewSchema } from "@/lib/validations/project-review";

type RouteContext = { params: Promise<{ id: string }> };

function toNumber(value: string | null): number | null {
  if (value == null) {
    return null;
  }
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function serializeComment(row: {
  id: string;
  reviewItemId: string;
  projectId: string;
  authorId: string | null;
  body: string;
  xPercent: string | null;
  yPercent: string | null;
  status: string;
  createdAt: Date;
  updatedAt: Date;
  authorName: string | null;
  authorEmail: string | null;
}) {
  return {
    id: row.id,
    reviewItemId: row.reviewItemId,
    projectId: row.projectId,
    authorId: row.authorId,
    authorName: row.authorName,
    authorEmail: row.authorEmail,
    body: row.body,
    xPercent: toNumber(row.xPercent),
    yPercent: toNumber(row.yPercent),
    status: row.status,
    createdAt: row.createdAt.toISOString(),
    updatedAt: row.updatedAt.toISOString(),
  };
}

export async function GET(request: NextRequest, { params }: RouteContext) {
  const { id } = await params;

  try {
    const guard = await requireProjectReviewAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const [items, commentRows] = await Promise.all([
      db
        .select()
        .from(projectReviewItems)
        .where(eq(projectReviewItems.projectId, id))
        .orderBy(desc(projectReviewItems.createdAt)),
      db
        .select({
          id: projectReviewComments.id,
          reviewItemId: projectReviewComments.reviewItemId,
          projectId: projectReviewComments.projectId,
          authorId: projectReviewComments.authorId,
          body: projectReviewComments.body,
          xPercent: projectReviewComments.xPercent,
          yPercent: projectReviewComments.yPercent,
          status: projectReviewComments.status,
          createdAt: projectReviewComments.createdAt,
          updatedAt: projectReviewComments.updatedAt,
          authorName: users.name,
          authorEmail: users.email,
        })
        .from(projectReviewComments)
        .leftJoin(users, eq(users.id, projectReviewComments.authorId))
        .where(eq(projectReviewComments.projectId, id))
        .orderBy(desc(projectReviewComments.createdAt)),
    ]);

    const commentsByItem = new Map<string, ReturnType<typeof serializeComment>[]>();
    for (const comment of commentRows) {
      const serialized = serializeComment(comment);
      const itemComments = commentsByItem.get(comment.reviewItemId) ?? [];
      itemComments.push(serialized);
      commentsByItem.set(comment.reviewItemId, itemComments);
    }

    const payload = items.map((item) => ({
      id: item.id,
      projectId: item.projectId,
      type: item.type,
      title: item.title,
      websiteUrl: item.websiteUrl,
      imageUrl:
        item.type === "image"
          ? `/api/projects/${item.projectId}/reviews/${item.id}/asset`
          : null,
      imageFileName: item.imageFileName,
      imageMimeType: item.imageMimeType,
      imageSize: item.imageSize,
      status: item.status,
      createdAt: item.createdAt.toISOString(),
      updatedAt: item.updatedAt.toISOString(),
      comments: commentsByItem.get(item.id) ?? [],
    }));

    return apiSuccess(request, payload, { extra: { reviews: payload } });
  } catch (error) {
    console.error("Error fetching project reviews:", error);
    return apiInternalError(request, "Failed to fetch project reviews");
  }
}

export async function POST(request: NextRequest, { params }: RouteContext) {
  const { id } = await params;

  try {
    const guard = await requireProjectReviewAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const parsed = createWebsiteReviewSchema.parse(await request.json());
    const [item] = await db
      .insert(projectReviewItems)
      .values({
        projectId: id,
        type: parsed.type,
        title: parsed.title,
        websiteUrl: parsed.websiteUrl,
        createdBy: guard.user.id,
      })
      .returning();

    const payload = {
      ...item,
      imageUrl: null,
      comments: [],
    };

    return apiSuccess(request, payload, { status: 201, extra: { review: payload } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }

    console.error("Error creating project review:", error);
    return apiInternalError(request, "Failed to create project review");
  }
}
