import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { UserSettings } from "@/components/settings/user-settings";
import { AccountSettings } from "@/components/settings/account-settings";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

export const metadata = {
  title: "Settings | KRE8IV",
  description: "Manage your account settings",
};

/**
 * Settings page (Server Component)
 *
 * Provides user profile and account settings.
 */
export default async function SettingsPage() {
  const supabase = createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Fetch full user data
  const { data: userData } = await supabase.from("users").select("*").eq("id", user.id).single();

  return (
    <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Settings</h1>
        <p className="text-muted-foreground">Manage your account settings and preferences</p>
      </div>

      <Tabs defaultValue="profile" className="w-full">
        <TabsList className="grid w-full grid-cols-2">
          <TabsTrigger value="profile">Profile</TabsTrigger>
          <TabsTrigger value="account">Account</TabsTrigger>
        </TabsList>

        <TabsContent value="profile" className="mt-6">
          <UserSettings user={userData || user.user_metadata} />
        </TabsContent>

        <TabsContent value="account" className="mt-6">
          <AccountSettings user={user} />
        </TabsContent>
      </Tabs>
    </div>
  );
}
