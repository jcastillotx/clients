import { createClient } from "@/lib/supabase/server";
import { SecuritySettings } from "@/components/settings/security-settings";

export const metadata = {
  title: "Security | KRE8IV",
  description: "Manage your password and two-factor authentication",
};

export default async function SecuritySettingsPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return null;

  return (
    <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Security</h1>
        <p className="text-muted-foreground">Manage your password and two-factor authentication</p>
      </div>
      <SecuritySettings user={{ id: user.id, email: user.email }} />
    </div>
  );
}
