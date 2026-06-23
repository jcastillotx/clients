import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

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
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);
    const { searchParams } = new URL(request.url);
    const parsed = listSchema.safeParse({
      clientId: searchParams.get("clientId"),
    });

    if (!parsed.success) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Client ID is required",
      });
    }

    const { clientId } = parsed.data;
    if (!canAccessClient(access, clientId)) {
      return apiForbidden(request);
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

    return apiSuccess(request, sanitizedAccounts);
  } catch (error) {
    console.error("Error fetching social accounts:", error);
    return apiInternalError(request, "Failed to fetch social accounts");
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
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);
    const body = await request.json();
    const parsed = createSchema.safeParse(body);

    if (!parsed.success) {
      return apiValidationError(request, parsed.error);
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
      return apiForbidden(request);
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

    return apiSuccess(
      request,
      {
        ...newAccount,
        accessTokenEncrypted: undefined,
        refreshTokenEncrypted: undefined,
      },
      { status: 201 },
    );
  } catch (error) {
    console.error("Error creating social account:", error);
    return apiInternalError(request, "Failed to create social account");
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
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);
    const { searchParams } = new URL(request.url);
    const body = await request.json();
    const parsed = patchSchema.safeParse({
      id: searchParams.get("id"),
      ...body,
    });

    if (!parsed.success) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Account ID is required",
      });
    }

    const { id: accountId, isActive, metadata } = parsed.data;

    const [existingAccount] = await db
      .select({ clientId: socialAccounts.clientId })
      .from(socialAccounts)
      .where(eq(socialAccounts.id, accountId))
      .limit(1);

    if (!existingAccount) {
      return apiNotFound(request, "Account not found");
    }

    if (!canAccessClient(access, existingAccount.clientId)) {
      return apiForbidden(request);
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

    return apiSuccess(request, {
      ...updatedAccount,
      accessTokenEncrypted: undefined,
      refreshTokenEncrypted: undefined,
    });
  } catch (error) {
    console.error("Error updating social account:", error);
    return apiInternalError(request, "Failed to update social account");
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
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);
    const { searchParams } = new URL(request.url);
    const parsed = deleteSchema.safeParse({ id: searchParams.get("id") });

    if (!parsed.success) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Account ID is required",
      });
    }

    const { id: accountId } = parsed.data;

    const [existingAccount] = await db
      .select({ clientId: socialAccounts.clientId })
      .from(socialAccounts)
      .where(eq(socialAccounts.id, accountId))
      .limit(1);

    if (!existingAccount) {
      return apiNotFound(request, "Account not found");
    }

    if (!canAccessClient(access, existingAccount.clientId)) {
      return apiForbidden(request);
    }

    await db
      .update(socialAccounts)
      .set({
        deletedAt: new Date(),
        isActive: false,
      })
      .where(eq(socialAccounts.id, accountId));

    return apiSuccess(request, { deleted: true }, { extra: { success: true } });
  } catch (error) {
    console.error("Error deleting social account:", error);
    return apiInternalError(request, "Failed to delete social account");
  }
}
