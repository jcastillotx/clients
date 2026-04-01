"use client";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { ScrollArea } from "@/components/ui/scroll-area";
import { CheckSquare, Clock, Layers } from "lucide-react";

interface TemplatePreviewDialogProps {
  template: {
    name: string;
    description?: string | null;
    estimatedHours?: number | null;
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
  } | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

const priorityColors: Record<string, string> = {
  low: "bg-slate-100 text-slate-700",
  normal: "bg-blue-100 text-blue-700",
  high: "bg-orange-100 text-orange-700",
  urgent: "bg-red-100 text-red-700",
};

export function TemplatePreviewDialog({ template, open, onOpenChange }: TemplatePreviewDialogProps) {
  if (!template) return null;

  const totalTasks = template.phases.reduce((sum, p) => sum + p.tasks.length, 0);
  const totalChecklist = template.phases.reduce(
    (sum, p) => sum + p.tasks.reduce((s, t) => s + (t.checklist?.length || 0), 0),
    0,
  );

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[80vh]">
        <DialogHeader>
          <DialogTitle>{template.name}</DialogTitle>
          {template.description && <DialogDescription>{template.description}</DialogDescription>}
          <div className="flex gap-4 pt-2 text-sm text-muted-foreground">
            <span className="flex items-center gap-1">
              <Layers className="h-3.5 w-3.5" /> {template.phases.length} phases
            </span>
            <span className="flex items-center gap-1">
              <CheckSquare className="h-3.5 w-3.5" /> {totalTasks} tasks, {totalChecklist} checklist items
            </span>
            {template.estimatedHours && (
              <span className="flex items-center gap-1">
                <Clock className="h-3.5 w-3.5" /> ~{template.estimatedHours}h
              </span>
            )}
          </div>
        </DialogHeader>

        <ScrollArea className="max-h-[55vh] pr-4">
          <div className="space-y-6">
            {template.phases.map((phase, phaseIdx) => (
              <div key={phaseIdx}>
                <h3 className="font-semibold text-sm mb-1">
                  Phase {phaseIdx + 1}: {phase.name}
                </h3>
                {phase.description && (
                  <p className="text-xs text-muted-foreground mb-2">{phase.description}</p>
                )}
                <div className="space-y-2 ml-2">
                  {phase.tasks.map((task, taskIdx) => (
                    <div key={taskIdx} className="border rounded-md p-3">
                      <div className="flex items-center gap-2 mb-1">
                        <span className="font-medium text-sm">{task.title}</span>
                        {task.priority && (
                          <Badge variant="outline" className={`text-xs ${priorityColors[task.priority] || ""}`}>
                            {task.priority}
                          </Badge>
                        )}
                        {task.estimatedHours && (
                          <span className="text-xs text-muted-foreground ml-auto">{task.estimatedHours}h</span>
                        )}
                      </div>
                      {task.description && (
                        <p className="text-xs text-muted-foreground mb-2">{task.description}</p>
                      )}
                      {task.checklist && task.checklist.length > 0 && (
                        <div className="space-y-1 mt-2 pl-2 border-l-2 border-muted">
                          {task.checklist.map((item, idx) => (
                            <div key={idx} className="flex items-center gap-1.5 text-xs text-muted-foreground">
                              <div className="h-3 w-3 rounded-sm border border-muted-foreground/30 flex-shrink-0" />
                              {item.title}
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </ScrollArea>
      </DialogContent>
    </Dialog>
  );
}
