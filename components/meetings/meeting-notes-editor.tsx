"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardFooter } from "@/components/ui/card";
import { toast } from "sonner";
import { Loader2, Save } from "lucide-react";

interface MeetingNotesEditorProps {
  meetingId: string;
  initialNotes?: any[];
}

export function MeetingNotesEditor({ meetingId, initialNotes = [] }: MeetingNotesEditorProps) {
  const [notes, setNotes] = useState(
    initialNotes.length > 0 ? initialNotes.map(n => n.content).join("\n\n") : ""
  );
  const [isSaving, setIsSaving] = useState(false);

  const handleSave = async () => {
    setIsSaving(true);
    try {
      const response = await fetch(`/api/meetings/${meetingId}/notes`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ content: notes }),
      });

      if (!response.ok) {
        throw new Error("Failed to save notes");
      }

      toast.success("Notes saved successfully");
    } catch (error) {
      console.error("Error saving notes:", error);
      toast.error("Failed to save notes");
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <Card>
      <CardContent className="pt-6">
        <Textarea
          placeholder="Type your meeting notes here..."
          className="min-height-[400px] resize-none focus-visible:ring-0 border-none shadow-none text-base"
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          rows={20}
        />
      </CardContent>
      <CardFooter className="flex justify-end border-t pt-4">
        <Button onClick={handleSave} disabled={isSaving}>
          {isSaving ? (
            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
          ) : (
            <Save className="mr-2 h-4 w-4" />
          )}
          Save Notes
        </Button>
      </CardFooter>
    </Card>
  );
}
