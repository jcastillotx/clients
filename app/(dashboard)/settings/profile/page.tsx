import { createClient } from "@/lib/supabase/server";
import { UserSettings } from "@/components/settings/user-settings";

export const metadata = {
  title: "Profile | KRE8IV",
  description: "Edit your profile information",
};

export default async function ProfileSettingsPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return null;

  const { data: userData } = await supabase.from("users").select("*").eq("id", user.id).single();

  return (
    <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Profile</h1>
        <p className="text-muted-foreground">Update your name, email, phone, address and avatar</p>
      </div>
      <UserSettings user={userData || user.user_metadata} />
    </div>
  );
}
