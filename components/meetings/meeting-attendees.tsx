"use client";

import { useState } from "react";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Plus, Trash2, Mail } from "lucide-react";
import { toast } from "sonner";

interface MeetingAttendeesProps {
  meetingId: string;
  attendees: any[];
  users: any[];
}

export function MeetingAttendees({ meetingId, attendees: initialAttendees, users }: MeetingAttendeesProps) {
  const [attendees, setAttendees] = useState(initialAttendees);

  const removeAttendee = async (attendeeId: string) => {
    try {
      const response = await fetch(`/api/meetings/${meetingId}/attendees/${attendeeId}`, {
        method: "DELETE",
      });

      if (!response.ok) throw new Error("Failed to remove attendee");

      setAttendees(attendees.filter(a => a.id !== attendeeId));
      toast.success("Attendee removed");
    } catch (error) {
      console.error(error);
      toast.error("Failed to remove attendee");
    }
  };

  return (
    <Card>
      <CardContent className="pt-6">
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-medium">Attendees</h3>
            <Button size="sm" variant="outline">
              <Plus className="mr-2 h-4 w-4" />
              Add Attendee
            </Button>
          </div>

          <div className="grid gap-4">
            {attendees.map((attendee) => (
              <div key={attendee.id} className="flex items-center justify-between p-2 rounded-lg border">
                <div className="flex items-center gap-3">
                  <Avatar>
                    <AvatarImage src={attendee.user?.avatar_url} />
                    <AvatarFallback>{attendee.user?.name?.charAt(0) || "U"}</AvatarFallback>
                  </Avatar>
                  <div>
                    <p className="text-sm font-medium">{attendee.user?.name}</p>
                    <p className="text-xs text-muted-foreground">{attendee.user?.email}</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Button variant="ghost" size="icon" className="h-8 w-8">
                    <Mail className="h-4 w-4 text-muted-foreground" />
                  </Button>
                  <Button 
                    variant="ghost" 
                    size="icon" 
                    className="h-8 w-8 text-destructive"
                    onClick={() => removeAttendee(attendee.id)}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            ))}

            {attendees.length === 0 && (
              <p className="text-center py-4 text-sm text-muted-foreground">No attendees added yet.</p>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
