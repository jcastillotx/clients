import { createClient } from "@/lib/supabase/server";
import { StorageConnections } from "@/components/features/storage-connections";

export const metadata = {
  title: "Storage Management | KRE8IV",
  description: "Manage platform and client cloud storage connections",
};

const DEFAULT_PARENT_COMPANY_NAMES = ["Kre8ivTech", "Kre8iv Designs"];

export default async function StoragePage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return null;

  const { data: userData } = await supabase.from("users").select("*").eq("id", user.id).single();
  const clientId = userData?.client_id ?? null;

  const userRole = String(user.user_metadata?.role || user.user_metadata?.app_role || "").toLowerCase();
  const { data: roleData } = await supabase
    .from("user_roles")
    .select("role:roles(name)")
    .eq("user_id", user.id);

  type RoleDataRow = {
    role?: { name?: string } | Array<{ name?: string }>;
  };

  const hasAdminRole = (roleData || []).some((row: RoleDataRow) => {
    const roleValue = row?.role;
    const roleName = Array.isArray(roleValue) ? roleValue[0]?.name : roleValue?.name;
    return roleName === "admin" || roleName === "super_admin";
  });

  const isAdmin = Boolean(
    userData?.is_super_admin || userRole === "admin" || userRole === "super_admin" || hasAdminRole,
  );

  // Resolve the backing client record used for platform-level connections.
  let companyClientId: string | null = clientId;

  if (!companyClientId) {
    const parentClientIds = (process.env.PARENT_CLIENT_IDS ?? "")
      .split(",")
      .map((v) => v.trim())
      .filter(Boolean);
    const parentCompanyNames = (process.env.PARENT_COMPANY_NAMES ?? DEFAULT_PARENT_COMPANY_NAMES.join(","))
      .split(",")
      .map((v) => v.trim())
      .filter(Boolean);

    if (parentClientIds.length > 0) {
      const { data: parentById } = await supabase
        .from("clients")
        .select("id")
        .in("id", parentClientIds)
        .limit(1)
        .maybeSingle();
      companyClientId = parentById?.id ?? null;
    }

    if (!companyClientId && parentCompanyNames.length > 0) {
      const { data: parentByName } = await supabase
        .from("clients")
        .select("id")
        .in("company_name", parentCompanyNames)
        .limit(1)
        .maybeSingle();
      companyClientId = parentByName?.id ?? null;
    }

    if (!companyClientId && isAdmin) {
      const { data: whiteLabelClient } = await supabase
        .from("white_label_configs")
        .select("client_id, updated_at")
        .not("client_id", "is", null)
        .order("updated_at", { ascending: false })
        .limit(1)
        .maybeSingle();

      companyClientId = whiteLabelClient?.client_id ?? null;
    }

    if (!companyClientId && isAdmin) {
      const { data: anyClient } = await supabase
        .from("clients")
        .select("id")
        .order("created_at", { ascending: true })
        .limit(1)
        .maybeSingle();

      companyClientId = anyClient?.id ?? null;
    }
  }

  const resolvedClientId = clientId || companyClientId;

  if (!resolvedClientId) {
    return (
      <div className="flex flex-col gap-8 p-8 max-w-5xl mx-auto">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Storage Management</h1>
          <p className="text-muted-foreground">No company account found. Please contact your administrator.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-8 p-8 max-w-5xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Storage Management</h1>
        <p className="text-muted-foreground">
          Manage platform storage and client-owned storage access
        </p>
      </div>

      <StorageConnections
        clientId={resolvedClientId}
        isAdmin={isAdmin}
        companyClientId={companyClientId}
      />
    </div>
  );
}
