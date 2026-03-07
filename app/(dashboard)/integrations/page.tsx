import { createClient } from "@/lib/supabase/server";
import { IntegrationSettings } from "@/components/settings/integration-settings";

export const metadata = {
  title: "Integrations | KRE8IV",
  description: "Manage connected integrations and API credentials",
};

const DEFAULT_PARENT_COMPANY_NAMES = ["Kre8ivTech", "Kre8iv Designs"];

type RoleDataRow = {
  role?: { name?: string } | Array<{ name?: string }>;
};

export default async function IntegrationsPage() {
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

  const hasAdminRole = (roleData || []).some((row: RoleDataRow) => {
    const roleValue = row?.role;
    const roleName = Array.isArray(roleValue) ? roleValue[0]?.name : roleValue?.name;
    return roleName === "admin" || roleName === "super_admin";
  });

  const canManageIntegrations = Boolean(
    userData?.is_super_admin || userRole === "admin" || userRole === "super_admin" || hasAdminRole,
  );

  if (!canManageIntegrations) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] gap-4 p-8">
        <div className="text-xl font-semibold">Integrations</div>
        <div className="text-sm text-muted-foreground text-center max-w-md">
          You do not have permission to manage workspace integrations.
        </div>
      </div>
    );
  }

  const parentClientIds = (process.env.PARENT_CLIENT_IDS ?? "")
    .split(",")
    .map((value) => value.trim())
    .filter(Boolean);
  const parentCompanyNames = (process.env.PARENT_COMPANY_NAMES ?? DEFAULT_PARENT_COMPANY_NAMES.join(","))
    .split(",")
    .map((value) => value.trim())
    .filter(Boolean);

  let brandingClientId: string | null = clientId;

  if (!brandingClientId) {
    if (parentClientIds.length > 0) {
      const { data: parentClientById } = await supabase
        .from("clients")
        .select("id")
        .in("id", parentClientIds)
        .limit(1)
        .maybeSingle();
      brandingClientId = parentClientById?.id ?? null;
    }

    if (!brandingClientId && parentCompanyNames.length > 0) {
      const { data: parentClientByName } = await supabase
        .from("clients")
        .select("id")
        .in("company_name", parentCompanyNames)
        .limit(1)
        .maybeSingle();
      brandingClientId = parentClientByName?.id ?? null;
    }

    if (!brandingClientId && canManageIntegrations) {
      const { data: whiteLabelClient } = await supabase
        .from("white_label_configs")
        .select("client_id, updated_at")
        .not("client_id", "is", null)
        .order("updated_at", { ascending: false })
        .limit(1)
        .maybeSingle();

      brandingClientId = whiteLabelClient?.client_id ?? null;
    }

    if (!brandingClientId && canManageIntegrations) {
      const { data: anyClient } = await supabase
        .from("clients")
        .select("id")
        .order("created_at", { ascending: true })
        .limit(1)
        .maybeSingle();

      brandingClientId = anyClient?.id ?? null;
    }
  }

  const settingsScopeClientId = clientId ?? brandingClientId;

  if (!settingsScopeClientId) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] gap-4 p-8">
        <div className="text-xl font-semibold">Integrations</div>
        <div className="text-sm text-muted-foreground text-center max-w-md">
          No client scope is available for integration management.
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-8 p-8 max-w-6xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Integrations</h1>
        <p className="text-muted-foreground">Manage billing, AI, automation, analytics, branding, and email integrations</p>
      </div>

      <IntegrationSettings clientId={settingsScopeClientId} />
    </div>
  );
}
