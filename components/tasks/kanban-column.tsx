"use client";

import { useState } from "react";
import { Plus, AlertCircle } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { TaskCard } from "./task-card";
import { ScrollArea } from "@/components/ui/scroll-area";

interface KanbanColumnProps {
  column: any;
  board: any;
  onTaskClick: (task: any) => void;
  onCreateTask: () => void;
  onDrop: (columnId: string, position: number) => void;
  onDragStart: (task: any) => void;
  onDragEnd: () => void;
  isDragging: boolean;
}

export function KanbanColumn({
  column,
  board,
  onTaskClick,
  onCreateTask,
  onDrop,
  onDragStart,
  onDragEnd,
  isDragging,
}: KanbanColumnProps) {
  const [isDragOver, setIsDragOver] = useState(false);

  const tasks = column.tasks || [];
  const isOverWipLimit = column.wipLimit && tasks.length > column.wipLimit;

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(true);
  };

  const handleDragLeave = () => {
    setIsDragOver(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(false);
    onDrop(column.id, tasks.length);
  };

  return (
    <div
      className={`flex w-80 flex-shrink-0 flex-col rounded-lg border bg-muted/30 ${
        isDragOver ? "ring-2 ring-primary" : ""
      }`}
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      onDrop={handleDrop}
    >
      <div className="flex items-center justify-between border-b bg-background p-3">
        <div className="flex items-center gap-2">
          <div className="h-3 w-3 rounded-full" style={{ backgroundColor: column.color }} />
          <h3 className="font-semibold">{column.name}</h3>
          <Badge variant="secondary" className="text-xs">
            {tasks.length}
          </Badge>
          {isOverWipLimit && (
            <span title="Over WIP limit">
              <AlertCircle className="h-4 w-4 text-amber-500" />
            </span>
          )}
        </div>
        <Button size="sm" variant="ghost" onClick={onCreateTask}>
          <Plus className="h-4 w-4" />
        </Button>
      </div>

      {column.wipLimit && (
        <div className="px-3 pt-2">
          <div className="text-xs text-muted-foreground">
            WIP Limit: {tasks.length}/{column.wipLimit}
          </div>
          <div className="mt-1 h-1 w-full overflow-hidden rounded-full bg-muted">
            <div
              className={`h-full transition-all ${isOverWipLimit ? "bg-amber-500" : "bg-primary"}`}
              style={{
                width: `${Math.min((tasks.length / column.wipLimit) * 100, 100)}%`,
              }}
            />
          </div>
        </div>
      )}

      <ScrollArea className="flex-1 p-3">
        <div className="space-y-2">
          {tasks.length === 0 ? (
            <div
              className={`flex h-32 items-center justify-center rounded-lg border-2 border-dashed ${
                isDragOver ? "border-primary bg-primary/5" : "border-muted-foreground/25"
              }`}
            >
              <p className="text-sm text-muted-foreground">{isDragging ? "Drop task here" : "No tasks"}</p>
            </div>
          ) : (
            tasks.map((task: any) => (
              <TaskCard
                key={task.id}
                task={task}
                onClick={() => onTaskClick(task)}
                onDragStart={() => onDragStart(task)}
                onDragEnd={onDragEnd}
              />
            ))
          )}
        </div>
      </ScrollArea>
    </div>
  );
}
