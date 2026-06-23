import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { proposals } from "@/lib/db/schema/proposals";
import { eq } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

/**
 * POST /api/proposals/[id]/feedback
 * Client sends feedback on a proposal
 */
export async function POST(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
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

    const body = await request.json();

    if (!body.feedback?.trim()) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Feedback message is required",
      });
    }

    const [existing] = await db.select().from(proposals).where(eq(proposals.id, id)).limit(1);

    if (!existing) {
      return apiNotFound(request, "Proposal not found");
    }

    const [updated] = await db
      .update(proposals)
      .set({
        clientFeedback: body.feedback,
        updatedAt: new Date(),
      })
      .where(eq(proposals.id, id))
      .returning();

    return apiSuccess(request, updated, {
      extra: { proposal: updated, message: "Feedback submitted successfully" },
    });
  } catch (error) {
    console.error("Error submitting feedback:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return apiError(request, {
      status,
      code: status === 503 ? "SERVICE_UNAVAILABLE" : "INTERNAL_ERROR",
      message: error instanceof Error ? error.message : "Failed to submit feedback",
    });
  }
}
