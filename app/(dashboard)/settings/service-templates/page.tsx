import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { isUserAdmin } from "@/lib/rbac/check";
import { ServiceTemplatesManager } from "@/components/admin/service-templates-manager";

export const metadata = {
  title: "Service templates | KRE8IV",
  description: "Manage proposal service templates",
};

export default async function ServiceTemplatesSettingsPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const admin = await isUserAdmin(user.id);
  if (!admin) {
    redirect("/dashboard");
  }

  return (
    <div className="p-8 max-w-7xl mx-auto">
      <ServiceTemplatesManager />
    </div>
  );
}
