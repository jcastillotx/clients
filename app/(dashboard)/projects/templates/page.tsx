"use client";

import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { TemplateCard } from "@/components/projects/templates/template-card";
import { TemplatePreviewDialog } from "@/components/projects/templates/template-preview-dialog";
import { ArrowLeft, Loader2 } from "lucide-react";
import Link from "next/link";

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

export default function ProjectTemplatesPage() {
  const [templates, setTemplates] = useState<Template[]>([]);
  const [loading, setLoading] = useState(true);
  const [previewTemplate, setPreviewTemplate] = useState<Template | null>(null);

  useEffect(() => {
    fetchTemplates();
  }, []);

  const fetchTemplates = async () => {
    try {
      const res = await fetch("/api/projects/templates");
      const json = await res.json();
      if (json.success) {
        setTemplates(json.data);
      }
    } catch (error) {
      console.error("Error fetching templates:", error);
    } finally {
      setLoading(false);
    }
  };

  const categories = [...new Set(templates.map((t) => t.category))];

  return (
    <div className="container mx-auto py-8 px-4">
      <div className="flex items-center gap-4 mb-8">
        <Button variant="ghost" size="sm" asChild>
          <Link href="/projects">
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to Projects
          </Link>
        </Button>
      </div>

      <div className="mb-8">
        <h1 className="text-3xl font-bold">Task Template Library</h1>
        <p className="text-muted-foreground mt-1">
          Browse reusable task templates that can be applied to any project. Each template creates a complete task board
          with phases, tasks, and checklists.
        </p>
      </div>

      {loading ? (
        <div className="flex items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : templates.length === 0 ? (
        <div className="text-center py-20 border rounded-lg">
          <h3 className="text-lg font-semibold mb-2">No templates yet</h3>
          <p className="text-muted-foreground">Task templates will appear here as they are created.</p>
        </div>
      ) : (
        <div className="space-y-10">
          {categories.map((category) => {
            const categoryTemplates = templates.filter((t) => t.category === category);
            const categoryLabel =
              {
                web_development: "Web Development",
                marketing: "Marketing",
                design: "Design",
                seo: "SEO",
                maintenance: "Maintenance",
                migration: "Migration",
                launch: "Launch",
                general: "General",
              }[category] || category;

            return (
              <div key={category}>
                <h2 className="text-xl font-semibold mb-4">{categoryLabel}</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {categoryTemplates.map((template) => (
                    <TemplateCard
                      key={template.id}
                      template={template}
                      onPreview={() => setPreviewTemplate(template)}
                    />
                  ))}
                </div>
              </div>
            );
          })}
        </div>
      )}

      <TemplatePreviewDialog
        template={previewTemplate}
        open={!!previewTemplate}
        onOpenChange={(open) => !open && setPreviewTemplate(null)}
      />
    </div>
  );
}
