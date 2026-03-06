import { NextRequest, NextResponse } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { proposals } from "@/lib/db/schema/proposals";
import { eq } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";

/**
 * POST /api/proposals/[id]/cancel
 * Client cancels a proposal
 */
export async function POST(_request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const { data: { user }, error: authError } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ success: false, error: "Authentication required" }, { status: 401 });
    }

    const [existing] = await db.select().from(proposals).where(eq(proposals.id, id)).limit(1);

    if (!existing) {
      return NextResponse.json({ success: false, error: "Proposal not found" }, { status: 404 });
    }

    if (existing.status === "accepted") {
      return NextResponse.json({ success: false, error: "Cannot cancel an accepted proposal" }, { status: 400 });
    }

    const [updated] = await db
      .update(proposals)
      .set({
        status: "cancelled",
        cancelledAt: new Date(),
        updatedAt: new Date(),
      })
      .where(eq(proposals.id, id))
      .returning();

    return NextResponse.json({ success: true, data: updated, message: "Proposal cancelled successfully" });
  } catch (error) {
    console.error("Error cancelling proposal:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to cancel proposal", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}
