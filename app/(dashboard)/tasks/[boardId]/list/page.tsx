"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import { format } from "date-fns";
import { ArrowLeft, Calendar, CheckCircle2, Circle, Loader2, User } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

const priorityColors = {
  low: "bg-slate-100 text-slate-800",
  normal: "bg-blue-100 text-blue-800",
  high: "bg-amber-100 text-amber-800",
  urgent: "bg-red-100 text-red-800",
};

export default function TaskListPage() {
  const params = useParams();
  const boardId = params.boardId as string;
  const [board, setBoard] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function fetchBoard() {
      try {
        setLoading(true);
        const response = await fetch(`/api/tasks/boards/${boardId}`);
        if (!response.ok) {
          throw new Error("Failed to fetch board");
        }
        const data = await response.json();
        setBoard(data.board ?? data.data);
      } catch (err) {
        setError(err instanceof Error ? err.message : "An error occurred");
      } finally {
        setLoading(false);
      }
    }

    fetchBoard();
  }, [boardId]);

  if (loading) {
    return (
      <div className="flex h-[calc(100vh-200px)] items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error || !board) {
    return (
      <div className="flex h-[calc(100vh-200px)] flex-col items-center justify-center">
        <p className="text-lg font-semibold text-destructive">{error || "Board not found"}</p>
      </div>
    );
  }

  const allTasks =
    board.columns?.flatMap((col: any) => col.tasks?.map((task: any) => ({ ...task, columnName: col.name })) || []) ||
    [];

  return (
    <div className="container mx-auto py-8">
      <div className="mb-8 flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button asChild variant="ghost" size="icon">
            <Link href={`/tasks/${boardId}`}>
              <ArrowLeft className="h-4 w-4" />
            </Link>
          </Button>
          <div>
            <h1 className="text-3xl font-bold tracking-tight">{board.name}</h1>
            <p className="text-muted-foreground">List view</p>
          </div>
        </div>
      </div>

      <div className="space-y-4">
        {board.columns?.map((column: any) => (
          <Card key={column.id}>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <div className="h-3 w-3 rounded-full" style={{ backgroundColor: column.color }} />
                {column.name}
                <Badge variant="secondary" className="ml-2">
                  {column.tasks?.length || 0}
                </Badge>
              </CardTitle>
            </CardHeader>
            <CardContent>
              {column.tasks?.length === 0 ? (
                <p className="py-4 text-center text-sm text-muted-foreground">No tasks in this column</p>
              ) : (
                <div className="space-y-2">
                  {column.tasks?.map((task: any) => (
                    <TaskListItem key={task.id} task={task} />
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}

function TaskListItem({ task }: { task: any }) {
  return (
    <div className="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50">
      <div className="flex items-center gap-4">
        {task.completedAt ? (
          <CheckCircle2 className="h-5 w-5 text-green-600" />
        ) : (
          <Circle className="h-5 w-5 text-muted-foreground" />
        )}
        <div className="flex-1">
          <div className="flex items-center gap-2">
            <h4 className="font-medium">{task.title}</h4>
            <Badge className={priorityColors[task.priority as keyof typeof priorityColors]}>{task.priority}</Badge>
          </div>
          {task.description && <p className="mt-1 line-clamp-1 text-sm text-muted-foreground">{task.description}</p>}
          <div className="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
            {task.dueDate && (
              <div className="flex items-center gap-1">
                <Calendar className="h-3 w-3" />
                <span>{format(new Date(task.dueDate), "MMM d, yyyy")}</span>
              </div>
            )}
            {task.assignees?.length > 0 && (
              <div className="flex items-center gap-1">
                <User className="h-3 w-3" />
                <span>{task.assignees.length} assigned</span>
              </div>
            )}
            {task.checklists?.length > 0 && (
              <span>
                {task.checklists.filter((c: any) => c.isCompleted).length}/{task.checklists.length} checklist items
              </span>
            )}
          </div>
        </div>
      </div>
      <div className="flex items-center gap-2">
        {task.assignees?.slice(0, 3).map((assignee: any) => (
          <Avatar key={assignee.userId} className="h-8 w-8">
            <AvatarImage src={assignee.user?.avatar} />
            <AvatarFallback>{assignee.user?.name?.charAt(0) || "?"}</AvatarFallback>
          </Avatar>
        ))}
        {task.assignees?.length > 3 && (
          <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-xs">
            +{task.assignees.length - 3}
          </div>
        )}
      </div>
    </div>
  );
}
