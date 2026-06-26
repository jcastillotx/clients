"use client";

import { useState, useMemo } from "react";
import { Plus, Settings, List } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { KanbanColumn } from "./kanban-column";
import { TaskDetailModal } from "./task-detail-modal";
import { CreateTaskDialog } from "./create-task-dialog";

interface KanbanBoardProps {
  board: any;
  onUpdate: (board: any) => void;
}

export function KanbanBoard({ board, onUpdate }: KanbanBoardProps) {
  const [selectedTask, setSelectedTask] = useState<any>(null);
  const [createTaskColumn, setCreateTaskColumn] = useState<string | null>(null);
  const [draggedTask, setDraggedTask] = useState<any>(null);

  const refreshBoard = async () => {
    const boardResponse = await fetch(`/api/tasks/boards/${board.id}`);
    const boardData = await boardResponse.json();
    onUpdate(boardData.board ?? boardData.data);
  };

  const handleTaskMove = async (taskId: string, newColumnId: string, newPosition: number) => {
    try {
      const response = await fetch(`/api/tasks/${taskId}/move`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ columnId: newColumnId, position: newPosition }),
      });

      if (!response.ok) {
        throw new Error("Failed to move task");
      }

      await refreshBoard();
    } catch (error) {
      console.error("Error moving task:", error);
    }
  };

  const handleDragStart = (task: any) => {
    setDraggedTask(task);
  };

  const handleDragEnd = () => {
    setDraggedTask(null);
  };

  const handleDrop = async (columnId: string, position: number) => {
    if (!draggedTask) return;

    await handleTaskMove(draggedTask.id, columnId, position);
    setDraggedTask(null);
  };

  const handleTaskUpdate = async (taskId: string) => {
    await refreshBoard();

    // Update selected task if it's the one being viewed
    if (selectedTask?.id === taskId) {
      const taskResponse = await fetch(`/api/tasks/${taskId}`);
      const taskData = await taskResponse.json();
      setSelectedTask(taskData.task ?? taskData.data);
    }
  };

  const handleTaskDeleted = async () => {
    setSelectedTask(null);
    await refreshBoard();
  };

  const columns = useMemo(() => {
    return board.columns || [];
  }, [board.columns]);

  return (
    <div className="flex h-full flex-col">
      <div className="mb-4 flex items-center justify-between border-b bg-background pb-4">
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-2">
            <div className="h-4 w-4 rounded-full" style={{ backgroundColor: board.color }} />
            <h1 className="text-2xl font-bold">{board.name}</h1>
          </div>
          {board.description && <p className="text-sm text-muted-foreground">{board.description}</p>}
        </div>
        <div className="flex items-center gap-2">
          <Button asChild variant="outline" size="sm">
            <Link href={`/tasks/${board.id}/list`}>
              <List className="mr-2 h-4 w-4" />
              List View
            </Link>
          </Button>
          <Button variant="outline" size="sm">
            <Settings className="mr-2 h-4 w-4" />
            Settings
          </Button>
        </div>
      </div>

      <div className="flex-1 overflow-x-auto">
        <div className="flex h-full gap-4 pb-4">
          {columns.map((column: any) => (
            <KanbanColumn
              key={column.id}
              column={column}
              board={board}
              onTaskClick={setSelectedTask}
              onCreateTask={() => setCreateTaskColumn(column.id)}
              onDrop={handleDrop}
              onDragStart={handleDragStart}
              onDragEnd={handleDragEnd}
              isDragging={!!draggedTask}
            />
          ))}

          {columns.length === 0 && (
            <div className="flex w-full items-center justify-center">
              <div className="text-center">
                <p className="text-lg font-semibold text-muted-foreground">No columns yet</p>
                <p className="mt-2 text-sm text-muted-foreground">Add columns to start organizing tasks</p>
                <Button className="mt-4" variant="outline">
                  <Plus className="mr-2 h-4 w-4" />
                  Add Column
                </Button>
              </div>
            </div>
          )}
        </div>
      </div>

      {selectedTask && (
        <TaskDetailModal
          task={selectedTask}
          open={!!selectedTask}
          onOpenChange={(open) => !open && setSelectedTask(null)}
          onUpdate={handleTaskUpdate}
          onDeleted={handleTaskDeleted}
        />
      )}

      {createTaskColumn && (
        <CreateTaskDialog
          boardId={board.id}
          columnId={createTaskColumn}
          open={!!createTaskColumn}
          onOpenChange={(open) => !open && setCreateTaskColumn(null)}
          onSuccess={() => {
            setCreateTaskColumn(null);
            handleTaskUpdate("");
          }}
        />
      )}
    </div>
  );
}
