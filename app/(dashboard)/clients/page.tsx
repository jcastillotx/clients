import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
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

interface QueryErrorShape {
  code?: string;
  message?: string | null;
  details?: string | null;
}

function isMissingPrimaryContactRelation(error: QueryErrorShape | null) {
  if (!error) return false;
  if (error.code !== "PGRST200") return false;
  const detailText = `${error.message ?? ""} ${error.details ?? ""}`.toLowerCase();
  return detailText.includes("clients_primary_contact_id_fkey");
}

function isMissingLegacyClientColumn(error: QueryErrorShape | null) {
  if (!error) return false;
  const detailText = `${error.message ?? ""} ${error.details ?? ""}`.toLowerCase();
  return (
    (error.code === "42703" || detailText.includes("does not exist")) &&
    (detailText.includes("domain") || detailText.includes("industry"))
  );
}

/**
 * Clients list page (Server Component)
 *
 * Fetches clients on the server with RLS automatically filtering by user's access.
 * For super admins, returns all clients. For staff, returns assigned clients only.
 */
export default async function ClientsPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();

  // Authentication is handled by the dashboard layout - no redundant redirect needed.
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return null; // Type narrowing only; layout already guards auth

  const metadataRole = String(user.user_metadata?.role ?? user.user_metadata?.app_role ?? "").toLowerCase();
  const [userRowRes, userRolesRes] = await Promise.all([
    supabase.from("users").select("id, is_super_admin").eq("id", user.id).maybeSingle(),
    supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
  ]);

  const userRow = userRowRes.data;
  const roleNames = new Set(
    (userRolesRes.data ?? []).flatMap((entry: any) => {
      const roleValue = Array.isArray(entry.role) ? entry.role[0]?.name : entry.role?.name;
      return typeof roleValue === "string" ? [roleValue.toLowerCase()] : [];
    }),
  );

  const isAdminUser =
    Boolean(userRow?.is_super_admin) ||
    user.user_metadata?.is_super_admin === true ||
    metadataRole === "admin" ||
    metadataRole === "super_admin" ||
    metadataRole === "account_manager" ||
    roleNames.has("admin") ||
    roleNames.has("super_admin") ||
    roleNames.has("account_manager");

  const adminClient = isAdminUser ? createAdminClientIfAvailable() : null;
  const dbClient = adminClient ?? supabase;

  if (isAdminUser && !adminClient) {
    console.warn("Service-role Supabase key missing; falling back to session client for /clients");
  }

  const excludedClientIds = (process.env.PARENT_CLIENT_IDS ?? "")
    .split(",")
    .map((value) => value.trim())
    .filter(Boolean);
  const excludedCompanyNames = (process.env.PARENT_COMPANY_NAMES ?? "Kre8ivTech,Kre8iv Designs")
    .split(",")
    .map((value) => value.trim().toLowerCase())
    .filter(Boolean);

  // Pagination
  const page = parseInt(resolvedSearchParams.page || "1");
  const pageSize = 20;
  const from = (page - 1) * pageSize;
  const to = from + pageSize - 1;
  const searchValue = resolvedSearchParams.search?.trim();

  const runClientsQuery = async ({
    includePrimaryContact,
    useLegacySearchColumns,
  }: {
    includePrimaryContact: boolean;
    useLegacySearchColumns: boolean;
  }) => {
    let query = dbClient
      .from("clients")
      .select(
        includePrimaryContact
          ? `
            *,
            primary_contact:users!clients_primary_contact_id_fkey(id, name, email, phone),
            _count:requests(count)
          `
          : `
            *,
            _count:requests(count)
          `,
        { count: "exact" },
      )
      .order("created_at", { ascending: false });

    if (excludedClientIds.length > 0) {
      const formattedIds = excludedClientIds.map((id) => `"${id}"`).join(",");
      query = query.not("id", "in", `(${formattedIds})`);
    }

    if (excludedCompanyNames.length > 0) {
      const formattedNames = excludedCompanyNames.map((name) => `"${name}"`).join(",");
      query = query.not("company_name", "in", `(${formattedNames})`);
    }

    if (searchValue) {
      const searchFilter = useLegacySearchColumns
        ? `company_name.ilike.%${searchValue}%,domain.ilike.%${searchValue}%,industry.ilike.%${searchValue}%`
        : `company_name.ilike.%${searchValue}%,email.ilike.%${searchValue}%,website.ilike.%${searchValue}%`;
      query = query.or(searchFilter);
    }

    if (resolvedSearchParams.status && resolvedSearchParams.status !== "all") {
      query = query.eq("status", resolvedSearchParams.status);
    }

    return query.range(from, to);
  };

  let result = await runClientsQuery({ includePrimaryContact: true, useLegacySearchColumns: true });

  if (isMissingPrimaryContactRelation(result.error)) {
    console.warn("Missing clients_primary_contact_id_fkey relation; retrying /clients query without primary contact join");
    result = await runClientsQuery({ includePrimaryContact: false, useLegacySearchColumns: true });
  }

  if (isMissingLegacyClientColumn(result.error)) {
    console.warn("Missing legacy client columns (domain/industry); retrying /clients query with core searchable fields");
    result = await runClientsQuery({ includePrimaryContact: false, useLegacySearchColumns: false });
  }

  const { data: clients, error, count } = result;

  if (error) {
    console.error("Error fetching clients:", error);
    return <div>Error loading clients</div>;
  }

  const filteredClients = (clients ?? []).filter((client) => {
    const companyName = String(client.company_name ?? "").trim().toLowerCase();
    return !excludedCompanyNames.includes(companyName);
  });

  return (
    <div className="flex flex-col gap-8 p-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Clients</h1>
          <p className="text-muted-foreground">Manage your client accounts</p>
        </div>
      </div>

      <ClientList initialData={filteredClients} totalCount={count || 0} currentPage={page} pageSize={pageSize} />
    </div>
  );
}
