"use client";

import { useCallback, useEffect, useState } from "react";
import { format } from "date-fns";
import { Loader2, RefreshCw } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { fetchApi } from "@/lib/api/client";

interface TaskAssignee {
  id: string;
  name: string;
  avatar?: string | null;
}

interface ProjectRequestTask {
  id: string;
  title: string;
  description: string | null;
  priority: "low" | "normal" | "high" | "urgent";
  due_date: string | null;
  progress: number;
  completed_at: string | null;
  columnName: string;
  assignees: TaskAssignee[];
}

interface ProjectRequestTasksProps {
  requestId: string;
}

const priorityVariant = (priority: ProjectRequestTask["priority"]) => {
  switch (priority) {
    case "urgent":
      return "destructive" as const;
    case "high":
      return "default" as const;
    default:
      return "secondary" as const;
  }
};

export function ProjectRequestTasks({ requestId }: ProjectRequestTasksProps) {
  const [tasks, setTasks] = useState<ProjectRequestTask[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await fetchApi<ProjectRequestTask[]>(
        `/api/projects/requests/${requestId}/tasks`,
        { method: "GET", cache: "no-store" },
        { fallbackMessage: "Failed to fetch tasks" },
      );
      setTasks(Array.isArray(data) ? data : []);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Failed to fetch tasks");
    } finally {
      setLoading(false);
    }
  }, [requestId]);

  useEffect(() => {
    void load();
  }, [load]);

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Tasks (AJAX)</CardTitle>
          <CardDescription>Real-time task feed tied to this project request.</CardDescription>
        </div>
        <Button variant="outline" size="sm" onClick={() => void load()} disabled={loading}>
          {loading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <RefreshCw className="mr-2 h-4 w-4" />}
          Refresh
        </Button>
      </CardHeader>
      <CardContent>
        {error ? <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</div> : null}

        {loading ? (
          <div className="flex items-center justify-center py-10">
            <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
          </div>
        ) : tasks.length === 0 ? (
          <div className="rounded-md border border-dashed py-10 text-center text-sm text-muted-foreground">
            No linked tasks yet. As work starts, request-related tasks will appear here.
          </div>
        ) : (
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Task</TableHead>
                  <TableHead>Column</TableHead>
                  <TableHead>Priority</TableHead>
                  <TableHead>Progress</TableHead>
                  <TableHead>Due Date</TableHead>
                  <TableHead>Assignees</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {tasks.map((task) => (
                  <TableRow key={task.id}>
                    <TableCell>
                      <div>
                        <div className="font-medium">{task.title}</div>
                        {task.description ? <p className="line-clamp-1 text-xs text-muted-foreground">{task.description}</p> : null}
                      </div>
                    </TableCell>
                    <TableCell>{task.columnName}</TableCell>
                    <TableCell>
                      <Badge variant={priorityVariant(task.priority)}>{task.priority}</Badge>
                    </TableCell>
                    <TableCell>{task.progress}%</TableCell>
                    <TableCell>{task.due_date ? format(new Date(task.due_date), "MMM d, yyyy") : "—"}</TableCell>
                    <TableCell>
                      <div className="flex items-center gap-1">
                        {task.assignees.slice(0, 3).map((assignee) => (
                          <Avatar key={assignee.id} className="h-7 w-7">
                            <AvatarImage src={assignee.avatar || undefined} />
                            <AvatarFallback>{assignee.name.slice(0, 1).toUpperCase()}</AvatarFallback>
                          </Avatar>
                        ))}
                        {task.assignees.length > 3 ? (
                          <span className="text-xs text-muted-foreground">+{task.assignees.length - 3}</span>
                        ) : null}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
