import { createClient } from "@/lib/supabase/server";
import { UserSettings } from "@/components/settings/user-settings";
import { AccountSettings } from "@/components/settings/account-settings";
import { SecuritySettings } from "@/components/settings/security-settings";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

export const metadata = {
  title: "Settings | KRE8IV",
  description: "Manage your account settings",
};

const allowedTabs = new Set(["profile", "account", "security"]);

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
  const requestedTab = (resolvedSearchParams.tab || "").toLowerCase();
  const tabIsAllowed = allowedTabs.has(requestedTab);
  const canUseRequestedTab =
    requestedTab === "profile" ||
    requestedTab === "account" ||
    requestedTab === "security";
  const defaultTab = tabIsAllowed && canUseRequestedTab ? requestedTab : "profile";

  return (
    <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Settings</h1>
        <p className="text-muted-foreground">Manage your account settings and preferences</p>
      </div>

      <Tabs defaultValue={defaultTab} className="w-full">
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="profile">Profile</TabsTrigger>
          <TabsTrigger value="account">Account</TabsTrigger>
          <TabsTrigger value="security">Security</TabsTrigger>
        </TabsList>

        <TabsContent value="profile" className="mt-6">
          <UserSettings user={userData || user.user_metadata} />
        </TabsContent>

        <TabsContent value="account" className="mt-6">
          <AccountSettings user={user} />
        </TabsContent>

        <TabsContent value="security" className="mt-6">
          <SecuritySettings />
        </TabsContent>
      </Tabs>
    </div>
  );
}
