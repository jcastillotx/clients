import { createClient } from "@/lib/supabase/server";
import { InvoiceList } from "@/components/invoices/invoice-list";
import { hasAnyRole } from "@/lib/rbac/permissions";
import { redirect } from "next/navigation";

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

  const {
    data: { user },
  } = await supabase.auth.getUser();

  const canCreateInvoices = user
    ? await hasAnyRole(["super_admin", "admin", "account_manager"], {
        supabase,
        userId: user.id,
      })
    : false;

  const isPlainStaff = user
    ? await hasAnyRole(["staff"], {
        supabase,
        userId: user.id,
      })
    : false;

  if (isPlainStaff && !canCreateInvoices) {
    redirect("/dashboard");
  }

  // Authentication is handled by the dashboard layout - no redundant check needed.
  const page = parseInt(resolvedSearchParams.page || "1");
  const pageSize = 20;
  const from = (page - 1) * pageSize;
  const to = from + pageSize - 1;

  // Try query with user join first, fall back to simpler query if FK doesn't exist
  let invoices: any[] | null = null;
  let error: any = null;
  let count: number | null = null;

  const buildQuery = (select: string) => {
    let query = supabase
      .from("invoices")
      .select(select, { count: "exact" })
      .order("created_at", { ascending: false });

    if (resolvedSearchParams.search) {
      query = query.or(`invoice_number.ilike.%${resolvedSearchParams.search}%`);
    }

    if (resolvedSearchParams.status && resolvedSearchParams.status !== "all") {
      query = query.eq("status", resolvedSearchParams.status);
    }

    return query.range(from, to);
  };

  // Try with created_by_user join
  const result = await buildQuery(`
    *,
    client:clients(id, company_name),
    created_by_user:users!invoices_created_by_fkey(id, name)
  `);

  if (result.error?.code === "PGRST200") {
    // FK relationship not found - fall back to query without user join
    const fallback = await buildQuery(`
      *,
      client:clients(id, company_name)
    `);
    invoices = fallback.data;
    error = fallback.error;
    count = fallback.count;
  } else {
    invoices = result.data;
    error = result.error;
    count = result.count;
  }

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
        canCreateInvoices={canCreateInvoices}
      />
    </div>
  );
}
