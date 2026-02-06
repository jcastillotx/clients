"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ProjectMilestone, ProjectDeliverable } from "@/lib/db/schema/projects";
import { Badge } from "@/components/ui/badge";
import { CheckCircle2, Circle, Clock } from "lucide-react";
import { format } from "date-fns";

interface ProjectTimelineProps {
  milestones: (ProjectMilestone & { deliverables?: ProjectDeliverable[] })[];
}

export function ProjectTimeline({ milestones }: ProjectTimelineProps) {
  const sortedMilestones = [...milestones].sort((a, b) => (a.sortOrder || 0) - (b.sortOrder || 0));

  return (
    <Card>
      <CardHeader>
        <CardTitle>Project Timeline</CardTitle>
        <CardDescription>Milestones and deliverables</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-8">
          {sortedMilestones.map((milestone, index) => {
            const isCompleted = milestone.completedAt !== null;
            const isPast = milestone.dueDate && new Date(milestone.dueDate) < new Date();
            const completionPercentage = milestone.completionPercentage || 0;

            return (
              <div key={milestone.id} className="relative">
                {/* Timeline line */}
                {index < sortedMilestones.length - 1 && (
                  <div className="absolute left-4 top-8 bottom-0 w-0.5 bg-border" />
                )}

                <div className="flex gap-4">
                  {/* Icon */}
                  <div className="flex-shrink-0 mt-1">
                    {isCompleted ? (
                      <CheckCircle2 className="h-8 w-8 text-green-500" />
                    ) : isPast && !isCompleted ? (
                      <Clock className="h-8 w-8 text-yellow-500" />
                    ) : (
                      <Circle className="h-8 w-8 text-muted-foreground" />
                    )}
                  </div>

                  {/* Content */}
                  <div className="flex-1 pb-8">
                    <div className="flex items-start justify-between">
                      <div>
                        <h3 className="font-semibold text-lg">{milestone.title}</h3>
                        {milestone.description && (
                          <p className="text-sm text-muted-foreground mt-1">{milestone.description}</p>
                        )}
                      </div>
                      <div className="flex flex-col items-end gap-2">
                        {isCompleted ? (
                          <Badge className="bg-green-500">Completed</Badge>
                        ) : isPast ? (
                          <Badge className="bg-yellow-500">Overdue</Badge>
                        ) : (
                          <Badge variant="outline">In Progress</Badge>
                        )}
                        {milestone.dueDate && (
                          <span className="text-xs text-muted-foreground">
                            Due: {format(new Date(milestone.dueDate), "MMM dd, yyyy")}
                          </span>
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

                    {/* Deliverables */}
                    {milestone.deliverables && milestone.deliverables.length > 0 && (
                      <div className="mt-4 space-y-2">
                        <h4 className="text-sm font-medium text-muted-foreground">Deliverables</h4>
                        {milestone.deliverables.map((deliverable) => {
                          const deliverableCompleted = deliverable.status === "completed";
                          const deliverableOverdue =
                            deliverable.dueDate && new Date(deliverable.dueDate) < new Date() && !deliverableCompleted;

                          return (
                            <div key={deliverable.id} className="flex items-center gap-2 p-2 rounded-md bg-muted/50">
                              {deliverableCompleted ? (
                                <CheckCircle2 className="h-4 w-4 text-green-500" />
                              ) : (
                                <Circle className="h-4 w-4 text-muted-foreground" />
                              )}
                              <div className="flex-1">
                                <div className="text-sm font-medium">{deliverable.title}</div>
                                {deliverable.description && (
                                  <div className="text-xs text-muted-foreground">{deliverable.description}</div>
                                )}
                              </div>
                              <div className="flex items-center gap-2">
                                <Badge
                                  variant={deliverableCompleted ? "default" : "outline"}
                                  className={deliverableOverdue ? "bg-yellow-500 text-white" : ""}
                                >
                                  {deliverable.status}
                                </Badge>
                                {deliverable.dueDate && (
                                  <span className="text-xs text-muted-foreground">
                                    {format(new Date(deliverable.dueDate), "MMM dd")}
                                  </span>
                                )}
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    )}

                    {/* Completion date */}
                    {milestone.completedAt && (
                      <div className="mt-2 text-xs text-muted-foreground">
                        Completed on {format(new Date(milestone.completedAt), "MMM dd, yyyy")}
                      </div>
                    )}
                  </div>
                </div>
              </div>
            );
          })}

          {sortedMilestones.length === 0 && (
            <div className="text-center py-8 text-muted-foreground">No milestones defined yet</div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
