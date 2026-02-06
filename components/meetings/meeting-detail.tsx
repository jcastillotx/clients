"use client";

import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { MeetingNotesEditor } from "./meeting-notes-editor";
import { MeetingAttendees } from "./meeting-attendees";
import { ActionItems } from "./action-items";
import {
  Calendar,
  Clock,
  MapPin,
  Video,
  Building2,
  User,
  FileText,
  CheckSquare,
  Users,
  Edit,
  Trash2,
  Download,
} from "lucide-react";
import { format } from "date-fns";
import { cn } from "@/lib/utils";
import Link from "next/link";
import { useRouter } from "next/navigation";

interface MeetingDetailProps {
  meeting: any;
  users: Array<{
    id: string;
    name: string;
    email: string;
    avatar?: string;
  }>;
}

export function MeetingDetail({ meeting, users }: MeetingDetailProps) {
  const router = useRouter();
  const [isDeleting, setIsDeleting] = useState(false);

  const getStatusColor = (status: string) => {
    switch (status) {
      case "completed":
        return "bg-green-500/10 text-green-700";
      case "in_progress":
        return "bg-blue-500/10 text-blue-700";
      case "cancelled":
        return "bg-gray-500/10 text-gray-700";
      case "rescheduled":
        return "bg-yellow-500/10 text-yellow-700";
      default:
        return "bg-primary/10 text-primary";
    }
  };

  const getMeetingTypeLabel = (type: string) => {
    return type
      .split("_")
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(" ");
  };

  const handleDelete = async () => {
    if (!confirm("Are you sure you want to delete this meeting?")) {
      return;
    }

    setIsDeleting(true);

    try {
      const response = await fetch(`/api/meetings/${meeting.id}`, {
        method: "DELETE",
      });

      if (!response.ok) {
        throw new Error("Failed to delete meeting");
      }

      router.push("/meetings");
    } catch (error) {
      console.error("Error deleting meeting:", error);
      alert("Failed to delete meeting. Please try again.");
      setIsDeleting(false);
    }
  };

  const handleStatusChange = async (newStatus: string) => {
    try {
      const response = await fetch(`/api/meetings/${meeting.id}`, {
        method: "PATCH",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ status: newStatus }),
      });

      if (!response.ok) {
        throw new Error("Failed to update status");
      }

      router.refresh();
    } catch (error) {
      console.error("Error updating status:", error);
      alert("Failed to update meeting status. Please try again.");
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div className="space-y-2">
          <div className="flex items-center gap-3">
            <h1 className="text-3xl font-bold tracking-tight">{meeting.title}</h1>
            <Badge className={cn(getStatusColor(meeting.status))}>{meeting.status.replace("_", " ")}</Badge>
            <Badge variant="outline">{getMeetingTypeLabel(meeting.meeting_type)}</Badge>
          </div>
          <div className="flex items-center gap-4 text-muted-foreground">
            <div className="flex items-center gap-2">
              <Building2 className="h-4 w-4" />
              <Link href={`/clients/${meeting.client.id}`} className="hover:underline">
                {meeting.client.company_name}
              </Link>
            </div>
            <div className="flex items-center gap-2">
              <User className="h-4 w-4" />
              <span>{meeting.creator.name}</span>
            </div>
          </div>
        </div>

        <div className="flex gap-2">
          <Button variant="outline" asChild>
            <Link href={`/meetings/${meeting.id}/edit`}>
              <Edit className="mr-2 h-4 w-4" />
              Edit
            </Link>
          </Button>
          <Button variant="outline" onClick={handleDelete} disabled={isDeleting}>
            <Trash2 className="mr-2 h-4 w-4" />
            Delete
          </Button>
        </div>
      </div>

      {/* Meeting Info */}
      <Card>
        <CardHeader>
          <CardTitle>Meeting Details</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {meeting.description && (
            <div>
              <p className="text-muted-foreground">{meeting.description}</p>
            </div>
          )}

          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="space-y-1">
              <div className="flex items-center gap-2 text-muted-foreground">
                <Calendar className="h-4 w-4" />
                <span className="text-sm">Date</span>
              </div>
              <p className="font-medium">{format(new Date(meeting.scheduled_at), "MMMM d, yyyy")}</p>
            </div>

            <div className="space-y-1">
              <div className="flex items-center gap-2 text-muted-foreground">
                <Clock className="h-4 w-4" />
                <span className="text-sm">Time</span>
              </div>
              <p className="font-medium">
                {format(new Date(meeting.scheduled_at), "h:mm a")} ({meeting.duration_minutes} min)
              </p>
            </div>

            {meeting.meeting_url && (
              <div className="space-y-1">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <Video className="h-4 w-4" />
                  <span className="text-sm">Video Call</span>
                </div>
                <a
                  href={meeting.meeting_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="font-medium text-primary hover:underline"
                >
                  Join Meeting
                </a>
              </div>
            )}

            {meeting.location && (
              <div className="space-y-1">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <MapPin className="h-4 w-4" />
                  <span className="text-sm">Location</span>
                </div>
                <p className="font-medium">{meeting.location}</p>
              </div>
            )}
          </div>

          {meeting.agenda && (
            <div className="space-y-2">
              <h4 className="font-semibold">Agenda</h4>
              <div className="prose prose-sm max-w-none">
                <pre className="whitespace-pre-wrap font-sans text-sm">{meeting.agenda}</pre>
              </div>
            </div>
          )}

          {meeting.recording_url && (
            <div className="space-y-2">
              <h4 className="font-semibold">Recording</h4>
              <a
                href={meeting.recording_url}
                target="_blank"
                rel="noopener noreferrer"
                className="text-primary hover:underline flex items-center gap-2"
              >
                <Download className="h-4 w-4" />
                Download Recording
              </a>
            </div>
          )}

          {/* Quick Status Actions */}
          <div className="pt-4 border-t">
            <div className="flex gap-2">
              {meeting.status === "scheduled" && (
                <>
                  <Button size="sm" onClick={() => handleStatusChange("in_progress")}>
                    Start Meeting
                  </Button>
                  <Button size="sm" variant="outline" onClick={() => handleStatusChange("cancelled")}>
                    Cancel
                  </Button>
                </>
              )}
              {meeting.status === "in_progress" && (
                <Button size="sm" onClick={() => handleStatusChange("completed")}>
                  Complete Meeting
                </Button>
              )}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Tabs for Notes, Attendees, Action Items */}
      <Tabs defaultValue="notes" className="space-y-4">
        <TabsList>
          <TabsTrigger value="notes">
            <FileText className="mr-2 h-4 w-4" />
            Notes
          </TabsTrigger>
          <TabsTrigger value="attendees">
            <Users className="mr-2 h-4 w-4" />
            Attendees ({meeting.attendeeRecords?.length || 0})
          </TabsTrigger>
          <TabsTrigger value="actions">
            <CheckSquare className="mr-2 h-4 w-4" />
            Action Items ({meeting.action_items?.length || 0})
          </TabsTrigger>
        </TabsList>

        <TabsContent value="notes">
          <MeetingNotesEditor meetingId={meeting.id} initialNotes={meeting.notes || []} />
        </TabsContent>

        <TabsContent value="attendees">
          <MeetingAttendees meetingId={meeting.id} attendees={meeting.attendeeRecords || []} users={users} />
        </TabsContent>

        <TabsContent value="actions">
          <ActionItems meetingId={meeting.id} initialItems={meeting.action_items || []} users={users} />
        </TabsContent>
      </Tabs>
    </div>
  );
}
