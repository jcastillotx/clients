"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Globe,
  ClipboardList,
  Megaphone,
  Palette,
  Search,
  Wrench,
  ArrowRightLeft,
  Rocket,
  Clock,
  CheckSquare,
  ListChecks,
  Layers,
} from "lucide-react";

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
  Globe,
  ClipboardList,
  Megaphone,
  Palette,
  Search,
  Wrench,
  ArrowRightLeft,
  Rocket,
};

const categoryLabels: Record<string, string> = {
  web_development: "Web Development",
  marketing: "Marketing",
  design: "Design",
  seo: "SEO",
  maintenance: "Maintenance",
  migration: "Migration",
  launch: "Launch",
  general: "General",
};

interface TemplateCardProps {
  template: {
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
      tasks: Array<{
        title: string;
        checklist?: Array<{ title: string }>;
      }>;
    }>;
    metadata?: {
      tags?: string[];
    } | null;
  };
  onApply?: (templateId: string) => void;
  onPreview?: (templateId: string) => void;
  showApply?: boolean;
}

export function TemplateCard({ template, onApply, onPreview, showApply = false }: TemplateCardProps) {
  const IconComponent = iconMap[template.icon || "ClipboardList"] || ClipboardList;

  const totalTasks = template.phases.reduce((sum, phase) => sum + phase.tasks.length, 0);
  const totalChecklist = template.phases.reduce(
    (sum, phase) => sum + phase.tasks.reduce((s, t) => s + (t.checklist?.length || 0), 0),
    0,
  );

  return (
    <Card className="group hover:shadow-md transition-shadow">
      <CardHeader className="pb-3">
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-3">
            <div
              className="p-2 rounded-lg"
              style={{ backgroundColor: `${template.color || "#3b82f6"}20` }}
            >
              <IconComponent className="h-5 w-5" style={{ color: template.color || "#3b82f6" }} />
            </div>
            <div>
              <CardTitle className="text-base">{template.name}</CardTitle>
              <Badge variant="outline" className="mt-1 text-xs">
                {categoryLabels[template.category] || template.category}
              </Badge>
            </div>
          </div>
          {template.isSystem && (
            <Badge variant="secondary" className="text-xs">
              Built-in
            </Badge>
          )}
        </div>
        {template.description && (
          <CardDescription className="mt-2 line-clamp-2">{template.description}</CardDescription>
        )}
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-3 gap-3 text-sm text-muted-foreground mb-4">
          <div className="flex items-center gap-1.5">
            <Layers className="h-3.5 w-3.5" />
            <span>{template.phases.length} phases</span>
          </div>
          <div className="flex items-center gap-1.5">
            <ListChecks className="h-3.5 w-3.5" />
            <span>{totalTasks} tasks</span>
          </div>
          <div className="flex items-center gap-1.5">
            <CheckSquare className="h-3.5 w-3.5" />
            <span>{totalChecklist} items</span>
          </div>
        </div>

        {template.estimatedHours && (
          <div className="flex items-center gap-1.5 text-sm text-muted-foreground mb-4">
            <Clock className="h-3.5 w-3.5" />
            <span>~{template.estimatedHours} hours estimated</span>
          </div>
        )}

        {template.metadata?.tags && template.metadata.tags.length > 0 && (
          <div className="flex flex-wrap gap-1 mb-4">
            {template.metadata.tags.map((tag) => (
              <Badge key={tag} variant="outline" className="text-xs font-normal">
                {tag}
              </Badge>
            ))}
          </div>
        )}

        <div className="flex gap-2">
          {onPreview && (
            <Button variant="outline" size="sm" className="flex-1" onClick={() => onPreview(template.id)}>
              Preview
            </Button>
          )}
          {showApply && onApply && (
            <Button size="sm" className="flex-1" onClick={() => onApply(template.id)}>
              Apply to Project
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
