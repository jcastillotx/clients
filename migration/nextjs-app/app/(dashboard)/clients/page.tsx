import { createClient } from "@/lib/supabase/server";
import { ClientList } from "@/components/clients/client-list";

export const metadata = {
  title: "Clients | KRE8IV",
  description: "Manage your clients",
};

interface SearchParams {
  search?: string;
  status?: string;
  page?: string;
}

/**
 * Clients list page (Server Component)
 *
 * Fetches clients on the server with RLS automatically filtering by user's access.
 * For super admins, returns all clients. For staff, returns assigned clients only.
 */
export default async function ClientsPage({ searchParams }: { searchParams: SearchParams }) {
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return null; // Middleware will redirect
  }

  // Build query with filters
  let query = supabase
    .from("clients")
    .select(
      `
      *,
      primary_contact:users!clients_primary_contact_id_fkey(id, name, email, phone),
      _count:requests(count)
    `,
      { count: "exact" },
    )
    .order("created_at", { ascending: false });

  // Apply search filter
  if (searchParams.search) {
    query = query.or(
      `company_name.ilike.%${searchParams.search}%,domain.ilike.%${searchParams.search}%,industry.ilike.%${searchParams.search}%`,
    );
  }

  // Apply status filter
  if (searchParams.status && searchParams.status !== "all") {
    query = query.eq("status", searchParams.status);
  }

  // Pagination
  const page = parseInt(searchParams.page || "1");
  const pageSize = 20;
  const from = (page - 1) * pageSize;
  const to = from + pageSize - 1;

  query = query.range(from, to);

  const { data: clients, error, count } = await query;

  if (error) {
    console.error("Error fetching clients:", error);
    return <div>Error loading clients</div>;
  }

  return (
    <div className="flex flex-col gap-8 p-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Clients</h1>
          <p className="text-muted-foreground">Manage your client accounts</p>
        </div>
      </div>

      <ClientList initialData={clients || []} totalCount={count || 0} currentPage={page} pageSize={pageSize} />
    </div>
  );
}
