import { NextResponse } from "next/server";
import { and, desc, eq, gte, isNull, lte } from "drizzle-orm";
import { z } from "zod";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { db } from "@/lib/db";
import {
  postStatusEnum,
  socialAccounts,
  socialPosts,
  type PostStatus,
} from "@/lib/db/schema/social-media";
import { createClient } from "@/lib/supabase/server";

const listSchema = z.object({
  accountId: z.string().uuid().optional(),
  clientId: z.string().uuid().optional(),
  status: z.string().optional(),
  startDate: z.string().optional(),
  endDate: z.string().optional(),
});

const createSchema = z.object({
  accountId: z.string().uuid(),
  content: z.string().min(1),
  scheduledFor: z.string().optional(),
  metadata: z.record(z.string(), z.unknown()).optional(),
  publishNow: z.boolean().optional(),
});

const patchSchema = z.object({
  id: z.string().uuid(),
  content: z.string().optional(),
  scheduledFor: z.string().optional(),
  status: z.enum(postStatusEnum).optional(),
  engagementMetrics: z.record(z.string(), z.unknown()).optional(),
  postUrl: z.string().optional(),
  metadata: z.record(z.string(), z.unknown()).optional(),
});

const deleteSchema = z.object({
  id: z.string().uuid(),
});

async function requireUserAccess() {
  const supabase = await createClient();
  const {
    data: { user },
    error: authError,
  } = await supabase.auth.getUser();

  if (authError || !user) {
    return {
      error: NextResponse.json({ error: "Unauthorized" }, { status: 401 }),
    };
  }

  const access = await resolveRouteAccess(supabase, user);
  return { user, access };
}

async function requireAccountAccess(accountId: string) {
  const auth = await requireUserAccess();
  if ("error" in auth) {
    return auth;
  }

  const [account] = await db
    .select()
    .from(socialAccounts)
    .where(eq(socialAccounts.id, accountId))
    .limit(1);
  if (!account) {
    return {
      error: NextResponse.json({ error: "Account not found" }, { status: 404 }),
    };
  }

  if (!canAccessClient(auth.access, account.clientId)) {
    return {
      error: NextResponse.json({ error: "Forbidden" }, { status: 403 }),
    };
  }

  return { ...auth, account };
}

/**
 * GET /api/social/posts
 * List social media posts with filtering
 */
