"use client";

import { useState, useEffect } from "react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { TemplateCard } from "./template-card";
import { TemplatePreviewDialog } from "./template-preview-dialog";
import { Loader2 } from "lucide-react";

interface Template {
  id: string;
  name: string;
  description?: string | null;
  category: string;
  icon?: string | null;
  color?: string | null;
  estimatedHours?: number | null;
  isSystem: boolean;
  phases: Array<{
    name: string;
    description?: string;
    tasks: Array<{
      title: string;
      description?: string;
      priority?: string;
      estimatedHours?: number;
      checklist?: Array<{ title: string }>;
    }>;
  }>;
  metadata?: { tags?: string[] } | null;
}

interface ApplyTemplateDialogProps {
  projectId: string;
  projectName: string;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onApplied?: (result: { boardId: string; totalTasks: number; totalChecklists: number }) => void;
}

export function ApplyTemplateDialog({
  projectId,
  projectName,
  open,
  onOpenChange,
  onApplied,
}: ApplyTemplateDialogProps) {
  const [templates, setTemplates] = useState<Template[]>([]);
  const [loading, setLoading] = useState(false);
  const [applying, setApplying] = useState(false);
  const [previewTemplate, setPreviewTemplate] = useState<Template | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (open) {
      fetchTemplates();
    }
  }, [open]);

  const fetchTemplates = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await fetch("/api/projects/templates");
      const json = await res.json();
      if (json.success) {
        setTemplates(json.data);
      } else {
        setError(json.error || "Failed to fetch templates");
      }
    } catch {
      setError("Failed to fetch templates");
    } finally {
      setLoading(false);
    }
  };

  const handleApply = async (templateId: string) => {
    setApplying(true);
    setError(null);
    try {
      const res = await fetch(`/api/projects/${projectId}/apply-template`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ templateId }),
      });
      const json = await res.json();
      if (json.success) {
        onOpenChange(false);
        onApplied?.(json.data);
      } else {
        setError(json.error || "Failed to apply template");
      }
    } catch {
      setError("Failed to apply template");
    } finally {
      setApplying(false);
    }
  };

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent className="max-w-3xl max-h-[80vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Apply Task Template</DialogTitle>
            <DialogDescription>
              Choose a template to create a task board with pre-configured phases, tasks, and checklists for{" "}
              <span className="font-medium">{projectName}</span>.
            </DialogDescription>
          </DialogHeader>

          {error && (
            <div className="bg-destructive/10 text-destructive text-sm p-3 rounded-md">{error}</div>
          )}

          {loading ? (
            <div className="flex items-center justify-center py-12">
              <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
            </div>
          ) : templates.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <p>No templates available yet.</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {templates.map((template) => (
                <TemplateCard
                  key={template.id}
                  template={template}
                  showApply
                  onApply={applying ? undefined : handleApply}
                  onPreview={() => setPreviewTemplate(template)}
                />
              ))}
            </div>
          )}

          {applying && (
            <div className="flex items-center justify-center gap-2 py-4 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
              Applying template...
            </div>
          )}
        </DialogContent>
      </Dialog>

      <TemplatePreviewDialog
        template={previewTemplate}
        open={!!previewTemplate}
        onOpenChange={(open) => !open && setPreviewTemplate(null)}
      />
    </>
  );
}
