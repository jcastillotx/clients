import { Suspense } from "react";
import Link from "next/link";
import { desc, eq, inArray, sql } from "drizzle-orm";
import { Plus, Layout, Archive } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { db } from "@/lib/db";
import { staffTaskBoards, staffTasks } from "@/lib/db/schema/staff-tasks";

async function getBoards() {
  const boards = await db.query.staffTaskBoards.findMany({
    where: eq(staffTaskBoards.isArchived, false),
    orderBy: [desc(staffTaskBoards.isDefault), desc(staffTaskBoards.sortOrder)],
    with: {
      columns: true,
    },
  });

  if (boards.length === 0) {
    return [];
  }

  const boardIds = boards.map((board) => board.id);
  const taskCounts = await db
    .select({
      boardId: staffTasks.boardId,
      count: sql<number>`count(*)::int`,
    })
    .from(staffTasks)
    .where(inArray(staffTasks.boardId, boardIds))
    .groupBy(staffTasks.boardId);

  const countByBoard = new Map(taskCounts.map((row) => [row.boardId, row.count]));

  return boards.map((board) => ({
    id: board.id,
    name: board.name,
    description: board.description,
    color: board.color,
    isDefault: board.isDefault,
    _count: {
      columns: board.columns?.length ?? 0,
      tasks: countByBoard.get(board.id) ?? 0,
    },
  }));
}

export default async function TasksPage() {
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

function BoardsList({
  boards,
}: {
  boards: Array<{
    id: string;
    name: string;
    description: string | null;
    color: string | null;
    isDefault: boolean;
    _count: { columns: number; tasks: number };
  }>;
}) {
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
                <div className="h-3 w-3 rounded-full" style={{ backgroundColor: board.color ?? "#3b82f6" }} />
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
                <span>{board._count.columns} columns</span>
              </div>
              <div className="flex items-center gap-1">
                <span>{board._count.tasks} tasks</span>
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
