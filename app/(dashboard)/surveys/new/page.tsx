import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { createClient } from "@/lib/supabase/server";
import { Button } from "@/components/ui/button";
import { SurveyCreateForm } from "@/components/surveys/survey-create-form";

export const metadata = {
  title: "Create Survey",
  description: "Create a new client feedback survey",
};

export default async function NewSurveyPage() {
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

  const canSelectClient = isAdmin;
  const { data: clients } = canSelectClient
    ? await supabase.from("clients").select("id, company_name").is("deleted_at", null).order("company_name")
    : { data: [] };

  return (
    <div className="container mx-auto max-w-4xl py-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Create Survey</h1>
          <p className="text-muted-foreground mt-1">Build a feedback survey and publish it for responses.</p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/surveys">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Surveys
          </Link>
        </Button>
      </div>

      <SurveyCreateForm
        clients={clients || []}
        canSelectClient={canSelectClient}
        defaultClientId={dbUser?.client_id || undefined}
      />
    </div>
  );
}
