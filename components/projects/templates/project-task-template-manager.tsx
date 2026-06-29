"use client";

import { useEffect, useMemo, useState } from "react";
import { AlertCircle, Loader2, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { fetchApi } from "@/lib/api/client";
import { TemplateCard } from "./template-card";
import { TemplatePreviewDialog } from "./template-preview-dialog";

type TemplateCategory =
  | "web_development"
  | "marketing"
  | "design"
  | "seo"
  | "maintenance"
  | "migration"
  | "launch"
  | "general";

type TaskPriority = "low" | "normal" | "high" | "urgent";

interface TemplateTask {
  title: string;
  description?: string;
  priority?: string;
  estimatedHours?: number;
  checklist?: Array<{ title: string; sortOrder?: number }>;
  labels?: string[];
  sortOrder?: number;
}

interface TemplatePhase {
  name: string;
  description?: string;
  sortOrder?: number;
  tasks: TemplateTask[];
}

interface Template {
  id: string;
  name: string;
  description?: string | null;
  category: string;
  icon?: string | null;
  color?: string | null;
  estimatedHours?: number | null;
  isSystem: boolean;
  phases: TemplatePhase[];
  metadata?: { tags?: string[] } | null;
}

interface DraftTask {
  title: string;
  description: string;
  priority: TaskPriority;
  estimatedHours: string;
  checklistText: string;
  labelsText: string;
}

interface DraftPhase {
  name: string;
  description: string;
  tasks: DraftTask[];
}

const categories: Array<{ value: TemplateCategory; label: string }> = [
  { value: "web_development", label: "Web Development" },
  { value: "marketing", label: "Marketing" },
  { value: "design", label: "Design" },
  { value: "seo", label: "SEO" },
  { value: "maintenance", label: "Maintenance" },
  { value: "migration", label: "Migration" },
  { value: "launch", label: "Launch" },
  { value: "general", label: "General" },
];

const priorities: TaskPriority[] = ["low", "normal", "high", "urgent"];

function createDraftTask(): DraftTask {
  return {
    title: "",
    description: "",
    priority: "normal",
    estimatedHours: "",
    checklistText: "",
    labelsText: "",
  };
}

function createDraftPhase(): DraftPhase {
  return {
    name: "",
    description: "",
    tasks: [createDraftTask()],
  };
}

function splitLines(value: string): string[] {
  return value
    .split("\n")
    .map((item) => item.trim())
    .filter(Boolean);
}

function splitCsv(value: string): string[] {
  return value
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);
}

function optionalNumber(value: string): number | undefined {
  if (!value.trim()) return undefined;
  const parsed = Number(value);
  return Number.isFinite(parsed) ? Math.max(0, Math.round(parsed)) : undefined;
}

