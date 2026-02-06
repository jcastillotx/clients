import { createClient } from "@/lib/supabase/server";
import { ProposalDetail } from "@/components/proposals/proposal-detail";
import { notFound, redirect } from "next/navigation";

export const metadata = {
  title: "Proposal Details | KRE8IV",
  description: "View proposal details",
};

interface PageProps {
  params: Promise<{
    id: string;
  }>;
}

/**
 * Proposal detail page (Server Component)
 *
 * Fetches proposal details and displays edit/view mode.
 */
export default async function ProposalDetailPage({ params }: PageProps) {
  const { id } = await params;
  const supabase = createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Fetch proposal with relations
  const { data: proposal, error } = await supabase
    .from("proposals")
    .select(
      `
      *,
      client:clients(id, company_name, email, phone, address, city, state, zip_code),
      creator:users!proposals_created_by_fkey(id, name, email),
      selections:proposal_selections(*),
      views:proposal_views(*)
    `,
    )
    .eq("id", id)
    .single();

  if (error || !proposal) {
    notFound();
  }

  // Fetch clients for editing
  const { data: clients } = await supabase
    .from("clients")
    .select("id, company_name, email")
    .eq("status", "active")
    .order("company_name");

  return (
    <div className="flex flex-col gap-8 p-8 max-w-7xl mx-auto">
      <ProposalDetail proposal={proposal} clients={clients || []} />
    </div>
  );
}
