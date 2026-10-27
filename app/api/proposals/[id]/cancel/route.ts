import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { proposals } from "@/lib/db/schema/proposals";
import { and, eq, ne } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

/**
 * POST /api/proposals/[id]/cancel
 * Client cancels a proposal
 */
export async function POST(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request, "Authentication required");
    }

    const [existing] = await db
      .select()
      .from(proposals)
      .where(eq(proposals.id, id))
      .limit(1);

    if (!existing) {
      return apiNotFound(request, "Proposal not found");
    }

    const access = await resolveRouteAccess(supabase, user);
    if (!canAccessClient(access, existing.clientId))
      return apiForbidden(request);

    if (existing.status === "accepted") {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Cannot cancel an accepted proposal",
      });
    }

    const [updated] = await db
      .update(proposals)
      .set({
        status: "cancelled",
        cancelledAt: new Date(),
        updatedAt: new Date(),
      })
      .where(and(eq(proposals.id, id), ne(proposals.status, "accepted")))
      .returning();

    if (!updated) {
      return apiError(request, {
        status: 409,
        code: "CONFLICT",
        message:
          "The proposal changed before cancellation. Refresh and try again.",
      });
    }

    return apiSuccess(request, updated, {
      extra: { proposal: updated, message: "Proposal cancelled successfully" },
    });
  } catch (error) {
    console.error("Error cancelling proposal:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return apiError(request, {
      status,
      code: status === 503 ? "SERVICE_UNAVAILABLE" : "INTERNAL_ERROR",
      message:
        error instanceof Error ? error.message : "Failed to cancel proposal",
    });
  }
}
