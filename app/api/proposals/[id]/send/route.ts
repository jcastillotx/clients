import { createClient } from "@/lib/supabase/server";
import { NextRequest } from "next/server";
import { createProposalAccessToken } from "@/lib/proposals/public-access";
import { dispatchNotification } from "@/lib/notifications/service";
import {
  apiError,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

/**
 * POST /api/proposals/[id]/send
 *
 * Send proposal to client via email and update status
 */
export async function POST(
  req: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  try {
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
      return apiNotFound(req, "Proposal not found");
    }

    if (proposal.status !== "draft") {
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Only draft proposals can be sent",
      });
    }

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

    const appUrl = (
      process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000"
    ).replace(/\/+$/, "");
    const accessToken = createProposalAccessToken(id);
    const proposalUrl = accessToken
      ? `${appUrl}/proposals/${id}/preview?token=${encodeURIComponent(accessToken)}`
      : `${appUrl}/proposals/${id}/preview`;

    await dispatchNotification({
      eventType: "proposal_sent",
      clientId: proposal.client_id,
      subjectType: "proposal",
      subjectId: id,
      actorUserId: user.id,
      extraEmails: proposal.client?.email ? [proposal.client.email] : [],
      data: {
        proposalTitle: proposal.title,
        proposalUrl,
      },
    });

    const payload = {
      proposal: updatedProposal,
      proposalUrl,
      message: "Proposal sent successfully",
    };

    return apiSuccess(req, updatedProposal, { extra: payload });
  } catch (error) {
    console.error("Error sending proposal:", error);
    return apiInternalError(
      req,
      error instanceof Error ? error.message : "Failed to send proposal",
    );
  }
}
