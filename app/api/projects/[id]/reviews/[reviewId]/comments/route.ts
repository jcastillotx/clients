import { NextRequest } from "next/server";
import { and, desc, eq } from "drizzle-orm";
import { z } from "zod";

import {
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiValidationError,
} from "@/lib/api/response";
import { db } from "@/lib/db";
import { projectReviewComments, projectReviewItems } from "@/lib/db/schema/projects";
import { users } from "@/lib/db/schema/users";
import { requireProjectReviewAccess } from "@/lib/projects/review-access";
import { createReviewCommentSchema } from "@/lib/validations/project-review";

type RouteContext = { params: Promise<{ id: string; reviewId: string }> };

function toNumber(value: string | null): number | null {
  if (value == null) {
    return null;
  }
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

async function reviewExists(projectId: string, reviewId: string): Promise<boolean> {
  const [item] = await db
    .select({ id: projectReviewItems.id })
    .from(projectReviewItems)
    .where(
      and(
        eq(projectReviewItems.id, reviewId),
        eq(projectReviewItems.projectId, projectId),
      ),
    )
    .limit(1);

  return Boolean(item);
}

export async function GET(request: NextRequest, { params }: RouteContext) {
  const { id, reviewId } = await params;

  try {
    const guard = await requireProjectReviewAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    if (!(await reviewExists(id, reviewId))) {
      return apiNotFound(request, "Review item not found");
    }

    const rows = await db
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
      .where(eq(projectReviewComments.reviewItemId, reviewId))
      .orderBy(desc(projectReviewComments.createdAt));

    const payload = rows.map((row) => ({
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
    }));

    return apiSuccess(request, payload, { extra: { comments: payload } });
  } catch (error) {
    console.error("Error fetching project review comments:", error);
    return apiInternalError(request, "Failed to fetch review comments");
  }
}

export async function POST(request: NextRequest, { params }: RouteContext) {
  const { id, reviewId } = await params;

  try {
    const guard = await requireProjectReviewAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    if (!(await reviewExists(id, reviewId))) {
      return apiNotFound(request, "Review item not found");
    }

    const parsed = createReviewCommentSchema.parse(await request.json());
    const [comment] = await db
      .insert(projectReviewComments)
      .values({
        reviewItemId: reviewId,
        projectId: id,
        authorId: guard.user.id,
        body: parsed.body,
        xPercent: parsed.xPercent == null ? null : parsed.xPercent.toFixed(3),
        yPercent: parsed.yPercent == null ? null : parsed.yPercent.toFixed(3),
      })
      .returning();

    const payload = {
      id: comment.id,
      reviewItemId: comment.reviewItemId,
      projectId: comment.projectId,
      authorId: comment.authorId,
      authorName: guard.user.user_metadata?.name ?? guard.user.email ?? null,
      authorEmail: guard.user.email ?? null,
      body: comment.body,
      xPercent: toNumber(comment.xPercent),
      yPercent: toNumber(comment.yPercent),
      status: comment.status,
      createdAt: comment.createdAt.toISOString(),
      updatedAt: comment.updatedAt.toISOString(),
    };

    return apiSuccess(request, payload, { status: 201, extra: { comment: payload } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }

    console.error("Error creating project review comment:", error);
    return apiInternalError(request, "Failed to create review comment");
  }
}
