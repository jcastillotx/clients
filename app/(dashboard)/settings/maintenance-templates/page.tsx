import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { isUserAdmin } from "@/lib/rbac/check";
import { MaintenancePlanTemplatesManager } from "@/components/admin/maintenance-plan-templates-manager";

export const metadata = {
  title: "Maintenance templates | KRE8IV",
  description: "Create and manage maintenance plan templates",
};

export default async function MaintenanceTemplatesSettingsPage() {
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
      <MaintenancePlanTemplatesManager />
    </div>
  );
}
