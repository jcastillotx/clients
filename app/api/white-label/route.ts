import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { whiteLabelConfigs } from "@/lib/db/schema/additional-features";
import { eq } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { z } from "zod";

const querySchema = z.object({
  clientId: z.string().uuid(),
});

const upsertSchema = z.object({
  clientId: z.string().uuid(),
  domain: z.string().nullable().optional(),
  logoUrl: z.string().nullable().optional(),
  faviconUrl: z.string().nullable().optional(),
  primaryColor: z.string().nullable().optional(),
  secondaryColor: z.string().nullable().optional(),
  customCss: z.string().nullable().optional(),
  emailFromName: z.string().nullable().optional(),
  isActive: z.boolean().optional(),
});

/**
 * GET /api/white-label
 * Retrieve white label configuration for a client
 */
export async function GET(request: NextRequest) {
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

    const searchParams = request.nextUrl.searchParams;
    const parsed = querySchema.safeParse({
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

    const config = await db
      .select()
      .from(whiteLabelConfigs)
      .where(eq(whiteLabelConfigs.clientId, clientId))
      .limit(1);

    if (config.length === 0) {
      return NextResponse.json(
        { error: "White label config not found" },
        { status: 404 },
      );
    }

    return NextResponse.json(config[0]);
  } catch (error) {
    console.error("Error fetching white label config:", error);
    return NextResponse.json(
      { error: "Failed to fetch white label config" },
      { status: 500 },
    );
  }
}

/**
 * POST /api/white-label
 * Create or update white label configuration
 */
export async function POST(request: NextRequest) {
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
    const parsed = upsertSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json(
        { error: "Client ID is required" },
        { status: 400 },
      );
    }

    const {
      clientId,
      domain,
      logoUrl,
      faviconUrl,
      primaryColor,
      secondaryColor,
      customCss,
      emailFromName,
      isActive,
    } = parsed.data;

    if (!canAccessClient(access, clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    // Check if config already exists
    const existing = await db
      .select()
      .from(whiteLabelConfigs)
      .where(eq(whiteLabelConfigs.clientId, clientId))
      .limit(1);

    let config;

    if (existing.length > 0) {
      // Update existing config
      config = await db
        .update(whiteLabelConfigs)
        .set({
          domain,
          logoUrl,
          faviconUrl,
          primaryColor,
          secondaryColor,
          customCss,
          emailFromName,
          isActive,
          updatedAt: new Date(),
        })
        .where(eq(whiteLabelConfigs.clientId, clientId))
        .returning();
    } else {
      // Create new config
      config = await db
        .insert(whiteLabelConfigs)
        .values({
          clientId,
          domain,
          logoUrl,
          faviconUrl,
          primaryColor: primaryColor || "#000000",
          secondaryColor: secondaryColor || "#ffffff",
          customCss,
          emailFromName,
          isActive: isActive ?? true,
        })
        .returning();
    }

    return NextResponse.json(config[0]);
  } catch (error) {
    console.error("Error saving white label config:", error);
    return NextResponse.json(
      { error: "Failed to save white label config" },
      { status: 500 },
    );
  }
}

/**
 * DELETE /api/white-label
 * Delete white label configuration
 */
export async function DELETE(request: NextRequest) {
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

    const searchParams = request.nextUrl.searchParams;
    const parsed = querySchema.safeParse({
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

    await db
      .delete(whiteLabelConfigs)
      .where(eq(whiteLabelConfigs.clientId, clientId));

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting white label config:", error);
    return NextResponse.json(
      { error: "Failed to delete white label config" },
      { status: 500 },
    );
  }
}
