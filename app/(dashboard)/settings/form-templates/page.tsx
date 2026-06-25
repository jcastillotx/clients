import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { isUserAdmin } from "@/lib/rbac/check";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

export const metadata = {
  title: "Form templates | KRE8IV",
  description: "Create and manage reusable form templates",
};

type FormTemplateRow = {
  id: string;
  name: string;
  description: string | null;
  fields: unknown;
  updated_at: string;
};

export default async function FormTemplatesSettingsPage() {
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

  const { data: templates } = await supabase
    .from("form_templates")
    .select("id, name, description, fields, updated_at")
    .order("updated_at", { ascending: false });

  const rows = (templates ?? []) as FormTemplateRow[];

  return (
    <div className="p-8 max-w-7xl mx-auto space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Form Templates</h1>
        <p className="text-muted-foreground mt-2">
          Reusable form templates with dynamic fields for requests, surveys, and
          intake flows.
        </p>
      </div>

      {rows.length === 0 ? (
        <Card>
          <CardHeader>
            <CardTitle>No form templates yet</CardTitle>
            <CardDescription>
              Form templates you create will appear here. This page is ready for
              your team to manage reusable field layouts.
            </CardDescription>
          </CardHeader>
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {rows.map((template) => {
            const fieldCount = Array.isArray(template.fields)
              ? template.fields.length
              : 0;

            return (
              <Card key={template.id}>
                <CardHeader>
                  <div className="flex items-start justify-between gap-3">
                    <CardTitle className="text-lg">{template.name}</CardTitle>
                    <Badge variant="secondary">{fieldCount} fields</Badge>
                  </div>
                  {template.description ? (
                    <CardDescription>{template.description}</CardDescription>
                  ) : null}
                </CardHeader>
                <CardContent>
                  <p className="text-sm text-muted-foreground">
                    Updated{" "}
                    {new Date(template.updated_at).toLocaleDateString(undefined, {
                      month: "short",
                      day: "numeric",
                      year: "numeric",
                    })}
                  </p>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}
    </div>
  );
}
