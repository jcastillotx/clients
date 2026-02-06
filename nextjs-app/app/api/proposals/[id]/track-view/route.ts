import { createClient } from "@/lib/supabase/server";
import { NextRequest, NextResponse } from "next/server";

/**
 * POST /api/proposals/[id]/track-view
 *
 * Track when a proposal is viewed
 */
export async function POST(req: NextRequest, { params }: { params: { id: string } }) {
  const supabase = createClient();

  try {
    const { id } = params;
    const ipAddress = req.headers.get("x-forwarded-for") || req.headers.get("x-real-ip") || "unknown";

    // Fetch proposal
    const { data: proposal, error: fetchError } = await supabase.from("proposals").select("*").eq("id", id).single();

    if (fetchError) throw fetchError;
    if (!proposal) {
      return NextResponse.json({ error: "Proposal not found" }, { status: 404 });
    }

    // Record the view
    const { error: viewError } = await supabase.from("proposal_views").insert({
      proposal_id: id,
      viewed_by_ip: ipAddress,
      viewed_at: new Date().toISOString(),
    });

    if (viewError) {
      console.error("Error recording view:", viewError);
      // Don't fail the request if view tracking fails
    }

    // Update proposal status to "viewed" if it's currently "sent"
    if (proposal.status === "sent") {
      const { error: updateError } = await supabase
        .from("proposals")
        .update({
          status: "viewed",
          viewed_at: new Date().toISOString(),
        })
        .eq("id", id);

      if (updateError) {
        console.error("Error updating proposal status:", updateError);
      }
    }

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error tracking view:", error);
    // Return success even if tracking fails to not disrupt the user experience
    return NextResponse.json({ success: true });
  }
}
