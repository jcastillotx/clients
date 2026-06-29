"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { FileText, Download, Edit, Trash2 } from "lucide-react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from "sonner";

interface ReportTemplate {
  id: string;
  name: string;
  report_type: string;
  sections: Array<{ id: string; title: string; type: string; config: Record<string, unknown>; order: number }> | null;
  created_by_user: { id: string; name: string } | null;
  created_at: string;
  updated_at: string;
}

interface ReportsListProps {
  templates: ReportTemplate[];
}

const reportTypeColors: Record<string, string> = {
  financial: "bg-green-500",
  seo: "bg-blue-500",
  project: "bg-purple-500",
  marketing: "bg-orange-500",
};

export function ReportsList({ templates: initialTemplates }: ReportsListProps) {
  const router = useRouter();
  const [templates, setTemplates] = useState(initialTemplates);
  const [deleting, setDeleting] = useState<string | null>(null);

  const handleDelete = async (id: string) => {
    if (!confirm("Delete this report template?")) return;
    setDeleting(id);
    try {
      const res = await fetch(`/api/reports/templates/${id}`, { method: "DELETE" });
      if (!res.ok) throw new Error("Failed to delete template");
      setTemplates((prev) => prev.filter((t) => t.id !== id));
      toast.success("Template deleted");
    } catch {
      toast.error("Failed to delete template");
    } finally {
      setDeleting(null);
    }
  };

  return (
    <div className="space-y-4">
      {templates.length === 0 ? (
        <div className="text-center py-12">
          <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
          <p className="mt-4 text-muted-foreground">No report templates found.</p>
          <p className="text-sm text-muted-foreground">Create your first template to get started.</p>
        </div>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Template Name</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Sections</TableHead>
              <TableHead>Created By</TableHead>
              <TableHead>Created</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {templates.map((template) => (
              <TableRow key={template.id}>
                <TableCell className="font-medium">{template.name}</TableCell>
                <TableCell>
                  <Badge className={reportTypeColors[template.report_type] ?? "bg-gray-500"}>
                    {template.report_type}
                  </Badge>
                </TableCell>
                <TableCell>{Array.isArray(template.sections) ? template.sections.length : 0}</TableCell>
                <TableCell>{template.created_by_user?.name ?? "—"}</TableCell>
                <TableCell>{new Date(template.created_at).toLocaleDateString()}</TableCell>
                <TableCell className="text-right">
                  <div className="flex justify-end gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      title="Download"
                      onClick={() => toast.info("Download not yet configured")}
                    >
                      <Download className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      title="Edit"
                      onClick={() => router.push(`/reports/templates/${template.id}/edit`)}
                    >
                      <Edit className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      title="Delete"
                      disabled={deleting === template.id}
                      onClick={() => handleDelete(template.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  );
}
