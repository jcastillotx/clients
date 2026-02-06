import { createClient } from "@/lib/supabase/server";
import { InvoiceForm } from "@/components/invoices/invoice-form";
import { redirect } from "next/navigation";

export const metadata = {
  title: "New Invoice | KRE8IV",
  description: "Create a new invoice",
};

interface SearchParams {
  client_id?: string;
}

/**
 * New invoice page (Server Component)
 *
 * Fetches clients for the dropdown and pre-selects if client_id provided.
 */
export default async function NewInvoicePage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const resolvedSearchParams = await searchParams;
  const supabase = createClient();

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
    <div className="flex flex-col gap-8 p-8 max-w-6xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">New Invoice</h1>
        <p className="text-muted-foreground">Create a new invoice</p>
      </div>

      <InvoiceForm clients={clients || []} preselectedClientId={resolvedSearchParams.client_id} />
    </div>
  );
}
