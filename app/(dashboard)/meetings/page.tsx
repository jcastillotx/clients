import { Suspense } from "react";
import { createClient } from "@/lib/supabase/server";
import { MeetingCalendar } from "@/components/meetings/meeting-calendar";
import { MeetingList } from "@/components/meetings/meeting-list";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import Link from "next/link";
import { Plus, Calendar, List, NotebookText } from "lucide-react";
import { format } from "date-fns";

export const metadata = {
  title: "Meetings",
  description: "Manage your client meetings and schedules",
};

const VALID_TABS = ["calendar", "list", "notes"] as const;
type MeetingsTab = (typeof VALID_TABS)[number];

async function getMeetings() {
  const supabase = await createClient();

  const { data: meetings, error } = await supabase
    .from("meetings")
    .select(
      `
      *,
      client:clients(id, company_name),
      creator:users!meetings_created_by_fkey(id, name, email, avatar)
    `,
    )
    .is("deleted_at", null)
    .order("scheduled_at", { ascending: true });

  if (error) {
    console.error("Error fetching meetings:", error);
    return [];
  }

  return meetings;
}

export default async function MeetingsPage({
  searchParams,
}: {
  searchParams: Promise<{ tab?: string }>;
}) {
  const { tab } = await searchParams;
  const initialTab: MeetingsTab = (VALID_TABS as readonly string[]).includes(tab ?? "")
    ? (tab as MeetingsTab)
    : "calendar";
  const meetings = await getMeetings();
  const meetingsWithNotes = meetings.filter(
    (m) => typeof m.notes === "string" && m.notes.trim().length > 0,
  );

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Meetings</h1>
          <p className="text-muted-foreground">Schedule and manage client meetings</p>
        </div>
        <Button asChild>
          <Link href="/meetings/new">
            <Plus className="mr-2 h-4 w-4" />
            Schedule Meeting
          </Link>
        </Button>
      </div>

      <Tabs defaultValue={initialTab} className="space-y-4">
        <TabsList>
          <TabsTrigger value="calendar">
            <Calendar className="mr-2 h-4 w-4" />
            Calendar View
          </TabsTrigger>
          <TabsTrigger value="list">
            <List className="mr-2 h-4 w-4" />
            List View
          </TabsTrigger>
          <TabsTrigger value="notes">
            <NotebookText className="mr-2 h-4 w-4" />
            Notes ({meetingsWithNotes.length})
          </TabsTrigger>
        </TabsList>

        <TabsContent value="calendar" className="space-y-4">
          <Suspense fallback={<div>Loading calendar...</div>}>
            <MeetingCalendar meetings={meetings} />
          </Suspense>
        </TabsContent>

        <TabsContent value="list" className="space-y-4">
          <Suspense fallback={<div>Loading meetings...</div>}>
            <MeetingList meetings={meetings} />
          </Suspense>
        </TabsContent>

        <TabsContent value="notes" className="space-y-4">
          {meetingsWithNotes.length === 0 ? (
            <Card>
              <CardContent className="flex flex-col items-center justify-center py-12">
                <NotebookText className="mb-4 h-12 w-12 text-muted-foreground" />
                <h3 className="mb-1 text-lg font-semibold">No meeting notes yet</h3>
                <p className="text-center text-sm text-muted-foreground">
                  Notes added to meetings will appear here.
                </p>
              </CardContent>
            </Card>
          ) : (
            <div className="space-y-4">
              {meetingsWithNotes.map((meeting) => (
                <Card key={meeting.id}>
                  <CardContent className="space-y-3 p-6">
                    <div className="flex items-start justify-between gap-4">
                      <div>
                        <Link
                          href={`/meetings/${meeting.id}`}
                          className="text-lg font-semibold hover:underline"
                        >
                          {meeting.title}
                        </Link>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                          {meeting.client?.company_name ?? "Internal"} ·{" "}
                          {meeting.scheduled_at
                            ? format(new Date(meeting.scheduled_at), "MMM d, yyyy 'at' h:mm a")
                            : "Unscheduled"}
                        </p>
                      </div>
                      <Badge variant="outline" className="capitalize">
                        {meeting.status}
                      </Badge>
                    </div>
                    <p className="whitespace-pre-wrap text-sm text-muted-foreground line-clamp-6">
                      {meeting.notes}
                    </p>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
}
