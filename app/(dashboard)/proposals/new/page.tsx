import { createClient } from "@/lib/supabase/server";
import { ProposalWizard } from "@/components/proposals/proposal-wizard";
import { redirect } from "next/navigation";

export const metadata = {
  title: "New Proposal | KRE8IV",
  description: "Create a new proposal",
};

interface SearchParams {
  client_id?: string;
}

/**
 * New proposal page (Server Component)
 *
 * Fetches clients for the wizard and pre-selects if client_id provided.
 */
export default async function NewProposalPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Fetch clients for dropdown
  const { data: clients } = await supabase
    .from("clients")
    .select("id, company_name, email")
    .eq("status", "active")
    .order("company_name");

  return (
    <div className="flex flex-col gap-8 p-8 max-w-7xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">New Proposal</h1>
        <p className="text-muted-foreground">Create a new business proposal</p>
      </div>

      <ProposalWizard clients={clients || []} preselectedClientId={resolvedSearchParams.client_id} />
    </div>
  );
}
