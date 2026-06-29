import { AlertCircle } from "lucide-react";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { ProjectTaskTemplateManager } from "@/components/projects/templates/project-task-template-manager";
import { hasPermission } from "@/lib/rbac/permissions";

export const metadata = {
  title: "Project Task Lists | Admin | KRE8IV",
  description: "Create and manage reusable project task list templates",
};

export default async function AdminProjectTaskTemplatesPage() {
  const canAccessAdmin = await hasPermission("admin.access");

  if (!canAccessAdmin) {
    return (
      <div className="container mx-auto px-4 py-8">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>You do not have permission to manage project task lists.</AlertDescription>
        </Alert>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <ProjectTaskTemplateManager />
    </div>
  );
}
