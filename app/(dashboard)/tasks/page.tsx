import { Suspense } from "react";
import Link from "next/link";
import { Plus, Layout, Archive, CalendarClock } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { db } from "@/lib/db";
import {
  staffTasks,
  staffTaskAssignees,
  staffTaskBoards,
  staffTaskColumns,
} from "@/lib/db/schema/staff-tasks";
import { and, asc, eq, isNull, ne } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { format, isPast } from "date-fns";

async function getBoards() {
  // This would normally fetch from API
  // For now, returning empty array for SSR
  return [];
}

async function getMyTasks(userId: string) {
  return db
    .select({
      id: staffTasks.id,
      title: staffTasks.title,
      priority: staffTasks.priority,
      dueDate: staffTasks.dueDate,
      progress: staffTasks.progress,
      completedAt: staffTasks.completedAt,
      boardId: staffTasks.boardId,
      boardName: staffTaskBoards.name,
      columnName: staffTaskColumns.name,
      isDoneColumn: staffTaskColumns.isDoneColumn,
    })
    .from(staffTasks)
    .innerJoin(staffTaskAssignees, eq(staffTaskAssignees.taskId, staffTasks.id))
    .innerJoin(staffTaskBoards, eq(staffTaskBoards.id, staffTasks.boardId))
    .innerJoin(staffTaskColumns, eq(staffTaskColumns.id, staffTasks.columnId))
    .where(
      and(
        eq(staffTaskAssignees.userId, userId),
        isNull(staffTasks.completedAt),
        ne(staffTaskBoards.isArchived, true),
      ),
    )
    .orderBy(asc(staffTasks.dueDate));
}

const PRIORITY_VARIANT: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
  low: "outline",
  normal: "secondary",
  high: "default",
  urgent: "destructive",
};

export default async function TasksPage({
  searchParams,
}: {
  searchParams: Promise<{ assignee?: string }>;
}) {
  const { assignee } = await searchParams;
  if (assignee === "me") return <MyTasksView />;

  const boards = await getBoards();

  return (
    <div className="container mx-auto py-8">
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Task Boards</h1>
          <p className="text-muted-foreground">Manage your projects with Kanban boards</p>
        </div>
        <Button asChild>
          <Link href="/tasks/new">
            <Plus className="mr-2 h-4 w-4" />
            New Board
          </Link>
        </Button>
      </div>

      <Suspense fallback={<BoardsLoadingSkeleton />}>
        <BoardsList boards={boards} />
      </Suspense>
    </div>
  );
}

function BoardsList({ boards }: { boards: any[] }) {
  if (boards.length === 0) {
    return (
      <Card>
        <CardContent className="flex flex-col items-center justify-center py-12">
          <Layout className="mb-4 h-12 w-12 text-muted-foreground" />
          <h3 className="mb-2 text-lg font-semibold">No boards yet</h3>
          <p className="mb-4 text-center text-sm text-muted-foreground">
            Create your first board to start organizing tasks
          </p>
          <Button asChild>
            <Link href="/tasks/new">
              <Plus className="mr-2 h-4 w-4" />
              Create Board
            </Link>
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      {boards.map((board) => (
        <Card key={board.id} className="transition-shadow hover:shadow-lg">
          <CardHeader>
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-2">
                <div className="h-3 w-3 rounded-full" style={{ backgroundColor: board.color }} />
                <CardTitle className="text-xl">{board.name}</CardTitle>
              </div>
              {board.isDefault && (
                <span className="rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary">Default</span>
              )}
            </div>
            {board.description && <CardDescription className="line-clamp-2">{board.description}</CardDescription>}
          </CardHeader>
          <CardContent>
            <div className="mb-4 flex items-center gap-4 text-sm text-muted-foreground">
              <div className="flex items-center gap-1">
                <Layout className="h-4 w-4" />
                <span>{board._count?.columns || 0} columns</span>
              </div>
              <div className="flex items-center gap-1">
                <span>{board._count?.tasks || 0} tasks</span>
              </div>
            </div>
            <div className="flex gap-2">
              <Button asChild className="flex-1" variant="default">
                <Link href={`/tasks/${board.id}`}>Open Board</Link>
              </Button>
              <Button asChild variant="outline" size="icon">
                <Link href={`/tasks/${board.id}/list`}>
                  <Archive className="h-4 w-4" />
                </Link>
              </Button>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function BoardsLoadingSkeleton() {
  return (
    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      {[1, 2, 3].map((i) => (
        <Card key={i} className="animate-pulse">
          <CardHeader>
            <div className="h-6 w-3/4 rounded bg-muted" />
            <div className="h-4 w-full rounded bg-muted" />
          </CardHeader>
          <CardContent>
            <div className="mb-4 flex gap-4">
              <div className="h-4 w-20 rounded bg-muted" />
              <div className="h-4 w-20 rounded bg-muted" />
            </div>
            <div className="h-10 w-full rounded bg-muted" />
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

async function MyTasksView() {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) {
    return (
      <div className="container mx-auto py-8">
        <p className="text-muted-foreground">Sign in to see your tasks.</p>
      </div>
    );
  }

  const tasks = await getMyTasks(user.id);

  return (
    <div className="container mx-auto py-8">
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">My Tasks</h1>
          <p className="text-muted-foreground">
            Open tasks assigned to you, across every board.
          </p>
        </div>
        <Button asChild variant="outline">
          <Link href="/tasks">
            <Layout className="mr-2 h-4 w-4" />
            All boards
          </Link>
        </Button>
      </div>

      {tasks.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <CalendarClock className="mb-4 h-12 w-12 text-muted-foreground" />
            <h3 className="mb-2 text-lg font-semibold">You're all clear</h3>
            <p className="text-center text-sm text-muted-foreground">
              No open tasks are assigned to you right now.
            </p>
          </CardContent>
        </Card>
      ) : (
        <Card>
          <CardContent className="p-0">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-xs uppercase text-muted-foreground">
                  <th className="px-4 py-3 font-medium">Task</th>
                  <th className="px-4 py-3 font-medium">Board</th>
                  <th className="px-4 py-3 font-medium">Status</th>
                  <th className="px-4 py-3 font-medium">Priority</th>
                  <th className="px-4 py-3 font-medium">Due</th>
                </tr>
              </thead>
              <tbody>
                {tasks.map((t) => {
                  const overdue = t.dueDate && isPast(new Date(t.dueDate));
                  return (
                    <tr key={t.id} className="border-b last:border-0 hover:bg-muted/40">
                      <td className="px-4 py-3 font-medium">
                        <Link href={`/tasks/${t.boardId}`} className="hover:underline">
                          {t.title}
                        </Link>
                      </td>
                      <td className="px-4 py-3 text-muted-foreground">{t.boardName}</td>
                      <td className="px-4 py-3 text-muted-foreground">{t.columnName}</td>
                      <td className="px-4 py-3">
                        <Badge
                          variant={PRIORITY_VARIANT[t.priority] ?? "secondary"}
                          className="capitalize"
                        >
                          {t.priority}
                        </Badge>
                      </td>
                      <td
                        className={`px-4 py-3 tabular-nums ${overdue ? "text-destructive" : "text-muted-foreground"}`}
                      >
                        {t.dueDate ? format(new Date(t.dueDate), "MMM d, yyyy") : "—"}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
