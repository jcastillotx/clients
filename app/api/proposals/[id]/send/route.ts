import { createClient } from "@/lib/supabase/server";
import { NextRequest, NextResponse } from "next/server";

/**
 * POST /api/proposals/[id]/send
 *
 * Send proposal to client via email and update status
 */
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {

    // Fetch proposal with client details
    const { data: proposal, error: fetchError } = await supabase
      .from("proposals")
      .select(
        `
        *,
        client:clients(id, company_name, email),
        creator:users!proposals_created_by_fkey(id, name, email)
      `,
      )
      .eq("id", id)
      .single();

    if (fetchError) throw fetchError;
    if (!proposal) {
      return NextResponse.json({ error: "Proposal not found" }, { status: 404 });
    }

    if (proposal.status !== "draft") {
      return NextResponse.json({ error: "Only draft proposals can be sent" }, { status: 400 });
    }

    // Update proposal status
    const { data: updatedProposal, error: updateError } = await supabase
      .from("proposals")
      .update({
        status: "sent",
        sent_at: new Date().toISOString(),
      })
      .eq("id", id)
      .select()
      .single();

    if (updateError) throw updateError;

    // TODO: Send email to client with proposal link
    // const proposalUrl = `${process.env.NEXT_PUBLIC_APP_URL}/proposals/${id}/preview`;
    // await sendProposalEmail(proposal.client.email, proposalUrl, proposal);

    return NextResponse.json({
      success: true,
      proposal: updatedProposal,
      message: "Proposal sent successfully",
    });
  } catch (error) {
    console.error("Error sending proposal:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to send proposal" },
      { status: 500 },
    );
  }
}
