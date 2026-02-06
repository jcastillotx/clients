"use client";

import { format } from "date-fns";
import { Calendar, AlertCircle, CheckCircle2, Clock, User } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Progress } from "@/components/ui/progress";

interface TaskCardProps {
  task: any;
  onClick: () => void;
  onDragStart: () => void;
  onDragEnd: () => void;
}

const priorityColors = {
  low: "bg-slate-100 text-slate-800 border-slate-300",
  normal: "bg-blue-100 text-blue-800 border-blue-300",
  high: "bg-amber-100 text-amber-800 border-amber-300",
  urgent: "bg-red-100 text-red-800 border-red-300",
};

export function TaskCard({ task, onClick, onDragStart, onDragEnd }: TaskCardProps) {
  const isOverdue = task.dueDate && new Date(task.dueDate) < new Date() && !task.completedAt;
  const isDueSoon =
    task.dueDate &&
    !isOverdue &&
    !task.completedAt &&
    new Date(task.dueDate) <= new Date(Date.now() + 2 * 24 * 60 * 60 * 1000);

  const checklistProgress =
    task.checklists?.length > 0
      ? {
          total: task.checklists.length,
          completed: task.checklists.filter((c: any) => c.isCompleted).length,
          percentage: Math.round(
            (task.checklists.filter((c: any) => c.isCompleted).length / task.checklists.length) * 100,
          ),
        }
      : null;

  return (
    <Card
      draggable
      onDragStart={onDragStart}
      onDragEnd={onDragEnd}
      onClick={onClick}
      className="cursor-pointer transition-all hover:shadow-md active:cursor-grabbing active:opacity-50"
    >
      <CardContent className="p-3">
        <div className="mb-2 flex items-start justify-between gap-2">
          <h4 className="flex-1 text-sm font-medium leading-tight">{task.title}</h4>
          <Badge
            variant="outline"
            className={`text-xs ${priorityColors[task.priority as keyof typeof priorityColors]}`}
          >
            {task.priority}
          </Badge>
        </div>

        {task.description && <p className="mb-2 line-clamp-2 text-xs text-muted-foreground">{task.description}</p>}

        {task.labelRelations && task.labelRelations.length > 0 && (
          <div className="mb-2 flex flex-wrap gap-1">
            {task.labelRelations.map((rel: any) => (
              <Badge
                key={rel.label.id}
                variant="secondary"
                className="text-xs"
                style={{
                  backgroundColor: `${rel.label.color}20`,
                  color: rel.label.color,
                  borderColor: rel.label.color,
                }}
              >
                {rel.label.name}
              </Badge>
            ))}
          </div>
        )}

        {checklistProgress && (
          <div className="mb-2">
            <div className="mb-1 flex items-center justify-between text-xs text-muted-foreground">
              <span className="flex items-center gap-1">
                <CheckCircle2 className="h-3 w-3" />
                {checklistProgress.completed}/{checklistProgress.total}
              </span>
              <span>{checklistProgress.percentage}%</span>
            </div>
            <Progress value={checklistProgress.percentage} className="h-1" />
          </div>
        )}

        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2 text-xs text-muted-foreground">
            {task.dueDate && (
              <div
                className={`flex items-center gap-1 ${isOverdue ? "text-red-600" : isDueSoon ? "text-amber-600" : ""}`}
              >
                {isOverdue ? <AlertCircle className="h-3 w-3" /> : <Calendar className="h-3 w-3" />}
                <span className="text-xs">{format(new Date(task.dueDate), "MMM d")}</span>
              </div>
            )}

            {task.estimatedHours && (
              <div className="flex items-center gap-1">
                <Clock className="h-3 w-3" />
                <span>{task.estimatedHours}h</span>
              </div>
            )}
          </div>

          {task.assignees && task.assignees.length > 0 && (
            <div className="flex -space-x-2">
              {task.assignees.slice(0, 3).map((assignee: any) => (
                <Avatar key={assignee.userId} className="h-6 w-6 border-2 border-background">
                  <AvatarImage src={assignee.user?.avatar} />
                  <AvatarFallback className="text-[10px]">{assignee.user?.name?.charAt(0) || "?"}</AvatarFallback>
                </Avatar>
              ))}
              {task.assignees.length > 3 && (
                <div className="flex h-6 w-6 items-center justify-center rounded-full border-2 border-background bg-muted text-[10px] font-medium">
                  +{task.assignees.length - 3}
                </div>
              )}
            </div>
          )}
        </div>

        {task.completedAt && (
          <div className="mt-2 flex items-center gap-1 text-xs text-green-600">
            <CheckCircle2 className="h-3 w-3" />
            <span>Completed {format(new Date(task.completedAt), "MMM d")}</span>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
