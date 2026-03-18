import { NextResponse } from "next/server";
import { and, eq, isNull } from "drizzle-orm";
import { z } from "zod";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { db } from "@/lib/db";
import { socialAccounts } from "@/lib/db/schema/social-media";
import { encrypt } from "@/lib/encryption";
import { createClient } from "@/lib/supabase/server";

const socialPlatformSchema = z.enum([
  "facebook",
  "instagram",
  "twitter",
  "linkedin",
  "tiktok",
  "youtube",
  "pinterest",
]);

const listSchema = z.object({
  clientId: z.string().uuid(),
});

const createSchema = z.object({
  clientId: z.string().uuid(),
  platform: socialPlatformSchema,
  accountName: z.string().min(1),
  accountId: z.string().min(1),
  accessToken: z.string().min(1),
  refreshToken: z.string().optional(),
  expiresAt: z.string().optional(),
  metadata: z.record(z.string(), z.unknown()).optional(),
});

const patchSchema = z.object({
  id: z.string().uuid(),
  isActive: z.boolean().optional(),
  metadata: z.record(z.string(), z.unknown()).optional(),
});

const deleteSchema = z.object({
  id: z.string().uuid(),
});

/**
 * GET /api/social/accounts
 * List all social media accounts for a client
 */
export async function GET(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const access = await resolveRouteAccess(supabase, user);
    const { searchParams } = new URL(request.url);
    const parsed = listSchema.safeParse({
      clientId: searchParams.get("clientId"),
    });

    if (!parsed.success) {
      return NextResponse.json(
        { error: "Client ID is required" },
        { status: 400 },
      );
    }

    const { clientId } = parsed.data;
    if (!canAccessClient(access, clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const accounts = await db
      .select()
      .from(socialAccounts)
      .where(
        and(
          eq(socialAccounts.clientId, clientId),
          isNull(socialAccounts.deletedAt),
        ),
      );

    const sanitizedAccounts = accounts.map(
      (account: (typeof accounts)[number]) => ({
        ...account,
        accessTokenEncrypted: undefined,
        refreshTokenEncrypted: undefined,
      }),
    );

    return NextResponse.json(sanitizedAccounts);
  } catch (error) {
    console.error("Error fetching social accounts:", error);
    return NextResponse.json(
      { error: "Failed to fetch social accounts" },
      { status: 500 },
    );
  }
}

/**
 * POST /api/social/accounts
 * Connect a new social media account
 */
export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const access = await resolveRouteAccess(supabase, user);
    const body = await request.json();
    const parsed = createSchema.safeParse(body);

    if (!parsed.success) {
      return NextResponse.json(
        { error: "Missing required fields" },
        { status: 400 },
      );
    }

    const {
      clientId,
      platform,
      accountName,
      accountId,
      accessToken,
      refreshToken,
      expiresAt,
      metadata,
    } = parsed.data;

    if (!canAccessClient(access, clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const accessTokenEncrypted = encrypt(accessToken);
    const refreshTokenEncrypted = refreshToken ? encrypt(refreshToken) : null;

    const newAccountRow: typeof socialAccounts.$inferInsert = {
      clientId,
      platform,
      accountName,
      accountId,
      accessTokenEncrypted,
      refreshTokenEncrypted,
      expiresAt: expiresAt ? new Date(expiresAt) : null,
      metadata,
    };

    const [newAccount] = await db
      .insert(socialAccounts)
      .values(newAccountRow)
      .returning();

    return NextResponse.json(
      {
        ...newAccount,
        accessTokenEncrypted: undefined,
        refreshTokenEncrypted: undefined,
      },
      { status: 201 },
    );
  } catch (error) {
    console.error("Error creating social account:", error);
    return NextResponse.json(
      { error: "Failed to create social account" },
      { status: 500 },
    );
  }
}

/**
 * PATCH /api/social/accounts/:id
 * Update social account status or metadata
 */
export async function PATCH(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const access = await resolveRouteAccess(supabase, user);
    const { searchParams } = new URL(request.url);
    const body = await request.json();
    const parsed = patchSchema.safeParse({
      id: searchParams.get("id"),
      ...body,
    });

    if (!parsed.success) {
      return NextResponse.json(
        { error: "Account ID is required" },
        { status: 400 },
      );
    }

    const { id: accountId, isActive, metadata } = parsed.data;

    const [existingAccount] = await db
      .select({ clientId: socialAccounts.clientId })
      .from(socialAccounts)
      .where(eq(socialAccounts.id, accountId))
      .limit(1);

    if (!existingAccount) {
      return NextResponse.json({ error: "Account not found" }, { status: 404 });
    }

    if (!canAccessClient(access, existingAccount.clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const [updatedAccount] = await db
      .update(socialAccounts)
      .set({
        isActive,
        metadata,
        updatedAt: new Date(),
      })
      .where(eq(socialAccounts.id, accountId))
      .returning();

    return NextResponse.json({
      ...updatedAccount,
      accessTokenEncrypted: undefined,
      refreshTokenEncrypted: undefined,
    });
  } catch (error) {
    console.error("Error updating social account:", error);
    return NextResponse.json(
      { error: "Failed to update social account" },
      { status: 500 },
    );
  }
}

/**
 * DELETE /api/social/accounts/:id
 * Soft delete a social account
 */
export async function DELETE(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const access = await resolveRouteAccess(supabase, user);
    const { searchParams } = new URL(request.url);
    const parsed = deleteSchema.safeParse({ id: searchParams.get("id") });

    if (!parsed.success) {
      return NextResponse.json(
        { error: "Account ID is required" },
        { status: 400 },
      );
    }

    const { id: accountId } = parsed.data;

    const [existingAccount] = await db
      .select({ clientId: socialAccounts.clientId })
      .from(socialAccounts)
      .where(eq(socialAccounts.id, accountId))
      .limit(1);

    if (!existingAccount) {
      return NextResponse.json({ error: "Account not found" }, { status: 404 });
    }

    if (!canAccessClient(access, existingAccount.clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    await db
      .update(socialAccounts)
      .set({
        deletedAt: new Date(),
        isActive: false,
      })
      .where(eq(socialAccounts.id, accountId));

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting social account:", error);
    return NextResponse.json(
      { error: "Failed to delete social account" },
      { status: 500 },
    );
  }
}
