import { createClient } from "@/lib/supabase/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { redirect } from "next/navigation";
import { ContractList } from "@/components/contracts/contract-list";

export default async function ContractsPage({
  searchParams,
}: {
  searchParams: Promise<{ clientId?: string; status?: string }>;
}) {
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();

  // Get authenticated user
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Check permission (fallback to true if RBAC not set up)
  const canView = await hasPermission("contracts.read").catch(() => true);
  if (!canView) {
    redirect("/dashboard");
  }

  // Fetch initial contracts
  let query = supabase
    .from("contracts")
    .select(
      `
      *,
      client:clients(id, company_name),
      document:documents(id, name, file_name, storage_path)
    `,
    )
    .is("deleted_at", null)
    .order("created_at", { ascending: false });

  if (resolvedSearchParams.clientId) {
    query = query.eq("client_id", resolvedSearchParams.clientId);
  }

  if (resolvedSearchParams.status) {
    query = query.eq("status", resolvedSearchParams.status);
  }

  const { data: contracts } = await query;

  // Fetch clients for filter dropdown
  const { data: clients } = await supabase.from("clients").select("id, company_name").order("company_name");

  const canCreate = await hasPermission("contracts.create");

  return (
    <div className="container mx-auto py-6">
      <ContractList
        initialContracts={contracts || []}
        clients={clients || []}
        canCreate={canCreate}
        initialClientId={resolvedSearchParams.clientId}
        initialStatus={resolvedSearchParams.status}
      />
    </div>
  );
}
