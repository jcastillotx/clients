import { createClient } from "@/lib/supabase/server";
import { ProposalPreview } from "@/components/proposals/proposal-preview";
import { notFound } from "next/navigation";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { verifyProposalAccessToken } from "@/lib/proposals/public-access";

export const metadata = {
  title: "Proposal Preview | KRE8IV",
  description: "Preview proposal",
};

interface PageProps {
  params: Promise<{
    id: string;
  }>;
  searchParams: Promise<{
    token?: string;
  }>;
}

/**
 * Proposal preview page (Public - Client-facing)
 *
 * This page is publicly accessible via a secure token.
 * Tracks views and allows client to accept/reject.
 */
export default async function ProposalPreviewPage({
  params,
  searchParams,
}: PageProps) {
  const { id } = await params;
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();
  const accessToken = resolvedSearchParams.token;
  const hasValidPublicToken = verifyProposalAccessToken(id, accessToken);

  // Fetch proposal with relations (no RLS check for public preview)
  const { data: proposal, error } = await supabase
    .from("proposals")
    .select(
      `
      *,
      client:clients(id, company_name, email, phone, address, city, state, zip_code),
      creator:users!proposals_created_by_fkey(id, name, email),
      selections:proposal_selections(*)
    `,
    )
    .eq("id", id)
    .single();

  if (error || !proposal) {
    notFound();
  }

  if (!hasValidPublicToken) {
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      notFound();
    }

    const access = await resolveRouteAccess(supabase, user);
    if (!canAccessClient(access, proposal.client_id)) {
      notFound();
    }
  }

  return (
    <div className="min-h-screen bg-background">
      <ProposalPreview proposal={proposal} token={resolvedSearchParams.token} />
    </div>
  );
}
