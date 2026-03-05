import { createClient } from "@/lib/supabase/server";
import { UserSettings } from "@/components/settings/user-settings";
import { AccountSettings } from "@/components/settings/account-settings";
import { BrandingSettings } from "@/components/settings/branding-settings";
import { StorageConnections } from "@/components/features/storage-connections";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

export const metadata = {
  title: "Settings | KRE8IV",
  description: "Manage your account settings",
};

const allowedTabs = new Set(["profile", "account", "branding", "storage"]);
const DEFAULT_PARENT_COMPANY_NAMES = ["Kre8ivTech", "Kre8iv Designs"];

/**
 * Settings page (Server Component)
 *
 * Provides user profile and account settings.
 */
export default async function SettingsPage({
  searchParams,
}: {
  searchParams: Promise<{ tab?: string }>;
}) {
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();

  // Authentication is handled by the dashboard layout - no redundant redirect needed.
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return null; // Type narrowing only; layout already guards auth

  // Fetch full user data
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

  const canManageBranding = Boolean(
    userData?.is_super_admin || userRole === "admin" || userRole === "super_admin" || hasAdminRole,
  );

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

  if (!brandingClientId && canManageBranding) {
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

    if (brandingClientId) {
      isPortalBrandingScope = true;
    }
  }

  const { data: brandingConfig } = brandingClientId
    ? await supabase.from("white_label_configs").select("*").eq("client_id", brandingClientId).maybeSingle()
    : { data: null };

  const requestedTab = (resolvedSearchParams.tab || "").toLowerCase();
  const tabIsAllowed = allowedTabs.has(requestedTab);
  const canUseRequestedTab =
    requestedTab === "profile" ||
    requestedTab === "account" ||
    (canManageBranding && (requestedTab === "branding" || requestedTab === "storage"));
  const defaultTab = tabIsAllowed && canUseRequestedTab ? requestedTab : "profile";

  return (
    <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Settings</h1>
        <p className="text-muted-foreground">Manage your account settings and preferences</p>
      </div>

      <Tabs defaultValue={defaultTab} className="w-full">
        <TabsList className={`grid w-full ${canManageBranding ? "grid-cols-4" : "grid-cols-2"}`}>
          <TabsTrigger value="profile">Profile</TabsTrigger>
          <TabsTrigger value="account">Account</TabsTrigger>
          {canManageBranding ? <TabsTrigger value="branding">Branding</TabsTrigger> : null}
          {canManageBranding ? <TabsTrigger value="storage">Storage</TabsTrigger> : null}
        </TabsList>

        <TabsContent value="profile" className="mt-6">
          <UserSettings user={userData || user.user_metadata} />
        </TabsContent>

        <TabsContent value="account" className="mt-6">
          <AccountSettings user={user} />
        </TabsContent>

        {canManageBranding ? (
          <TabsContent value="branding" className="mt-6">
            <BrandingSettings
              clientId={brandingClientId}
              isPortalBrandingScope={isPortalBrandingScope}
              initialLogoUrl={brandingConfig?.logo_url ?? null}
              initialDomain={brandingConfig?.domain ?? null}
            />
          </TabsContent>
        ) : null}

        {canManageBranding && clientId ? (
          <TabsContent value="storage" className="mt-6">
            <StorageConnections clientId={clientId} />
          </TabsContent>
        ) : null}
      </Tabs>
    </div>
  );
}
