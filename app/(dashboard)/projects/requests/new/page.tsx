import { createClient } from "@/lib/supabase/server";
import { ProjectRequestForm } from "@/components/projects/project-request-form";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import { ArrowLeft } from "lucide-react";

export const metadata = {
  title: "New Project Request",
  description: "Submit a project request with files for internal review and estimation",
};

export default async function NewProjectRequestPage() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  const [{ data: dbUser }, { data: roleRows }] = user
    ? await Promise.all([
        supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle(),
        supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
      ])
    : [{ data: null }, { data: [] }];

  const metadataRole = String(user?.user_metadata?.role || user?.user_metadata?.app_role || "").toLowerCase();
  const roleNames = (roleRows || []).map((row: unknown) => {
    const roleRow = row as { role?: { name?: string } | Array<{ name?: string }> };
    if (Array.isArray(roleRow.role)) {
      return String(roleRow.role[0]?.name || "").toLowerCase();
    }
    return String(roleRow.role?.name || "").toLowerCase();
  });

  const isAdmin = Boolean(
    dbUser?.is_super_admin ||
      user?.user_metadata?.is_super_admin === true ||
      metadataRole === "admin" ||
      metadataRole === "super_admin" ||
      roleNames.includes("admin") ||
      roleNames.includes("super_admin"),
  );

  const isStaff = Boolean(
    isAdmin ||
      metadataRole === "staff" ||
      metadataRole === "account_manager" ||
      roleNames.includes("staff") ||
      roleNames.includes("account_manager"),
  );

  const canSelectClient = isStaff;

  const { data: clients } = canSelectClient
    ? await supabase.from("clients").select("id, company_name").is("deleted_at", null).order("company_name")
    : { data: [] };

  return (
    <div className="container mx-auto max-w-4xl space-y-6 py-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Request a New Project</h1>
          <p className="mt-1 text-muted-foreground">
            Share your executive summary, files, and due dates. We will review and send an estimate.
          </p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/projects/requests">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Requests
          </Link>
        </Button>
      </div>

      <ProjectRequestForm
        clients={clients || []}
        canSelectClient={canSelectClient}
        defaultClientId={dbUser?.client_id || undefined}
      />
    </div>
  );
}
