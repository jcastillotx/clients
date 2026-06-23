import { NextRequest } from "next/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
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

export async function GET(request: NextRequest) {
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
    const parsed = querySchema.safeParse({
      clientId: request.nextUrl.searchParams.get("clientId"),
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

    const config = await db
      .select()
      .from(whiteLabelConfigs)
      .where(eq(whiteLabelConfigs.clientId, clientId))
      .limit(1);

    if (config.length === 0) {
      return apiNotFound(request, "White label config not found");
    }

    return apiSuccess(request, config[0]);
  } catch (error) {
    console.error("Error fetching white label config:", error);
    return apiInternalError(request, "Failed to fetch white label config");
  }
}

export async function POST(request: NextRequest) {
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
    const parsed = upsertSchema.safeParse(body);
    if (!parsed.success) {
      return apiValidationError(request, parsed.error);
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
      return apiForbidden(request);
    }

    const existing = await db
      .select()
      .from(whiteLabelConfigs)
      .where(eq(whiteLabelConfigs.clientId, clientId))
      .limit(1);

    let config;

    if (existing.length > 0) {
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

    return apiSuccess(request, config[0]);
  } catch (error) {
    console.error("Error saving white label config:", error);
    return apiInternalError(request, "Failed to save white label config");
  }
}

export async function DELETE(request: NextRequest) {
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
    const parsed = querySchema.safeParse({
      clientId: request.nextUrl.searchParams.get("clientId"),
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

    await db
      .delete(whiteLabelConfigs)
      .where(eq(whiteLabelConfigs.clientId, clientId));

    return apiSuccess(request, { deleted: true }, { extra: { success: true } });
  } catch (error) {
    console.error("Error deleting white label config:", error);
    return apiInternalError(request, "Failed to delete white label config");
  }
}
