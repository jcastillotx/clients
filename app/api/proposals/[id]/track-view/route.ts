import { createClient } from "@/lib/supabase/server";
import { NextRequest } from "next/server";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { verifyProposalAccessToken } from "@/lib/proposals/public-access";
import {
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
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
      return apiValidationError(req, parsedBody.error);
    }

    const ipAddress =
      req.headers.get("x-forwarded-for") ||
      req.headers.get("x-real-ip") ||
      "unknown";

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
      parsedBody.data.token,
    );
    if (!allowed) {
      return apiUnauthorized(req);
    }

    const { error: viewError } = await supabase.from("proposal_views").insert({
      proposal_id: id,
      viewed_by_ip: ipAddress,
      viewed_at: new Date().toISOString(),
    });

    if (viewError) {
      console.error("Error recording view:", viewError);
    }

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

    return apiSuccess(req, { tracked: true });
  } catch (error) {
    console.error("Error tracking view:", error);
    return apiSuccess(req, { tracked: false });
  }
}
