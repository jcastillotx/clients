"use client";

import { useState } from "react";
import { MilestoneList } from "./milestone-list";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { ProjectMilestone } from "@/lib/db/schema/projects";

interface ProjectMilestonesSectionProps {
  projectId: string;
  initialMilestones: ProjectMilestone[];
}

export function ProjectMilestonesSection({ projectId, initialMilestones }: ProjectMilestonesSectionProps) {
  const [milestones, setMilestones] = useState<ProjectMilestone[]>(initialMilestones);
  const [editing, setEditing] = useState<ProjectMilestone | null>(null);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({ title: "", description: "", dueDate: "", completionPercentage: 0 });

  function openEdit(milestone: ProjectMilestone) {
    setEditing(milestone);
    setForm({
      title: milestone.title,
      description: milestone.description ?? "",
      dueDate: milestone.dueDate ? new Date(milestone.dueDate).toISOString().slice(0, 10) : "",
      completionPercentage: milestone.completionPercentage ?? 0,
    });
  }

  async function handleSave() {
    if (!editing) return;
    setSaving(true);
    try {
      const res = await fetch(`/api/projects/${projectId}/milestones`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          milestoneId: editing.id,
          title: form.title,
          description: form.description || null,
          dueDate: form.dueDate || null,
          completionPercentage: form.completionPercentage,
        }),
      });
      if (!res.ok) throw new Error("Failed to update milestone");
      const { data } = await res.json();
      setMilestones((prev) => prev.map((m) => (m.id === editing.id ? { ...m, ...data } : m)));
      toast.success("Milestone updated");
      setEditing(null);
    } catch {
      toast.error("Failed to update milestone");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(milestoneId: string) {
    try {
      const res = await fetch(`/api/projects/${projectId}/milestones?milestoneId=${milestoneId}`, {
        method: "DELETE",
      });
      if (!res.ok) throw new Error("Failed to delete milestone");
      setMilestones((prev) => prev.filter((m) => m.id !== milestoneId));
      toast.success("Milestone deleted");
    } catch {
      toast.error("Failed to delete milestone");
    }
  }

  async function handleToggleComplete(milestoneId: string, completed: boolean) {
    try {
      const res = await fetch(`/api/projects/${projectId}/milestones`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          milestoneId,
          completedAt: completed ? new Date().toISOString() : null,
          completionPercentage: completed ? 100 : 0,
        }),
      });
      if (!res.ok) throw new Error("Failed to update milestone");
      const { data } = await res.json();
      setMilestones((prev) => prev.map((m) => (m.id === milestoneId ? { ...m, ...data } : m)));
    } catch {
      toast.error("Failed to update milestone");
    }
  }

  return (
    <>
      <MilestoneList
        milestones={milestones}
        onEdit={openEdit}
        onDelete={handleDelete}
        onToggleComplete={handleToggleComplete}
      />

      <Dialog open={!!editing} onOpenChange={(open) => !open && setEditing(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Milestone</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-2">
            <div className="space-y-2">
              <Label>Title</Label>
              <Input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>Description</Label>
              <Textarea
                value={form.description}
                onChange={(e) => setForm({ ...form, description: e.target.value })}
                rows={3}
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Due Date</Label>
                <Input
                  type="date"
                  value={form.dueDate}
                  onChange={(e) => setForm({ ...form, dueDate: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label>Progress (%)</Label>
                <Input
                  type="number"
                  min="0"
                  max="100"
                  value={form.completionPercentage}
                  onChange={(e) => setForm({ ...form, completionPercentage: parseInt(e.target.value) || 0 })}
                />
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setEditing(null)}>Cancel</Button>
            <Button onClick={handleSave} disabled={saving || !form.title}>
              {saving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : null}
              Save
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
