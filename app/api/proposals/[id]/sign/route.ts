import { createClient } from "@/lib/supabase/server";
import { NextRequest, NextResponse } from "next/server";

/**
 * POST /api/proposals/[id]/sign
 *
 * Accept or reject a proposal with signature
 */
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = createClient();

  try {
    const body = await req.json();
    const { action, signatureData, signerName, signerEmail, token } = body;

    // Fetch proposal
    const { data: proposal, error: fetchError } = await supabase.from("proposals").select("*").eq("id", id).single();

    if (fetchError) throw fetchError;
    if (!proposal) {
      return NextResponse.json({ error: "Proposal not found" }, { status: 404 });
    }

    // Check if proposal can be signed
    if (!["sent", "viewed"].includes(proposal.status)) {
      return NextResponse.json({ error: "This proposal cannot be signed" }, { status: 400 });
    }

    // Check if expired
    if (proposal.valid_until && new Date(proposal.valid_until) < new Date()) {
      return NextResponse.json({ error: "This proposal has expired" }, { status: 400 });
    }

    const now = new Date().toISOString();
    const ipAddress = req.headers.get("x-forwarded-for") || req.headers.get("x-real-ip") || "unknown";
    const userAgent = req.headers.get("user-agent") || undefined;

    let updates: any = {};

    if (action === "accept") {
      if (!signatureData || !signerName || !signerEmail) {
        return NextResponse.json({ error: "Signature data, name, and email are required" }, { status: 400 });
      }

      updates = {
        status: "accepted",
        accepted_at: now,
        signature_data: {
          signatureImage: signatureData,
          signedBy: signerName,
          signedAt: now,
          ipAddress,
          userAgent,
        },
      };
    } else if (action === "reject") {
      updates = {
        status: "rejected",
        rejected_at: now,
      };
    } else {
      return NextResponse.json({ error: "Invalid action" }, { status: 400 });
    }

    // Update proposal
    const { data: updatedProposal, error: updateError } = await supabase
      .from("proposals")
      .update(updates)
      .eq("id", id)
      .select()
      .single();

    if (updateError) throw updateError;

    // TODO: Send notification email to creator
    // if (action === "accept") {
    //   await sendProposalAcceptedEmail(proposal.creator.email, proposal);
    // }

    return NextResponse.json({
      success: true,
      proposal: updatedProposal,
      message: action === "accept" ? "Proposal accepted successfully" : "Proposal rejected",
    });
  } catch (error) {
    console.error("Error signing proposal:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to sign proposal" },
      { status: 500 },
    );
  }
}