export function ProjectTaskTemplateManager() {
  const [templates, setTemplates] = useState<Template[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [createOpen, setCreateOpen] = useState(false);
  const [previewTemplate, setPreviewTemplate] = useState<Template | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [category, setCategory] = useState<TemplateCategory>("web_development");
  const [estimatedHours, setEstimatedHours] = useState("");
  const [color, setColor] = useState("#3b82f6");
  const [tagsText, setTagsText] = useState("");
  const [phases, setPhases] = useState<DraftPhase[]>([createDraftPhase()]);

  const customTemplateCount = useMemo(
    () => templates.filter((template) => !template.isSystem).length,
    [templates],
  );

  useEffect(() => {
    void loadTemplates();
  }, []);

  async function loadTemplates() {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchApi<Template[]>("/api/projects/templates", undefined, {
        fallbackMessage: "Failed to load project task templates",
      });
      setTemplates(Array.isArray(data) ? data : []);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load project task templates");
    } finally {
      setLoading(false);
    }
  }

  function resetForm() {
    setName("");
    setDescription("");
    setCategory("web_development");
    setEstimatedHours("");
    setColor("#3b82f6");
    setTagsText("");
    setPhases([createDraftPhase()]);
  }

  function updatePhase(index: number, patch: Partial<DraftPhase>) {
    setPhases((current) =>
      current.map((phase, phaseIndex) => (phaseIndex === index ? { ...phase, ...patch } : phase)),
    );
  }

  function updateTask(phaseIndex: number, taskIndex: number, patch: Partial<DraftTask>) {
    setPhases((current) =>
      current.map((phase, currentPhaseIndex) => {
        if (currentPhaseIndex !== phaseIndex) return phase;
        return {
          ...phase,
          tasks: phase.tasks.map((task, currentTaskIndex) =>
            currentTaskIndex === taskIndex ? { ...task, ...patch } : task,
          ),
        };
      }),
    );
  }

  function addPhase() {
    setPhases((current) => [...current, createDraftPhase()]);
  }

  function removePhase(index: number) {
    setPhases((current) => (current.length === 1 ? current : current.filter((_, phaseIndex) => phaseIndex !== index)));
  }

  function addTask(phaseIndex: number) {
    setPhases((current) =>
      current.map((phase, currentPhaseIndex) =>
        currentPhaseIndex === phaseIndex ? { ...phase, tasks: [...phase.tasks, createDraftTask()] } : phase,
      ),
    );
  }

  function removeTask(phaseIndex: number, taskIndex: number) {
    setPhases((current) =>
      current.map((phase, currentPhaseIndex) => {
        if (currentPhaseIndex !== phaseIndex || phase.tasks.length === 1) return phase;
        return {
          ...phase,
          tasks: phase.tasks.filter((_, currentTaskIndex) => currentTaskIndex !== taskIndex),
        };
      }),
    );
  }

  async function handleSubmit() {
    const normalizedName = name.trim();
    if (!normalizedName) {
      toast.error("Template name is required");
      return;
    }

    const normalizedPhases = phases
      .map((phase, phaseIndex) => ({
        name: phase.name.trim(),
        description: phase.description.trim() || undefined,
        sortOrder: phaseIndex,
        tasks: phase.tasks
          .map((task, taskIndex) => ({
            title: task.title.trim(),
            description: task.description.trim() || undefined,
            priority: task.priority,
            estimatedHours: optionalNumber(task.estimatedHours),
            sortOrder: taskIndex,
            checklist: splitLines(task.checklistText).map((title, checklistIndex) => ({
              title,
              sortOrder: checklistIndex,
            })),
            labels: splitCsv(task.labelsText),
          }))
          .filter((task) => task.title),
      }))
      .filter((phase) => phase.name && phase.tasks.length > 0);

    if (normalizedPhases.length === 0) {
      toast.error("Add at least one phase with one task");
      return;
    }

    setSaving(true);
    try {
      const created = await fetchApi<Template>(
        "/api/projects/templates",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            name: normalizedName,
            description: description.trim() || undefined,
            category,
            icon: category === "seo" ? "Search" : category === "maintenance" ? "Wrench" : "ClipboardList",
            color,
            estimatedHours: optionalNumber(estimatedHours),
            metadata: {
              tags: splitCsv(tagsText),
              source: "admin",
              version: "1.0",
            },
            phases: normalizedPhases,
          }),
        },
        { fallbackMessage: "Failed to create project task template" },
      );
      setTemplates((current) => [created, ...current]);
      setCreateOpen(false);
      resetForm();
      toast.success("Project task template created");
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Failed to create project task template");
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <div className="space-y-6">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h1 className="text-3xl font-bold">Project Task Lists</h1>
            <p className="mt-1 text-muted-foreground">
              Manage reusable task list templates for project boards.
            </p>
          </div>
          <Button onClick={() => setCreateOpen(true)}>
            <Plus className="mr-2 h-4 w-4" />
            New Task List
          </Button>
        </div>

        <div className="grid gap-4 md:grid-cols-3">
          <Card>
            <CardHeader className="pb-2">
              <CardDescription>Available templates</CardDescription>
              <CardTitle>{templates.length}</CardTitle>
            </CardHeader>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardDescription>Built-in templates</CardDescription>
              <CardTitle>{templates.length - customTemplateCount}</CardTitle>
            </CardHeader>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardDescription>Custom templates</CardDescription>
              <CardTitle>{customTemplateCount}</CardTitle>
            </CardHeader>
          </Card>
        </div>

        {error && (
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{error}</AlertDescription>
          </Alert>
        )}

        {loading ? (
          <div className="flex items-center justify-center py-20">
            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
          </div>
        ) : (
          <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            {templates.map((template) => (
              <TemplateCard
                key={template.id}
                template={template}
                onPreview={() => setPreviewTemplate(template)}
              />
            ))}
          </div>
        )}
      </div>

      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent className="max-h-[90vh] max-w-5xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Create Project Task List</DialogTitle>
            <DialogDescription>
              Save a reusable template that can be applied to any project.
            </DialogDescription>
          </DialogHeader>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="template-name">Name</Label>
              <Input id="template-name" value={name} onChange={(event) => setName(event.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="template-category">Category</Label>
              <Select value={category} onValueChange={(value) => setCategory(value as TemplateCategory)}>
                <SelectTrigger id="template-category">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((item) => (
                    <SelectItem key={item.value} value={item.value}>
                      {item.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="template-description">Description</Label>
              <Textarea
                id="template-description"
                value={description}
                onChange={(event) => setDescription(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="template-hours">Estimated hours</Label>
              <Input
                id="template-hours"
                inputMode="numeric"
                value={estimatedHours}
                onChange={(event) => setEstimatedHours(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="template-color">Color</Label>
              <div className="flex gap-2">
                <Input
                  id="template-color"
                  type="color"
                  value={color}
                  onChange={(event) => setColor(event.target.value)}
                  className="w-16 p-1"
                />
                <Input value={color} onChange={(event) => setColor(event.target.value)} />
              </div>
            </div>
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="template-tags">Tags</Label>
              <Input id="template-tags" value={tagsText} onChange={(event) => setTagsText(event.target.value)} />
            </div>
          </div>

          <div className="space-y-5">
            {phases.map((phase, phaseIndex) => (
              <Card key={phaseIndex}>
                <CardHeader className="pb-4">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <CardTitle className="flex items-center gap-2 text-base">
                        Phase {phaseIndex + 1}
                        <Badge variant="outline">{phase.tasks.length} tasks</Badge>
                      </CardTitle>
                    </div>
                    <Button
                      variant="ghost"
                      size="icon"
                      disabled={phases.length === 1}
                      onClick={() => removePhase(phaseIndex)}
                      aria-label="Remove phase"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label>Phase name</Label>
                      <Input value={phase.name} onChange={(event) => updatePhase(phaseIndex, { name: event.target.value })} />
                    </div>
                    <div className="space-y-2">
                      <Label>Phase description</Label>
                      <Input
                        value={phase.description}
                        onChange={(event) => updatePhase(phaseIndex, { description: event.target.value })}
                      />
                    </div>
                  </div>

                  <div className="space-y-4">
                    {phase.tasks.map((task, taskIndex) => (
                      <div key={taskIndex} className="rounded-lg border p-4">
                        <div className="mb-4 flex items-start justify-between gap-3">
                          <h4 className="font-medium">Task {taskIndex + 1}</h4>
                          <Button
                            variant="ghost"
                            size="icon"
                            disabled={phase.tasks.length === 1}
                            onClick={() => removeTask(phaseIndex, taskIndex)}
                            aria-label="Remove task"
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                          <div className="space-y-2">
                            <Label>Task title</Label>
                            <Input
                              value={task.title}
                              onChange={(event) => updateTask(phaseIndex, taskIndex, { title: event.target.value })}
                            />
                          </div>
                          <div className="space-y-2">
                            <Label>Priority</Label>
                            <Select
                              value={task.priority}
                              onValueChange={(value) =>
                                updateTask(phaseIndex, taskIndex, { priority: value as TaskPriority })
                              }
                            >
                              <SelectTrigger>
                                <SelectValue />
                              </SelectTrigger>
                              <SelectContent>
                                {priorities.map((priority) => (
                                  <SelectItem key={priority} value={priority}>
                                    {priority}
                                  </SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
                          </div>
                          <div className="space-y-2 md:col-span-2">
                            <Label>Task description</Label>
                            <Textarea
                              value={task.description}
                              onChange={(event) =>
                                updateTask(phaseIndex, taskIndex, { description: event.target.value })
                              }
                            />
                          </div>
                          <div className="space-y-2">
                            <Label>Estimated hours</Label>
                            <Input
                              inputMode="numeric"
                              value={task.estimatedHours}
                              onChange={(event) =>
                                updateTask(phaseIndex, taskIndex, { estimatedHours: event.target.value })
                              }
                            />
                          </div>
                          <div className="space-y-2">
                            <Label>Labels</Label>
                            <Input
                              value={task.labelsText}
                              onChange={(event) =>
                                updateTask(phaseIndex, taskIndex, { labelsText: event.target.value })
                              }
                            />
                          </div>
                          <div className="space-y-2 md:col-span-2">
                            <Label>Checklist</Label>
                            <Textarea
                              value={task.checklistText}
                              onChange={(event) =>
                                updateTask(phaseIndex, taskIndex, { checklistText: event.target.value })
                              }
                            />
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>

                  <Button variant="outline" size="sm" onClick={() => addTask(phaseIndex)}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Task
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>

          <div className="flex justify-between gap-3 border-t pt-4">
            <Button variant="outline" onClick={addPhase}>
              <Plus className="mr-2 h-4 w-4" />
              Add Phase
            </Button>
            <div className="flex gap-2">
              <Button variant="outline" disabled={saving} onClick={() => setCreateOpen(false)}>
                Cancel
              </Button>
              <Button disabled={saving} onClick={handleSubmit}>
                {saving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                Create Task List
              </Button>
            </div>
          </div>
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
