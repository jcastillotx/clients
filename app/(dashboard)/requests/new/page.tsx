import { createClient } from "@/lib/supabase/server";
import { RequestForm } from "@/components/requests/request-form";
import { redirect } from "next/navigation";

export const metadata = {
  title: "New Request | KRE8IV",
  description: "Create a new service request",
};

interface SearchParams {
  client_id?: string;
}

/**
 * New request page (Server Component)
 *
 * Fetches clients for the dropdown and pre-selects if client_id provided.
 */
export default async function NewRequestPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
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
    .select("id, company_name")
    .eq("status", "active")
    .order("company_name");

  return (
    <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">New Request</h1>
        <p className="text-muted-foreground">Create a new service request</p>
      </div>

      <RequestForm clients={clients || []} preselectedClientId={resolvedSearchParams.client_id} />
    </div>
  );
}
