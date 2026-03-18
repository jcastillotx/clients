import { createClient } from "@/lib/supabase/server";
import { NextRequest, NextResponse } from "next/server";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { verifyProposalAccessToken } from "@/lib/proposals/public-access";
import { z } from "zod";

const trackViewSchema = z.object({
  token: z.string().optional(),
});

async function canAccessProposal(
  supabase: Awaited<ReturnType<typeof createClient>>,
  proposalId: string,
  proposalClientId: string,
  token?: string,
) {
  if (verifyProposalAccessToken(proposalId, token)) {
    return true;
  }

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return false;
  }

  const access = await resolveRouteAccess(supabase, user);
  return canAccessClient(access, proposalClientId);
}

/**
 * POST /api/proposals/[id]/track-view
 *
 * Track when a proposal is viewed
 */
export async function POST(
  req: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  const supabase = await createClient();

  try {
    const body = await req.json().catch(() => ({}));
    const parsedBody = trackViewSchema.safeParse(body);
    if (!parsedBody.success) {
      return NextResponse.json(
        { success: false, error: "Invalid payload" },
        { status: 400 },
      );
    }

    const ipAddress =
      req.headers.get("x-forwarded-for") ||
      req.headers.get("x-real-ip") ||
      "unknown";

    // Fetch proposal
    const { data: proposal, error: fetchError } = await supabase
      .from("proposals")
      .select("*")
      .eq("id", id)
      .single();

    if (fetchError) throw fetchError;
    if (!proposal) {
      return NextResponse.json(
        { error: "Proposal not found" },
        { status: 404 },
      );
    }

    const allowed = await canAccessProposal(
      supabase,
      id,
      proposal.client_id,
      parsedBody.data.token,
    );
    if (!allowed) {
      return NextResponse.json(
        { success: false, error: "Unauthorized" },
        { status: 401 },
      );
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
