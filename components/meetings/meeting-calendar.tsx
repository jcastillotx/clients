"use client";

import { useState, useMemo } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight, Video, MapPin } from "lucide-react";
import Link from "next/link";
import {
  format,
  startOfMonth,
  endOfMonth,
  eachDayOfInterval,
  isSameMonth,
  isSameDay,
  addMonths,
  subMonths,
  isToday,
} from "date-fns";
import { cn } from "@/lib/utils";

interface Meeting {
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

interface MeetingCalendarProps {
  meetings: Meeting[];
}

export function MeetingCalendar({ meetings }: MeetingCalendarProps) {
  const [currentMonth, setCurrentMonth] = useState(new Date());

  const monthStart = startOfMonth(currentMonth);
  const monthEnd = endOfMonth(currentMonth);
  const daysInMonth = eachDayOfInterval({ start: monthStart, end: monthEnd });

  // Get the starting day of the week (0 = Sunday)
  const startingDayOfWeek = monthStart.getDay();

  // Group meetings by date
  const meetingsByDate = useMemo(() => {
    const grouped = new Map<string, Meeting[]>();

    meetings.forEach((meeting) => {
      const date = format(new Date(meeting.scheduled_at), "yyyy-MM-dd");
      if (!grouped.has(date)) {
        grouped.set(date, []);
      }
      grouped.get(date)?.push(meeting);
    });

    return grouped;
  }, [meetings]);

  const goToPreviousMonth = () => {
    setCurrentMonth((prev) => subMonths(prev, 1));
  };

  const goToNextMonth = () => {
    setCurrentMonth((prev) => addMonths(prev, 1));
  };

  const goToToday = () => {
    setCurrentMonth(new Date());
  };

  const getMeetingBadgeColor = (status: string) => {
    switch (status) {
      case "completed":
        return "bg-green-500/10 text-green-700 hover:bg-green-500/20";
      case "in_progress":
        return "bg-blue-500/10 text-blue-700 hover:bg-blue-500/20";
      case "cancelled":
        return "bg-gray-500/10 text-gray-700 hover:bg-gray-500/20";
      case "rescheduled":
        return "bg-yellow-500/10 text-yellow-700 hover:bg-yellow-500/20";
      default:
        return "bg-primary/10 text-primary hover:bg-primary/20";
    }
  };

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle>{format(currentMonth, "MMMM yyyy")}</CardTitle>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" onClick={goToToday}>
              Today
            </Button>
            <Button variant="outline" size="icon" onClick={goToPreviousMonth}>
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <Button variant="outline" size="icon" onClick={goToNextMonth}>
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-7 gap-2">
          {/* Day headers */}
          {["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((day) => (
            <div key={day} className="text-center text-sm font-semibold text-muted-foreground p-2">
              {day}
            </div>
          ))}

          {/* Empty cells for days before month starts */}
          {Array.from({ length: startingDayOfWeek }).map((_, index) => (
            <div key={`empty-${index}`} className="min-h-[120px] p-2 border rounded-md bg-muted/30" />
          ))}

          {/* Calendar days */}
          {daysInMonth.map((day) => {
            const dateStr = format(day, "yyyy-MM-dd");
            const dayMeetings = meetingsByDate.get(dateStr) || [];
            const isCurrentDay = isToday(day);

            return (
              <div
                key={dateStr}
                className={cn(
                  "min-h-[120px] p-2 border rounded-md transition-colors",
                  isCurrentDay && "border-primary bg-primary/5",
                  !isSameMonth(day, currentMonth) && "bg-muted/30 text-muted-foreground",
                )}
              >
                <div className={cn("text-sm font-medium mb-1", isCurrentDay && "text-primary")}>{format(day, "d")}</div>
                <div className="space-y-1">
                  {dayMeetings.slice(0, 2).map((meeting) => (
                    <Link key={meeting.id} href={`/meetings/${meeting.id}`}>
                      <div
                        className={cn(
                          "text-xs p-1 rounded cursor-pointer transition-colors",
                          getMeetingBadgeColor(meeting.status),
                        )}
                      >
                        <div className="font-medium truncate">{meeting.title}</div>
                        <div className="flex items-center gap-1 text-xs opacity-75">
                          {format(new Date(meeting.scheduled_at), "h:mm a")}
                          {meeting.meeting_url && <Video className="h-3 w-3" />}
                          {meeting.location && <MapPin className="h-3 w-3" />}
                        </div>
                      </div>
                    </Link>
                  ))}
                  {dayMeetings.length > 2 && (
                    <div className="text-xs text-muted-foreground text-center">+{dayMeetings.length - 2} more</div>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </CardContent>
    </Card>
  );
}
