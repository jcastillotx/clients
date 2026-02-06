"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ProjectMilestone } from "@/lib/db/schema/projects";
import { CheckCircle2, Circle, Clock, Edit, Trash2 } from "lucide-react";
import { format } from "date-fns";

interface MilestoneListProps {
  milestones: ProjectMilestone[];
  onEdit?: (milestone: ProjectMilestone) => void;
  onDelete?: (milestoneId: string) => void;
  onToggleComplete?: (milestoneId: string, completed: boolean) => void;
}

export function MilestoneList({ milestones, onEdit, onDelete, onToggleComplete }: MilestoneListProps) {
  const sortedMilestones = [...milestones].sort((a, b) => a.sortOrder - b.sortOrder);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Milestones</CardTitle>
        <CardDescription>Track project milestones and their completion status</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          {sortedMilestones.map((milestone) => {
            const isCompleted = milestone.completedAt !== null;
            const isPast = milestone.dueDate && new Date(milestone.dueDate) < new Date();
            const completionPercentage = milestone.completionPercentage || 0;

            return (
              <div
                key={milestone.id}
                className="flex items-center gap-4 p-4 rounded-lg border hover:bg-muted/50 transition-colors"
              >
                {/* Checkbox/Icon */}
                <button
                  onClick={() => onToggleComplete?.(milestone.id, !isCompleted)}
                  className="flex-shrink-0 transition-transform hover:scale-110"
                >
                  {isCompleted ? (
                    <CheckCircle2 className="h-6 w-6 text-green-500" />
                  ) : isPast && !isCompleted ? (
                    <Clock className="h-6 w-6 text-yellow-500" />
                  ) : (
                    <Circle className="h-6 w-6 text-muted-foreground" />
                  )}
                </button>

                {/* Content */}
                <div className="flex-1 min-w-0">
                  <div className="flex items-start justify-between gap-2">
                    <div className="flex-1">
                      <h4 className={`font-semibold ${isCompleted ? "line-through text-muted-foreground" : ""}`}>
                        {milestone.title}
                      </h4>
                      {milestone.description && (
                        <p className="text-sm text-muted-foreground mt-1">{milestone.description}</p>
                      )}
                    </div>
                    <div className="flex items-center gap-2">
                      {isCompleted ? (
                        <Badge className="bg-green-500">Completed</Badge>
                      ) : isPast ? (
                        <Badge className="bg-yellow-500">Overdue</Badge>
                      ) : (
                        <Badge variant="outline">In Progress</Badge>
                      )}
                    </div>
                  </div>

                  {/* Progress bar */}
                  <div className="mt-3">
                    <div className="flex justify-between text-xs text-muted-foreground mb-1">
                      <span>Progress</span>
                      <span>{completionPercentage}%</span>
                    </div>
                    <div className="w-full h-2 bg-muted rounded-full overflow-hidden">
                      <div
                        className={`h-full transition-all ${isCompleted ? "bg-green-500" : "bg-primary"}`}
                        style={{ width: `${completionPercentage}%` }}
                      />
                    </div>
                  </div>

                  {/* Dates */}
                  <div className="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                    {milestone.dueDate && <div>Due: {format(new Date(milestone.dueDate), "MMM dd, yyyy")}</div>}
                    {milestone.completedAt && (
                      <div>Completed: {format(new Date(milestone.completedAt), "MMM dd, yyyy")}</div>
                    )}
                  </div>
                </div>

                {/* Actions */}
                <div className="flex items-center gap-2">
                  {onEdit && (
                    <Button variant="ghost" size="sm" onClick={() => onEdit(milestone)} className="h-8 w-8 p-0">
                      <Edit className="h-4 w-4" />
                    </Button>
                  )}
                  {onDelete && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => onDelete(milestone.id)}
                      className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  )}
                </div>
              </div>
            );
          })}

          {sortedMilestones.length === 0 && (
            <div className="text-center py-8 text-muted-foreground">No milestones yet. Create one to get started.</div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
