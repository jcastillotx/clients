import { createClient } from "@/lib/supabase/server";
import { BrandingSettings } from "@/components/settings/branding-settings";

export const metadata = {
  title: "Branding | KRE8IV",
  description: "Manage portal branding and white-label settings",
};

const DEFAULT_PARENT_COMPANY_NAMES = ["Kre8ivTech", "Kre8iv Designs"];

type RoleDataRow = {
  role?: { name?: string } | Array<{ name?: string }>;
};

export default async function BrandingPage() {
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

  const canManageBranding = Boolean(
    userData?.is_super_admin || userRole === "admin" || userRole === "super_admin" || hasAdminRole,
  );

  if (!canManageBranding) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] gap-4 p-8">
        <div className="text-xl font-semibold">Branding</div>
        <div className="text-sm text-muted-foreground text-center max-w-md">
          You do not have permission to manage branding settings.
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
  let isPortalBrandingScope = false;

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

    if (!brandingClientId) {
      const { data: whiteLabelClient } = await supabase
        .from("white_label_configs")
        .select("client_id, updated_at")
        .not("client_id", "is", null)
        .order("updated_at", { ascending: false })
        .limit(1)
        .maybeSingle();
      brandingClientId = whiteLabelClient?.client_id ?? null;
    }

    if (brandingClientId) {
      isPortalBrandingScope = true;
    }
  }

  const { data: brandingConfig } = brandingClientId
    ? await supabase.from("white_label_configs").select("*").eq("client_id", brandingClientId).maybeSingle()
    : { data: null };

  return (
    <div className="flex flex-col gap-8 p-8 max-w-5xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Branding</h1>
        <p className="text-muted-foreground">Manage portal branding, logo, and white-label configuration</p>
      </div>

      <BrandingSettings
        clientId={brandingClientId}
        isPortalBrandingScope={isPortalBrandingScope}
        initialLogoUrl={brandingConfig?.logo_url ?? null}
        initialDomain={brandingConfig?.domain ?? null}
      />
    </div>
  );
}
