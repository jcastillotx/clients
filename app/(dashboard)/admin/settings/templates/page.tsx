import { createClient } from "@/lib/supabase/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { redirect } from "next/navigation";
import { TemplateEditor } from "@/components/admin/templates/template-editor";

export default async function TemplateSettingsPage() {
  const canManage = await hasPermission("settings.manage");

  if (!canManage) {
    redirect("/dashboard");
  }

  const supabase = await createClient();

  // Fetch templates
  const [{ data: invoiceTemplates }, { data: emailTemplates }] = await Promise.all([
    supabase.from("invoice_templates").select("*").is("deleted_at", null).order("created_at", { ascending: false }),
    supabase.from("email_templates").select("*").is("deleted_at", null).order("created_at", { ascending: false }),
  ]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Template Settings</h1>
        <p className="text-muted-foreground mt-2">
          Manage invoice and email templates with custom branding and content
        </p>
      </div>

      <TemplateEditor invoiceTemplates={invoiceTemplates || []} emailTemplates={emailTemplates || []} />
    </div>
  );
}
