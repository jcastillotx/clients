"use client";

import { useState, useMemo } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Calendar, Clock, MapPin, Video, Building2, Search } from "lucide-react";
import Link from "next/link";
import { format } from "date-fns";
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

interface MeetingListProps {
  meetings: Meeting[];
}

export function MeetingList({ meetings }: MeetingListProps) {
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [typeFilter, setTypeFilter] = useState<string>("all");

  const filteredMeetings = useMemo(() => {
    return meetings.filter((meeting) => {
      const matchesSearch =
        meeting.title.toLowerCase().includes(search.toLowerCase()) ||
        meeting.client.company_name.toLowerCase().includes(search.toLowerCase());

      const matchesStatus = statusFilter === "all" || meeting.status === statusFilter;
      const matchesType = typeFilter === "all" || meeting.meeting_type === typeFilter;

      return matchesSearch && matchesStatus && matchesType;
    });
  }, [meetings, search, statusFilter, typeFilter]);

  const getStatusColor = (status: string) => {
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

  const getMeetingTypeLabel = (type: string) => {
    return type
      .split("_")
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(" ");
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>All Meetings</CardTitle>
        <div className="flex gap-4 mt-4">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              placeholder="Search meetings..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-9"
            />
          </div>
          <Select value={statusFilter} onValueChange={setStatusFilter}>
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Status</SelectItem>
              <SelectItem value="scheduled">Scheduled</SelectItem>
              <SelectItem value="in_progress">In Progress</SelectItem>
              <SelectItem value="completed">Completed</SelectItem>
              <SelectItem value="cancelled">Cancelled</SelectItem>
              <SelectItem value="rescheduled">Rescheduled</SelectItem>
            </SelectContent>
          </Select>
          <Select value={typeFilter} onValueChange={setTypeFilter}>
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="Type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Types</SelectItem>
              <SelectItem value="discovery">Discovery</SelectItem>
              <SelectItem value="planning">Planning</SelectItem>
              <SelectItem value="review">Review</SelectItem>
              <SelectItem value="qbr">QBR</SelectItem>
              <SelectItem value="standup">Standup</SelectItem>
              <SelectItem value="demo">Demo</SelectItem>
              <SelectItem value="training">Training</SelectItem>
              <SelectItem value="support">Support</SelectItem>
              <SelectItem value="other">Other</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </CardHeader>
      <CardContent>
        {filteredMeetings.length === 0 ? (
          <div className="text-center py-12 text-muted-foreground">
            <Calendar className="mx-auto h-12 w-12 mb-4 opacity-50" />
            <p>No meetings found</p>
          </div>
        ) : (
          <div className="space-y-4">
            {filteredMeetings.map((meeting) => (
              <Link key={meeting.id} href={`/meetings/${meeting.id}`}>
                <Card className="hover:border-primary transition-colors cursor-pointer">
                  <CardContent className="p-6">
                    <div className="flex items-start justify-between">
                      <div className="space-y-3 flex-1">
                        <div className="flex items-start gap-3">
                          <div className="flex-1">
                            <h3 className="font-semibold text-lg">{meeting.title}</h3>
                            <div className="flex items-center gap-2 mt-1">
                              <Badge variant="outline">{getMeetingTypeLabel(meeting.meeting_type)}</Badge>
                              <Badge className={cn(getStatusColor(meeting.status))}>
                                {meeting.status.replace("_", " ")}
                              </Badge>
                            </div>
                          </div>
                        </div>

                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-muted-foreground">
                          <div className="flex items-center gap-2">
                            <Building2 className="h-4 w-4" />
                            <span>{meeting.client.company_name}</span>
                          </div>
                          <div className="flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            <span>{format(new Date(meeting.scheduled_at), "MMM d, yyyy")}</span>
                          </div>
                          <div className="flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            <span>
                              {format(new Date(meeting.scheduled_at), "h:mm a")} ({meeting.duration_minutes} min)
                            </span>
                          </div>
                          <div className="flex items-center gap-2">
                            {meeting.meeting_url ? (
                              <>
                                <Video className="h-4 w-4" />
                                <span>Video Meeting</span>
                              </>
                            ) : meeting.location ? (
                              <>
                                <MapPin className="h-4 w-4" />
                                <span className="truncate">{meeting.location}</span>
                              </>
                            ) : (
                              <span className="text-muted-foreground/50">No location</span>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </Link>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
