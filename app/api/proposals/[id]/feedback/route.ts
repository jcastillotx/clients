import { NextRequest, NextResponse } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { proposals } from "@/lib/db/schema/proposals";
import { eq } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";

/**
 * POST /api/proposals/[id]/feedback
 * Client sends feedback on a proposal
 */
export async function POST(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const { data: { user }, error: authError } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ success: false, error: "Authentication required" }, { status: 401 });
    }

    const body = await request.json();

    if (!body.feedback?.trim()) {
      return NextResponse.json({ success: false, error: "Feedback message is required" }, { status: 400 });
    }

    const [existing] = await db.select().from(proposals).where(eq(proposals.id, id)).limit(1);

    if (!existing) {
      return NextResponse.json({ success: false, error: "Proposal not found" }, { status: 404 });
    }

    const [updated] = await db
      .update(proposals)
      .set({
        clientFeedback: body.feedback,
        updatedAt: new Date(),
      })
      .where(eq(proposals.id, id))
      .returning();

    return NextResponse.json({ success: true, data: updated, message: "Feedback submitted successfully" });
  } catch (error) {
    console.error("Error submitting feedback:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to submit feedback", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}
