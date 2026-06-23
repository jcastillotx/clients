"use client";

import { useState } from "react";
import { Plus, FileText, Edit2, Trash2, Eye } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { InvoiceTemplateDialog } from "./invoice-template-dialog";
import { useToast } from "@/hooks/use-toast";
import { fetchApi } from "@/lib/api/client";

interface Template {
  id: string;
  name: string;
  description: string | null;
  html_content: string;
  css_content: string | null;
  is_default: boolean;
  is_active: boolean;
  created_at: string;
}

interface InvoiceTemplateListProps {
  templates: Template[];
  onTemplatesChange: (templates: Template[]) => void;
}

export function InvoiceTemplateList({ templates, onTemplatesChange }: InvoiceTemplateListProps) {
  const [selectedTemplate, setSelectedTemplate] = useState<Template | null>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const { toast } = useToast();

  const handleCreate = () => {
    setSelectedTemplate(null);
    setIsDialogOpen(true);
  };

  const handleEdit = (template: Template) => {
    setSelectedTemplate(template);
    setIsDialogOpen(true);
  };

  const handleDelete = async (templateId: string) => {
    if (!confirm("Are you sure you want to delete this template?")) {
      return;
    }

    try {
      await fetchApi(`/api/admin/templates/invoice/${templateId}`, { method: "DELETE" }, {
        fallbackMessage: "Failed to delete template",
      });

      // Remove from list
      onTemplatesChange(templates.filter((t) => t.id !== templateId));

      toast({
        title: "Template deleted",
        description: "The template has been deleted successfully.",
      });
    } catch (error) {
      toast({
        title: "Error",
        description: error instanceof Error ? error.message : "Failed to delete template",
        variant: "destructive",
      });
    }
  };

  const handleSave = async (data: any) => {
    try {
      const url = selectedTemplate
        ? `/api/admin/templates/invoice/${selectedTemplate.id}`
        : "/api/admin/templates/invoice";

      const method = selectedTemplate ? "PATCH" : "POST";

      const template = await fetchApi<Template>(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      }, { fallbackMessage: "Failed to save template" });

      if (selectedTemplate) {
        // Update existing
        onTemplatesChange(templates.map((t) => (t.id === template.id ? template : t)));
      } else {
        // Add new
        onTemplatesChange([template, ...templates]);
      }

      setIsDialogOpen(false);
      toast({
        title: selectedTemplate ? "Template updated" : "Template created",
        description: `The template has been ${selectedTemplate ? "updated" : "created"} successfully.`,
      });
    } catch (error) {
      toast({
        title: "Error",
        description: error instanceof Error ? error.message : "Failed to save template",
        variant: "destructive",
      });
      throw error;
    }
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold">Invoice Templates</h2>
          <p className="text-muted-foreground">Create and manage invoice templates with custom HTML and CSS</p>
        </div>
        <Button onClick={handleCreate}>
          <Plus className="h-4 w-4 mr-2" />
          New Template
        </Button>
      </div>

      {templates.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <FileText className="h-12 w-12 text-muted-foreground mb-4" />
            <p className="text-muted-foreground text-center">
              No invoice templates yet. Create your first template to get started.
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {templates.map((template) => (
            <Card key={template.id}>
              <CardHeader>
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <CardTitle className="text-lg">{template.name}</CardTitle>
                    <CardDescription className="mt-1">{template.description || "No description"}</CardDescription>
                  </div>
                  <div className="flex flex-col gap-1">
                    {template.is_default && <Badge variant="default">Default</Badge>}
                    {!template.is_active && <Badge variant="secondary">Inactive</Badge>}
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div className="flex gap-2">
                  <Button variant="outline" size="sm" onClick={() => handleEdit(template)}>
                    <Edit2 className="h-3 w-3 mr-1" />
                    Edit
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => window.open(`/api/admin/templates/invoice/${template.id}/preview`, "_blank")}
                  >
                    <Eye className="h-3 w-3 mr-1" />
                    Preview
                  </Button>
                  <Button variant="outline" size="sm" onClick={() => handleDelete(template.id)}>
                    <Trash2 className="h-3 w-3 mr-1" />
                    Delete
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <InvoiceTemplateDialog
        open={isDialogOpen}
        onOpenChange={setIsDialogOpen}
        template={selectedTemplate}
        onSave={handleSave}
      />
    </div>
  );
}
