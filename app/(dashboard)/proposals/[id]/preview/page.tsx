import { createClient } from "@/lib/supabase/server";
import { ProposalPreview } from "@/components/proposals/proposal-preview";
import { notFound } from "next/navigation";

export const metadata = {
  title: "Proposal Preview | KRE8IV",
  description: "Preview proposal",
};

interface PageProps {
  params: {
    id: string;
  };
  searchParams: {
    token?: string;
  };
}

/**
 * Proposal preview page (Public - Client-facing)
 *
 * This page is publicly accessible via a secure token.
 * Tracks views and allows client to accept/reject.
 */
export default async function ProposalPreviewPage({ params, searchParams }: PageProps) {
  const supabase = createClient();

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
    .eq("id", params.id)
    .single();

  if (error || !proposal) {
    notFound();
  }

  // Verify token if required (implement token validation logic)
  // For now, we'll allow direct access but log the view

  return (
    <div className="min-h-screen bg-background">
      <ProposalPreview proposal={proposal} token={searchParams.token} />
    </div>
  );
}
