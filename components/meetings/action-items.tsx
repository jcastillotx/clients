"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { Input } from "@/components/ui/input";

interface ActionItemsProps {
  meetingId: string;
  initialItems: any[];
  users: any[];
}

export function ActionItems({ meetingId, initialItems, users }: ActionItemsProps) {
  const [items, setItems] = useState(initialItems);
  const [newItem, setNewItem] = useState("");

  const addItem = async () => {
    if (!newItem.trim()) return;

    try {
      const response = await fetch(`/api/meetings/${meetingId}/action-items`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ description: newItem }),
      });

      if (!response.ok) throw new Error("Failed to add item");

      const item = await response.json();
      setItems([...items, item]);
      setNewItem("");
      toast.success("Action item added");
    } catch (error) {
      console.error(error);
      toast.error("Failed to add action item");
    }
  };

  const toggleItem = async (id: string, completed: boolean) => {
    try {
      const response = await fetch(`/api/meetings/${meetingId}/action-items/${id}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status: completed ? "completed" : "pending" }),
      });

      if (!response.ok) throw new Error("Failed to update item");

      setItems(items.map(i => i.id === id ? { ...i, status: completed ? "completed" : "pending" } : i));
    } catch (error) {
      console.error(error);
      toast.error("Failed to update action item");
    }
  };

  return (
    <Card>
      <CardContent className="pt-6">
        <div className="space-y-4">
          <div className="flex items-center gap-2">
            <Input 
              placeholder="Add a new action item..." 
              value={newItem}
              onChange={(e) => setNewItem(e.target.value)}
              onKeyDown={(e) => e.key === "Enter" && addItem()}
            />
            <Button onClick={addItem} size="icon">
              <Plus className="h-4 w-4" />
            </Button>
          </div>

          <div className="space-y-3">
            {items.map((item) => (
              <div key={item.id} className="flex items-start gap-3 p-2 group">
                <Checkbox 
                  checked={item.status === "completed"} 
                  onCheckedChange={(checked) => toggleItem(item.id, checked === true)}
                  className="mt-1"
                />
                <div className="flex-1">
                  <p className={`text-sm ${item.status === "completed" ? "line-through text-muted-foreground" : ""}`}>
                    {item.description}
                  </p>
                </div>
                <Button variant="ghost" size="icon" className="h-8 w-8 opacity-0 group-hover:opacity-100">
                  <Trash2 className="h-4 w-4 text-destructive" />
                </Button>
              </div>
            ))}

            {items.length === 0 && (
              <p className="text-center py-4 text-sm text-muted-foreground">No action items yet.</p>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
