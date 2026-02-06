import { NextResponse } from "next/server";
import { db } from "@/lib/db";
import { socialAccounts } from "@/lib/db/schema/social-media";
import { eq, and, isNull } from "drizzle-orm";

/**
 * GET /api/social/accounts
 * List all social media accounts for a client
 */
export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const clientId = searchParams.get("clientId");

    if (!clientId) {
      return NextResponse.json({ error: "Client ID is required" }, { status: 400 });
    }

    const accounts = await db
      .select()
      .from(socialAccounts)
      .where(and(eq(socialAccounts.clientId, clientId), isNull(socialAccounts.deletedAt)));

    // Remove sensitive tokens from response
    const sanitizedAccounts = accounts.map((account) => ({
      ...account,
      accessTokenEncrypted: undefined,
      refreshTokenEncrypted: undefined,
    }));

    return NextResponse.json(sanitizedAccounts);
  } catch (error) {
    console.error("Error fetching social accounts:", error);
    return NextResponse.json({ error: "Failed to fetch social accounts" }, { status: 500 });
  }
}

/**
 * POST /api/social/accounts
 * Connect a new social media account
 */
export async function POST(request: Request) {
  try {
    const body = await request.json();
    const { clientId, platform, accountName, accountId, accessToken, refreshToken, expiresAt, metadata } = body;

    if (!clientId || !platform || !accountName || !accountId || !accessToken) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    // TODO: Encrypt tokens using a proper encryption library (e.g., crypto-js or @aws-crypto/client-node)
    // For now, we'll store them as-is (NOT SECURE - MUST IMPLEMENT ENCRYPTION)
    const accessTokenEncrypted = Buffer.from(accessToken).toString("base64");
    const refreshTokenEncrypted = refreshToken ? Buffer.from(refreshToken).toString("base64") : null;

    const [newAccount] = await db
      .insert(socialAccounts)
      .values({
        clientId,
        platform,
        accountName,
        accountId,
        accessTokenEncrypted,
        refreshTokenEncrypted,
        expiresAt: expiresAt ? new Date(expiresAt) : null,
        metadata,
      })
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
    return NextResponse.json({ error: "Failed to create social account" }, { status: 500 });
  }
}

/**
 * PATCH /api/social/accounts/:id
 * Update social account status or metadata
 */
export async function PATCH(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const accountId = searchParams.get("id");
    const body = await request.json();

    if (!accountId) {
      return NextResponse.json({ error: "Account ID is required" }, { status: 400 });
    }

    const { isActive, metadata } = body;

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
    return NextResponse.json({ error: "Failed to update social account" }, { status: 500 });
  }
}

/**
 * DELETE /api/social/accounts/:id
 * Soft delete a social account
 */
export async function DELETE(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const accountId = searchParams.get("id");

    if (!accountId) {
      return NextResponse.json({ error: "Account ID is required" }, { status: 400 });
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
    return NextResponse.json({ error: "Failed to delete social account" }, { status: 500 });
  }
}
