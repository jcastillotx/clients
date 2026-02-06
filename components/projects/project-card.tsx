"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Project } from "@/lib/db/schema/projects";
import { formatCurrency } from "@/lib/utils";
import { Calendar, DollarSign, Users, TrendingUp } from "lucide-react";
import Link from "next/link";

interface ProjectCardProps {
  project: Project & {
    budgetSummary?: number;
    milestonesCount?: number;
    deliverablesCount?: number;
  };
}

const statusColors = {
  planning: "bg-blue-500",
  active: "bg-green-500",
  on_hold: "bg-yellow-500",
  completed: "bg-gray-500",
  cancelled: "bg-red-500",
};

const statusLabels = {
  planning: "Planning",
  active: "Active",
  on_hold: "On Hold",
  completed: "Completed",
  cancelled: "Cancelled",
};

export function ProjectCard({ project }: ProjectCardProps) {
  const budgetPercentage = project.budgetAmount
    ? (parseFloat(project.spentAmount || "0") / parseFloat(project.budgetAmount || "0")) * 100
    : 0;

  const isOverBudget = budgetPercentage > 100;
  const priority = project.metadata?.priority || "medium";

  return (
    <Link href={`/projects/${project.id}`}>
      <Card className="hover:shadow-lg transition-shadow cursor-pointer">
        <CardHeader>
          <div className="flex justify-between items-start">
            <div className="flex-1">
              <CardTitle className="text-lg font-semibold">{project.name}</CardTitle>
              <CardDescription className="mt-1 line-clamp-2">
                {project.description || "No description provided"}
              </CardDescription>
            </div>
            <Badge className={statusColors[project.status]}>{statusLabels[project.status]}</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {/* Progress */}
            <div>
              <div className="flex justify-between text-sm mb-2">
                <span className="text-muted-foreground">Progress</span>
                <span className="font-medium">{project.progressPercent}%</span>
              </div>
              <Progress value={project.progressPercent} className="h-2" />
            </div>

            {/* Budget */}
            {project.budgetAmount && (
              <div>
                <div className="flex justify-between text-sm mb-2">
                  <span className="text-muted-foreground flex items-center gap-1">
                    <DollarSign className="h-3 w-3" />
                    Budget
                  </span>
                  <span className={`font-medium ${isOverBudget ? "text-red-600" : ""}`}>
                    {formatCurrency(parseFloat(project.spentAmount || "0"), project.currency)} /{" "}
                    {formatCurrency(parseFloat(project.budgetAmount || "0"), project.currency)}
                  </span>
                </div>
                <Progress
                  value={Math.min(budgetPercentage, 100)}
                  className={`h-2 ${isOverBudget ? "[&>div]:bg-red-500" : ""}`}
                />
              </div>
            )}

            {/* Stats */}
            <div className="grid grid-cols-2 gap-4 pt-2 border-t">
              <div className="flex items-center gap-2 text-sm">
                <TrendingUp className="h-4 w-4 text-muted-foreground" />
                <div>
                  <div className="text-muted-foreground">Milestones</div>
                  <div className="font-medium">{project.milestonesCount || 0}</div>
                </div>
              </div>
              <div className="flex items-center gap-2 text-sm">
                <Calendar className="h-4 w-4 text-muted-foreground" />
                <div>
                  <div className="text-muted-foreground">Deliverables</div>
                  <div className="font-medium">{project.deliverablesCount || 0}</div>
                </div>
              </div>
            </div>

            {/* Team Members */}
            {project.teamMembers && project.teamMembers.length > 0 && (
              <div className="flex items-center gap-2 text-sm pt-2 border-t">
                <Users className="h-4 w-4 text-muted-foreground" />
                <div className="flex-1">
                  <div className="text-muted-foreground">Team</div>
                  <div className="font-medium">{project.teamMembers.length} members</div>
                </div>
              </div>
            )}

            {/* Priority Badge */}
            {priority && (
              <div className="flex items-center gap-2">
                <Badge
                  variant={priority === "critical" ? "destructive" : priority === "high" ? "default" : "secondary"}
                >
                  {priority.charAt(0).toUpperCase() + priority.slice(1)} Priority
                </Badge>
              </div>
            )}

            {/* Dates */}
            {(project.startDate || project.endDate) && (
              <div className="text-xs text-muted-foreground pt-2 border-t">
                {project.startDate && <div>Start: {new Date(project.startDate).toLocaleDateString()}</div>}
                {project.endDate && <div>End: {new Date(project.endDate).toLocaleDateString()}</div>}
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </Link>
  );
}
