"use client";

import { useState } from "react";
import { formatDistanceToNow } from "date-fns";
import { Trash2 } from "lucide-react";
import { toast } from "sonner";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { fetchApi } from "@/lib/api/client";

interface TaskDetailModalProps {
  task: any;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onUpdate?: (taskId: string) => void;
  onDeleted?: (taskId: string) => void;
}

export function TaskDetailModal({ task, open, onOpenChange, onDeleted }: TaskDetailModalProps) {
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [deleting, setDeleting] = useState(false);

  if (!task) return null;

  const handleDelete = async () => {
    setDeleting(true);
    try {
      await fetchApi(
        `/api/tasks/${task.id}`,
        { method: "DELETE" },
        { fallbackMessage: "Failed to delete task" },
      );
      toast.success("Task deleted");
      setConfirmOpen(false);
      onDeleted?.(task.id);
      onOpenChange(false);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Failed to delete task");
    } finally {
      setDeleting(false);
    }
  };

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent className="sm:max-w-[600px]">
          <DialogHeader>
            <div className="mb-2 flex items-center gap-2">
              <Badge variant="outline">{task.status}</Badge>
              <Badge variant="secondary">{task.priority}</Badge>
            </div>
            <DialogTitle className="text-2xl">{task.title}</DialogTitle>
            <DialogDescription>
              Created {formatDistanceToNow(new Date(task.createdAt), { addSuffix: true })}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div>
              <h4 className="mb-2 text-sm font-medium uppercase tracking-wider text-muted-foreground">Description</h4>
              <p className="rounded-lg border bg-muted/30 p-4 text-sm">
                {task.description || "No description provided."}
              </p>
            </div>
            {task.tags && task.tags.length > 0 && (
              <div>
                <h4 className="mb-2 text-sm font-medium uppercase tracking-wider text-muted-foreground">Tags</h4>
                <div className="flex flex-wrap gap-2">
                  {task.tags.map((tag: string) => (
                    <Badge key={tag} variant="outline">
                      {tag}
                    </Badge>
                  ))}
                </div>
              </div>
            )}
          </div>
          <div className="flex justify-end border-t pt-4">
            <Button variant="destructive" size="sm" disabled={deleting} onClick={() => setConfirmOpen(true)}>
              <Trash2 className="mr-2 h-4 w-4" />
              {deleting ? "Deleting..." : "Delete task"}
            </Button>
          </div>
        </DialogContent>
      </Dialog>
      <ConfirmDialog
        open={confirmOpen}
        onOpenChange={setConfirmOpen}
        title="Delete task?"
        description="This will permanently delete the task and its comments, checklists, labels, and assignees."
        confirmLabel="Delete task"
        onConfirm={handleDelete}
        loading={deleting}
      />
    </>
  );
}
