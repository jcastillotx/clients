"use client";

import { useCallback, useEffect, useState } from "react";
import { Loader2, RefreshCw } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { MeetingCalendar } from "@/components/meetings/meeting-calendar";
import { fetchApi } from "@/lib/api/client";

interface CalendarMeeting {
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

interface ProjectRequestCalendarProps {
  requestId: string;
}

export function ProjectRequestCalendar({ requestId }: ProjectRequestCalendarProps) {
  const [meetings, setMeetings] = useState<CalendarMeeting[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await fetchApi<CalendarMeeting[]>(
        `/api/projects/requests/${requestId}/calendar`,
        { method: "GET", cache: "no-store" },
        { fallbackMessage: "Failed to fetch calendar" },
      );
      setMeetings(Array.isArray(data) ? data : []);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Failed to fetch calendar");
    } finally {
      setLoading(false);
    }
  }, [requestId]);

  useEffect(() => {
    void load();
  }, [load]);

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Calendar</CardTitle>
          <CardDescription>Meetings and milestone sessions tied to this project request.</CardDescription>
        </div>
        <Button variant="outline" size="sm" onClick={() => void load()} disabled={loading}>
          {loading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <RefreshCw className="mr-2 h-4 w-4" />}
          Refresh
        </Button>
      </CardHeader>
      <CardContent>
        {error ? <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</div> : null}

        {loading ? (
          <div className="flex items-center justify-center py-10">
            <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
          </div>
        ) : meetings.length === 0 ? (
          <div className="rounded-md border border-dashed py-10 text-center text-sm text-muted-foreground">
            No meetings are linked yet. Schedule meetings and they will appear here.
          </div>
        ) : (
          <MeetingCalendar meetings={meetings} />
        )}
      </CardContent>
    </Card>
  );
}