export async function GET(request: Request) {
  try {
    const auth = await requireUserAccess();
    if ("error" in auth) {
      return auth.error;
    }

    const { searchParams } = new URL(request.url);
    const parsed = listSchema.safeParse({
      accountId: searchParams.get("accountId") || undefined,
      clientId: searchParams.get("clientId") || undefined,
      status: searchParams.get("status") || undefined,
      startDate: searchParams.get("startDate") || undefined,
      endDate: searchParams.get("endDate") || undefined,
    });

    if (!parsed.success) {
      return NextResponse.json(
        { error: "Invalid query parameters" },
        { status: 400 },
      );
    }

    const { accountId, clientId, status, startDate, endDate } = parsed.data;

    if (clientId && !canAccessClient(auth.access, clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    if (accountId) {
      const accountGuard = await requireAccountAccess(accountId);
      if ("error" in accountGuard) {
        return accountGuard.error;
      }
    }

    let query = db
      .select({
        post: socialPosts,
        account: socialAccounts,
      })
      .from(socialPosts)
      .leftJoin(socialAccounts, eq(socialPosts.accountId, socialAccounts.id))
      .$dynamic();

    const conditions = [isNull(socialPosts.deletedAt)];

    if (accountId) {
      conditions.push(eq(socialPosts.accountId, accountId));
    }

    const effectiveClientId =
      clientId ?? (!auth.access.isAdmin ? auth.access.clientId : null);
    if (effectiveClientId) {
      conditions.push(eq(socialAccounts.clientId, effectiveClientId));
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

    query = query.where(and(...conditions));
    const posts = await query.orderBy(desc(socialPosts.scheduledFor));

    return NextResponse.json(posts);
  } catch (error) {
    console.error("Error fetching social posts:", error);
    return NextResponse.json(
      { error: "Failed to fetch social posts" },
      { status: 500 },
    );
  }
}

/**
 * POST /api/social/posts
 * Create or schedule a new social media post
 */
export async function POST(request: Request) {
  try {
    const auth = await requireUserAccess();
    if ("error" in auth) {
      return auth.error;
    }

    const body = await request.json();
    const parsed = createSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json(
        { error: "Missing required fields" },
        { status: 400 },
      );
    }

    const { accountId, content, scheduledFor, metadata, publishNow } =
      parsed.data;

    const accountGuard = await requireAccountAccess(accountId);
    if ("error" in accountGuard) {
      return accountGuard.error;
    }

    const status = publishNow
      ? "published"
      : scheduledFor
        ? "scheduled"
        : "draft";
    const publishedAt = publishNow ? new Date() : null;

    const [newPost] = await db
      .insert(socialPosts)
      .values({
        accountId,
        content,
        scheduledFor: scheduledFor ? new Date(scheduledFor) : null,
        publishedAt,
        status,
        createdBy: auth.user.id,
        metadata,
      })
      .returning();

    return NextResponse.json(newPost, { status: 201 });
  } catch (error) {
    console.error("Error creating social post:", error);
    return NextResponse.json(
      { error: "Failed to create social post" },
      { status: 500 },
    );
  }
}

/**
 * PATCH /api/social/posts/:id
 * Update a social media post
 */
export async function PATCH(request: Request) {
  try {
    const auth = await requireUserAccess();
    if ("error" in auth) {
      return auth.error;
    }

    const { searchParams } = new URL(request.url);
    const body = await request.json();
    const parsed = patchSchema.safeParse({
      id: searchParams.get("id"),
      ...body,
    });

    if (!parsed.success) {
      return NextResponse.json(
        { error: "Post ID is required" },
        { status: 400 },
      );
    }

    const {
      id,
      content,
      scheduledFor,
      status,
      engagementMetrics,
      postUrl,
      metadata,
    } = parsed.data;

    const [postRow] = await db
      .select({
        postId: socialPosts.id,
        clientId: socialAccounts.clientId,
      })
      .from(socialPosts)
      .leftJoin(socialAccounts, eq(socialPosts.accountId, socialAccounts.id))
      .where(eq(socialPosts.id, id))
      .limit(1);

    if (!postRow?.postId || !postRow.clientId) {
      return NextResponse.json({ error: "Post not found" }, { status: 404 });
    }

    if (!canAccessClient(auth.access, postRow.clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

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
      .where(eq(socialPosts.id, id))
      .returning();

    return NextResponse.json(updatedPost);
  } catch (error) {
    console.error("Error updating social post:", error);
    return NextResponse.json(
      { error: "Failed to update social post" },
      { status: 500 },
    );
  }
}

/**
 * DELETE /api/social/posts/:id
 * Soft delete a social post
 */
export async function DELETE(request: Request) {
  try {
    const auth = await requireUserAccess();
    if ("error" in auth) {
      return auth.error;
    }

    const { searchParams } = new URL(request.url);
    const parsed = deleteSchema.safeParse({ id: searchParams.get("id") });

    if (!parsed.success) {
      return NextResponse.json(
        { error: "Post ID is required" },
        { status: 400 },
      );
    }

    const { id } = parsed.data;

    const [postRow] = await db
      .select({
        postId: socialPosts.id,
        clientId: socialAccounts.clientId,
      })
      .from(socialPosts)
      .leftJoin(socialAccounts, eq(socialPosts.accountId, socialAccounts.id))
      .where(eq(socialPosts.id, id))
      .limit(1);

    if (!postRow?.postId || !postRow.clientId) {
      return NextResponse.json({ error: "Post not found" }, { status: 404 });
    }

    if (!canAccessClient(auth.access, postRow.clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    await db
      .update(socialPosts)
      .set({
        deletedAt: new Date(),
        status: "deleted",
      })
      .where(eq(socialPosts.id, id));

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting social post:", error);
    return NextResponse.json(
      { error: "Failed to delete social post" },
      { status: 500 },
    );
  }
}
