"use client";

import { Suspense } from "react";
import { Calendar, List } from "lucide-react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { MeetingCalendar } from "@/components/meetings/meeting-calendar";
import { MeetingList } from "@/components/meetings/meeting-list";

interface MeetingRow {
  id: string;
  title: string;
  meeting_type: string;
  scheduled_at: string;
  duration_minutes: number;
  location?: string;
  meeting_url?: string;
  status: string;
  client: {
    company_name: string;
  };
}

interface MeetingsTabsProps {
  meetings: MeetingRow[];
  defaultTab: "calendar" | "list";
}

export function MeetingsTabs({ meetings, defaultTab }: MeetingsTabsProps) {
  return (
    <Tabs defaultValue={defaultTab} className="space-y-4">
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
  );
}
