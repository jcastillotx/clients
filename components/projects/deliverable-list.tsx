"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ProjectDeliverable } from "@/lib/db/schema/projects";
import { CheckCircle2, Circle, Clock, Edit, Trash2, FileText } from "lucide-react";
import { format } from "date-fns";

interface DeliverableListProps {
  deliverables: ProjectDeliverable[];
  onEdit?: (deliverable: ProjectDeliverable) => void;
  onDelete?: (deliverableId: string) => void;
  onStatusChange?: (deliverableId: string, status: string) => void;
}

const statusColors = {
  pending: "bg-gray-500",
  in_progress: "bg-blue-500",
  review: "bg-yellow-500",
  completed: "bg-green-500",
  rejected: "bg-red-500",
};

const statusLabels = {
  pending: "Pending",
  in_progress: "In Progress",
  review: "In Review",
  completed: "Completed",
  rejected: "Rejected",
};

export function DeliverableList({ deliverables, onEdit, onDelete, onStatusChange }: DeliverableListProps) {
  const sortedDeliverables = [...deliverables].sort((a, b) => (a.sortOrder || 0) - (b.sortOrder || 0));

  return (
    <Card>
      <CardHeader>
        <CardTitle>Deliverables</CardTitle>
        <CardDescription>Track specific deliverables and their completion status</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          {sortedDeliverables.map((deliverable) => {
            const isCompleted = deliverable.status === "completed";
            const isOverdue = deliverable.dueDate && new Date(deliverable.dueDate) < new Date() && !isCompleted;

            return (
              <div
                key={deliverable.id}
                className="flex items-start gap-4 p-4 rounded-lg border hover:bg-muted/50 transition-colors"
              >
                {/* Icon */}
                <div className="flex-shrink-0 mt-1">
                  {isCompleted ? (
                    <CheckCircle2 className="h-5 w-5 text-green-500" />
                  ) : isOverdue ? (
                    <Clock className="h-5 w-5 text-yellow-500" />
                  ) : (
                    <Circle className="h-5 w-5 text-muted-foreground" />
                  )}
                </div>

                {/* Content */}
                <div className="flex-1 min-w-0">
                  <div className="flex items-start justify-between gap-2">
                    <div className="flex-1">
                      <h4 className={`font-semibold ${isCompleted ? "line-through text-muted-foreground" : ""}`}>
                        {deliverable.title}
                      </h4>
                      {deliverable.description && (
                        <p className="text-sm text-muted-foreground mt-1">{deliverable.description}</p>
                      )}
                    </div>
                    <Badge className={statusColors[deliverable.status]}>{statusLabels[deliverable.status]}</Badge>
                  </div>

                  {/* Metadata */}
                  <div className="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                    {deliverable.dueDate && (
                      <div className={isOverdue ? "text-yellow-600" : ""}>
                        Due: {format(new Date(deliverable.dueDate), "MMM dd, yyyy")}
                      </div>
                    )}
                    {deliverable.deliveredAt && (
                      <div className="text-green-600">
                        Delivered: {format(new Date(deliverable.deliveredAt), "MMM dd, yyyy")}
                      </div>
                    )}
                    {deliverable.metadata?.assignedTo && <div>Assigned: {deliverable.metadata.assignedTo}</div>}
                  </div>

                  {/* Checklist items */}
                  {deliverable.metadata?.checklistItems && deliverable.metadata.checklistItems.length > 0 && (
                    <div className="mt-3 space-y-1">
                      {deliverable.metadata.checklistItems.map((item, index) => (
                        <div key={index} className="flex items-center gap-2 text-sm">
                          {item.completed ? (
                            <CheckCircle2 className="h-3 w-3 text-green-500" />
                          ) : (
                            <Circle className="h-3 w-3 text-muted-foreground" />
                          )}
                          <span className={item.completed ? "line-through text-muted-foreground" : ""}>
                            {item.text}
                          </span>
                        </div>
                      ))}
                    </div>
                  )}

                  {/* Document link */}
                  {deliverable.documentId && (
                    <div className="mt-2">
                      <Button variant="outline" size="sm" className="h-7">
                        <FileText className="h-3 w-3 mr-1" />
                        View Document
                      </Button>
                    </div>
                  )}
                </div>

                {/* Actions */}
                <div className="flex items-center gap-2">
                  {onEdit && (
                    <Button variant="ghost" size="sm" onClick={() => onEdit(deliverable)} className="h-8 w-8 p-0">
                      <Edit className="h-4 w-4" />
                    </Button>
                  )}
                  {onDelete && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => onDelete(deliverable.id)}
                      className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  )}
                </div>
              </div>
            );
          })}

          {sortedDeliverables.length === 0 && (
            <div className="text-center py-8 text-muted-foreground">
              No deliverables yet. Create one to get started.
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
