"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { KanbanBoard } from "@/components/tasks/kanban-board";
import { Loader2 } from "lucide-react";

export default function BoardPage() {
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
        setBoard(data.board);
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
        <p className="mt-2 text-sm text-muted-foreground">Please check the URL and try again</p>
      </div>
    );
  }

  return (
    <div className="h-[calc(100vh-120px)]">
      <KanbanBoard board={board} onUpdate={setBoard} />
    </div>
  );
}
