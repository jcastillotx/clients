import { NextResponse } from "next/server";
import { db } from "@/lib/db";
import { socialPosts, socialAccounts, postStatusEnum, type PostStatus } from "@/lib/db/schema/social-media";
import { eq, and, isNull, desc, gte, lte } from "drizzle-orm";

/**
 * GET /api/social/posts
 * List social media posts with filtering
 */
export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const accountId = searchParams.get("accountId");
    const clientId = searchParams.get("clientId");
    const status = searchParams.get("status");
    const startDate = searchParams.get("startDate");
    const endDate = searchParams.get("endDate");

    let query = db
      .select({
        post: socialPosts,
        account: socialAccounts,
      })
      .from(socialPosts)
      .leftJoin(socialAccounts, eq(socialPosts.accountId, socialAccounts.id))
      .$dynamic();

    // Apply filters
    const conditions = [isNull(socialPosts.deletedAt)];

    if (accountId) {
      conditions.push(eq(socialPosts.accountId, accountId));
    }

    if (clientId) {
      conditions.push(eq(socialAccounts.clientId, clientId));
    }

    if (status && (postStatusEnum as readonly string[]).includes(status)) {
      conditions.push(eq(socialPosts.status, status as PostStatus));
    }

    if (startDate) {
      conditions.push(gte(socialPosts.scheduledFor, new Date(startDate)));
    }

    if (endDate) {
      conditions.push(lte(socialPosts.scheduledFor, new Date(endDate)));
    }

    if (conditions.length > 0) {
      query = query.where(and(...conditions));
    }

    const posts = await query.orderBy(desc(socialPosts.scheduledFor));

    return NextResponse.json(posts);
  } catch (error) {
    console.error("Error fetching social posts:", error);
    return NextResponse.json({ error: "Failed to fetch social posts" }, { status: 500 });
  }
}

/**
 * POST /api/social/posts
 * Create or schedule a new social media post
 */
export async function POST(request: Request) {
  try {
    const body = await request.json();
    const { accountId, content, scheduledFor, createdBy, metadata, publishNow } = body;

    if (!accountId || !content || !createdBy) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    const status = publishNow ? "published" : scheduledFor ? "scheduled" : "draft";
    const publishedAt = publishNow ? new Date() : null;

    const [newPost] = await db
      .insert(socialPosts)
      .values({
        accountId,
        content,
        scheduledFor: scheduledFor ? new Date(scheduledFor) : null,
        publishedAt,
        status,
        createdBy,
        metadata,
      })
      .returning();

    // TODO: If publishNow, trigger immediate posting to social platform
    // TODO: If scheduled, add to job queue for future posting

    return NextResponse.json(newPost, { status: 201 });
  } catch (error) {
    console.error("Error creating social post:", error);
    return NextResponse.json({ error: "Failed to create social post" }, { status: 500 });
  }
}

/**
 * PATCH /api/social/posts/:id
 * Update a social media post
 */
export async function PATCH(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const postId = searchParams.get("id");
    const body = await request.json();

    if (!postId) {
      return NextResponse.json({ error: "Post ID is required" }, { status: 400 });
    }

    const { content, scheduledFor, status, engagementMetrics, postUrl, metadata } = body;

    const [updatedPost] = await db
      .update(socialPosts)
      .set({
        content,
        scheduledFor: scheduledFor ? new Date(scheduledFor) : undefined,
        status,
        engagementMetrics,
        postUrl,
        metadata,
        updatedAt: new Date(),
      })
      .where(eq(socialPosts.id, postId))
      .returning();

    return NextResponse.json(updatedPost);
  } catch (error) {
    console.error("Error updating social post:", error);
    return NextResponse.json({ error: "Failed to update social post" }, { status: 500 });
  }
}

/**
 * DELETE /api/social/posts/:id
 * Soft delete a social post
 */
export async function DELETE(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const postId = searchParams.get("id");

    if (!postId) {
      return NextResponse.json({ error: "Post ID is required" }, { status: 400 });
    }

    await db
      .update(socialPosts)
      .set({
        deletedAt: new Date(),
        status: "deleted",
      })
      .where(eq(socialPosts.id, postId));

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting social post:", error);
    return NextResponse.json({ error: "Failed to delete social post" }, { status: 500 });
  }
}
