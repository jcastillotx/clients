"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { ArrowLeft, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { fetchApi } from "@/lib/api/client";

const BOARD_COLORS = [
  "#3b82f6", "#10b981", "#f59e0b", "#ef4444", "#8b5cf6",
  "#ec4899", "#06b6d4", "#84cc16", "#f97316", "#6366f1",
];

export default function NewBoardPage() {
  const router = useRouter();
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [color, setColor] = useState(BOARD_COLORS[0]);
  const [isDefault, setIsDefault] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!name.trim()) {
      setError("Board name is required");
      return;
    }

    try {
      setSubmitting(true);
      setError(null);

      const board = await fetchApi<{ id: string }>(
        "/api/tasks/boards",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ name: name.trim(), description: description.trim(), color, isDefault }),
        },
        { fallbackMessage: "Failed to create board" },
      );
      router.push(`/tasks/${board.id}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : "An error occurred");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="container mx-auto max-w-2xl py-8">
      <div className="mb-6">
        <Button variant="ghost" size="sm" asChild>
          <Link href="/tasks">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Boards
          </Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Create New Board</CardTitle>
          <CardDescription>Set up a new Kanban board to organize your tasks</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-6">
            {error && (
              <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                {error}
              </div>
            )}

            <div className="space-y-2">
              <Label htmlFor="name">Board Name *</Label>
              <Input
                id="name"
                placeholder="e.g., Sprint Board, Project Tasks"
                value={name}
                onChange={(e) => setName(e.target.value)}
                disabled={submitting}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                placeholder="Describe the purpose of this board..."
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                disabled={submitting}
                rows={3}
              />
            </div>

            <div className="space-y-2">
              <Label>Board Color</Label>
              <div className="flex flex-wrap gap-2">
                {BOARD_COLORS.map((c) => (
                  <button
                    key={c}
                    type="button"
                    className={`h-8 w-8 rounded-full border-2 transition-transform ${
                      color === c ? "scale-110 border-foreground" : "border-transparent"
                    }`}
                    style={{ backgroundColor: c }}
                    onClick={() => setColor(c)}
                    disabled={submitting}
                  />
                ))}
              </div>
            </div>

            <div className="flex items-center justify-between rounded-lg border p-4">
              <div className="space-y-0.5">
                <Label htmlFor="default">Set as Default Board</Label>
                <p className="text-sm text-muted-foreground">
                  This board will be shown first when opening tasks
                </p>
              </div>
              <Switch
                id="default"
                checked={isDefault}
                onCheckedChange={setIsDefault}
                disabled={submitting}
              />
            </div>

            <div className="flex gap-3 pt-2">
              <Button type="submit" disabled={submitting}>
                {submitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                Create Board
              </Button>
              <Button type="button" variant="outline" onClick={() => router.push("/tasks")} disabled={submitting}>
                Cancel
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
