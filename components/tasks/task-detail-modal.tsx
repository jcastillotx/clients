"use client";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { formatDistanceToNow } from "date-fns";

interface TaskDetailModalProps {
  task: any;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function TaskDetailModal({ task, open, onOpenChange }: TaskDetailModalProps) {
  if (!task) return null;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[600px]">
        <DialogHeader>
          <div className="flex items-center gap-2 mb-2">
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
            <h4 className="text-sm font-medium mb-2 text-muted-foreground uppercase tracking-wider">Description</h4>
            <p className="text-sm border rounded-lg p-4 bg-muted/30">
              {task.description || "No description provided."}
            </p>
          </div>
          {task.tags && task.tags.length > 0 && (
            <div>
              <h4 className="text-sm font-medium mb-2 text-muted-foreground uppercase tracking-wider">Tags</h4>
              <div className="flex flex-wrap gap-2">
                {task.tags.map((tag: string) => (
                  <Badge key={tag} variant="outline">{tag}</Badge>
                ))}
              </div>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
