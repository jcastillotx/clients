import { createClient } from "@/lib/supabase/server";
import { FeatureManagement } from "@/components/admin/features/feature-management";
import { redirect } from "next/navigation";
import { hasPermission } from "@/lib/rbac/permissions";

export const metadata = {
  title: "Feature Management | Admin",
  description: "Manage feature flags and permissions",
};

/**
 * Admin Feature Management Page
 *
 * Allows admins to control which features are enabled for:
 * - Global (system-wide defaults)
 * - Specific clients
 * - Specific roles
 * - Individual users
 */
export default async function AdminFeaturesPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const canAccessAdmin = await hasPermission("admin.access");
  if (!canAccessAdmin) {
    redirect("/dashboard");
  }

  // Fetch all features with their categories
  const { data: features } = await supabase.from("features").select("*").order("category").order("display_name");

  // Fetch all clients for dropdown
  const { data: clients } = await supabase
    .from("clients")
    .select("id, company_name")
    .eq("status", "active")
    .order("company_name");

  // Fetch all roles
  const { data: roles } = await supabase.from("roles").select("id, name, description").order("name");

  return (
    <div className="flex flex-col gap-8 p-8 max-w-7xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Feature Management</h1>
        <p className="text-muted-foreground">Control which features are available across the system</p>
      </div>

      <FeatureManagement features={features || []} clients={clients || []} roles={roles || []} />
    </div>
  );
}
