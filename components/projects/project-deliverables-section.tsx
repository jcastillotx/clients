"use client";

import { useState } from "react";
import { DeliverableList } from "./deliverable-list";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { ProjectDeliverable } from "@/lib/db/schema/projects";

interface ProjectDeliverablesSectionProps {
  projectId: string;
  initialDeliverables: ProjectDeliverable[];
}

export function ProjectDeliverablesSection({ projectId, initialDeliverables }: ProjectDeliverablesSectionProps) {
  const [deliverables, setDeliverables] = useState<ProjectDeliverable[]>(initialDeliverables);
  const [editing, setEditing] = useState<ProjectDeliverable | null>(null);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({ title: "", description: "", status: "pending", dueDate: "" });

  function openEdit(deliverable: ProjectDeliverable) {
    setEditing(deliverable);
    setForm({
      title: deliverable.title,
      description: deliverable.description ?? "",
      status: deliverable.status,
      dueDate: deliverable.dueDate ? new Date(deliverable.dueDate).toISOString().slice(0, 10) : "",
    });
  }

  async function handleSave() {
    if (!editing) return;
    setSaving(true);
    try {
      const res = await fetch(`/api/projects/${projectId}/deliverables`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          deliverableId: editing.id,
          title: form.title,
          description: form.description || null,
          status: form.status,
          dueDate: form.dueDate || null,
          deliveredAt: form.status === "completed" && !editing.deliveredAt ? new Date().toISOString() : undefined,
        }),
      });
      if (!res.ok) throw new Error("Failed to update deliverable");
      const { data } = await res.json();
      setDeliverables((prev) => prev.map((d) => (d.id === editing.id ? { ...d, ...data } : d)));
      toast.success("Deliverable updated");
      setEditing(null);
    } catch {
      toast.error("Failed to update deliverable");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(deliverableId: string) {
    try {
      const res = await fetch(`/api/projects/${projectId}/deliverables?deliverableId=${deliverableId}`, {
        method: "DELETE",
      });
      if (!res.ok) throw new Error("Failed to delete deliverable");
      setDeliverables((prev) => prev.filter((d) => d.id !== deliverableId));
      toast.success("Deliverable deleted");
    } catch {
      toast.error("Failed to delete deliverable");
    }
  }

  async function handleStatusChange(deliverableId: string, status: string) {
    try {
      const res = await fetch(`/api/projects/${projectId}/deliverables`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          deliverableId,
          status,
          deliveredAt: status === "completed" ? new Date().toISOString() : null,
        }),
      });
      if (!res.ok) throw new Error("Failed to update deliverable");
      const { data } = await res.json();
      setDeliverables((prev) => prev.map((d) => (d.id === deliverableId ? { ...d, ...data } : d)));
    } catch {
      toast.error("Failed to update deliverable");
    }
  }

  return (
    <>
      <DeliverableList
        deliverables={deliverables}
        onEdit={openEdit}
        onDelete={handleDelete}
        onStatusChange={handleStatusChange}
      />

      <Dialog open={!!editing} onOpenChange={(open) => !open && setEditing(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Deliverable</DialogTitle>
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
                <Label>Status</Label>
                <Select value={form.status} onValueChange={(v) => setForm({ ...form, status: v })}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="in_progress">In Progress</SelectItem>
                    <SelectItem value="review">In Review</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                    <SelectItem value="rejected">Rejected</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Due Date</Label>
                <Input
                  type="date"
                  value={form.dueDate}
                  onChange={(e) => setForm({ ...form, dueDate: e.target.value })}
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
