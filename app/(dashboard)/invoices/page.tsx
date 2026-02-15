import { createClient } from "@/lib/supabase/server";
import { InvoiceList } from "@/components/invoices/invoice-list";

export const metadata = {
  title: "Invoices | KRE8IV",
  description: "Manage your invoices",
};

interface SearchParams {
  search?: string;
  status?: string;
  page?: string;
}

/**
 * Invoices list page (Server Component)
 *
 * Fetches invoices on the server with RLS automatically filtering by user's access.
 */
export default async function InvoicesPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();

  // Authentication is handled by the dashboard layout - no redundant check needed.
  // Build query with filters
  let query = supabase
    .from("invoices")
    .select(
      `
      *,
      client:clients(id, company_name),
      created_by_user:users!invoices_created_by_fkey(id, name)
    `,
      { count: "exact" },
    )
    .order("created_at", { ascending: false });

  // Apply search filter (invoice number or client name)
  if (resolvedSearchParams.search) {
    query = query.or(`invoice_number.ilike.%${resolvedSearchParams.search}%`);
  }

  // Apply status filter
  if (resolvedSearchParams.status && resolvedSearchParams.status !== "all") {
    query = query.eq("status", resolvedSearchParams.status);
  }

  // Pagination
  const page = parseInt(resolvedSearchParams.page || "1");
  const pageSize = 20;
  const from = (page - 1) * pageSize;
  const to = from + pageSize - 1;

  query = query.range(from, to);

  const { data: invoices, error, count } = await query;

  if (error) {
    console.error("Error fetching invoices:", error);
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] gap-4">
        <div className="text-destructive font-semibold">Error loading invoices</div>
        <div className="text-sm text-muted-foreground max-w-md text-center">
          {error.message || "An unexpected error occurred"}
        </div>
        <pre className="text-xs bg-muted p-4 rounded max-w-2xl overflow-auto">
          {JSON.stringify(error, null, 2)}
        </pre>
      </div>
    );
  }

  // Calculate revenue stats
  const { data: allInvoices } = await supabase.from("invoices").select("amount, status");

  const stats = {
    totalRevenue: allInvoices?.reduce((sum, inv) => sum + inv.amount, 0) || 0,
    paidRevenue: allInvoices?.filter((inv) => inv.status === "paid").reduce((sum, inv) => sum + inv.amount, 0) || 0,
    pendingRevenue: allInvoices?.filter((inv) => inv.status === "sent").reduce((sum, inv) => sum + inv.amount, 0) || 0,
  };

  return (
    <div className="flex flex-col gap-8 p-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Invoices</h1>
          <p className="text-muted-foreground">Manage your billing and invoices</p>
        </div>
      </div>

      <InvoiceList
        initialData={invoices || []}
        totalCount={count || 0}
        currentPage={page}
        pageSize={pageSize}
        stats={stats}
      />
    </div>
  );
}
