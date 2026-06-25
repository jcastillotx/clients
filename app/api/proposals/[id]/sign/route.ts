import { createClient } from "@/lib/supabase/server";
import { NextRequest } from "next/server";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { dispatchNotification } from "@/lib/notifications/service";
import { withPlatformNotificationEmails } from "@/lib/notifications/platform-email";
import { verifyProposalAccessToken } from "@/lib/proposals/public-access";
import {
  apiError,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { z } from "zod";

const signSchema = z.object({
  action: z.enum(["accept", "reject"]),
  signatureData: z.string().optional(),
  signerName: z.string().optional(),
  signerEmail: z.string().email().optional(),
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
 * POST /api/proposals/[id]/sign
 *
 * Accept or reject a proposal with signature
 */
export async function POST(
  req: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  const supabase = await createClient();

  try {
    const body = await req.json();
    const parsed = signSchema.safeParse(body);
    if (!parsed.success) {
      return apiValidationError(req, parsed.error);
    }

    const { action, signatureData, signerName, signerEmail, token } = parsed.data;

    const { data: proposal, error: fetchError } = await supabase
      .from("proposals")
      .select("*")
      .eq("id", id)
      .single();

    if (fetchError) throw fetchError;
    if (!proposal) {
      return apiNotFound(req, "Proposal not found");
    }

    const allowed = await canAccessProposal(
      supabase,
      id,
      proposal.client_id,
      token,
    );
    if (!allowed) {
      return apiUnauthorized(req);
    }

    if (!["sent", "viewed"].includes(proposal.status)) {
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "This proposal cannot be signed",
      });
    }

    if (proposal.valid_until && new Date(proposal.valid_until) < new Date()) {
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "This proposal has expired",
      });
    }

    const now = new Date().toISOString();
    const ipAddress =
      req.headers.get("x-forwarded-for") ||
      req.headers.get("x-real-ip") ||
      "unknown";
    const userAgent = req.headers.get("user-agent") || undefined;

    let updates: Record<string, unknown> = {};

    if (action === "accept") {
      if (!signatureData || !signerName || !signerEmail) {
        return apiError(req, {
          status: 400,
          code: "BAD_REQUEST",
          message: "Signature data, name, and email are required",
        });
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
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Invalid action",
      });
    }

    const { data: updatedProposal, error: updateError } = await supabase
      .from("proposals")
      .update(updates)
      .eq("id", id)
      .select()
      .single();

    if (updateError) throw updateError;

    const {
      data: { user: actorUser },
    } = await supabase.auth.getUser();

    try {
      await dispatchNotification({
        eventType:
          action === "accept" ? "proposal_accepted" : "proposal_rejected",
        clientId: proposal.client_id,
        subjectType: "proposal",
        subjectId: proposal.id,
        actorUserId: actorUser?.id,
        recipientUserIds: proposal.created_by ? [proposal.created_by] : undefined,
        extraEmails: await withPlatformNotificationEmails(signerEmail ? [signerEmail] : []),
        data: {
          proposalTitle: proposal.title,
          signerName,
        },
      });
    } catch (notifyErr) {
      console.error("[POST /api/proposals/:id/sign] notification dispatch:", notifyErr);
    }

    const message =
      action === "accept"
        ? "Proposal accepted successfully"
        : "Proposal rejected";

    return apiSuccess(req, updatedProposal, {
      extra: { proposal: updatedProposal, message },
    });
  } catch (error) {
    console.error("Error signing proposal:", error);
    return apiInternalError(
      req,
      error instanceof Error ? error.message : "Failed to sign proposal",
    );
  }
}
