import { Suspense } from "react";
import { createClient } from "@/lib/supabase/server";
import { MeetingCalendar } from "@/components/meetings/meeting-calendar";
import { MeetingList } from "@/components/meetings/meeting-list";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import Link from "next/link";
import { Plus, Calendar, List } from "lucide-react";

export const metadata = {
  title: "Meetings",
  description: "Manage your client meetings and schedules",
};

async function getMeetings() {
  const supabase = createClient();

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

export default async function MeetingsPage() {
  const meetings = await getMeetings();

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

      <Tabs defaultValue="calendar" className="space-y-4">
        <TabsList>
          <TabsTrigger value="calendar">
            <Calendar className="mr-2 h-4 w-4" />
            Calendar View
          </TabsTrigger>
          <TabsTrigger value="list">
            <List className="mr-2 h-4 w-4" />
            List View
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
      </Tabs>
    </div>
  );
}
