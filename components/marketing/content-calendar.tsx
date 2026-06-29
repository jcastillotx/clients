"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { formatDate } from "@/lib/utils/format";
import { Facebook, Instagram, Linkedin, Twitter, Calendar as CalendarIcon, List, Loader2 } from "lucide-react";
import { fetchApi } from "@/lib/api/client";
import type { ContentCalendarItem } from "@/lib/db/schema/marketing";

const platformIcons = {
  facebook: Facebook,
  instagram: Instagram,
  linkedin: Linkedin,
  twitter: Twitter,
  x: Twitter,
};

export function ContentCalendar() {
  const router = useRouter();
  const [date, setDate] = useState<Date | undefined>(new Date());
  const [selectedPlatform, setSelectedPlatform] = useState<string>("all");
  const [content, setContent] = useState<ContentCalendarItem[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchApi<ContentCalendarItem[]>(
      "/api/marketing/content-calendar",
      {},
      { fallbackMessage: "Failed to load content calendar" },
    )
      .then((data) => setContent(Array.isArray(data) ? data : []))
      .catch((err: unknown) => setError(err instanceof Error ? err.message : "Failed to load content calendar"))
      .finally(() => setIsLoading(false));
  }, []);

  const getStatusBadgeVariant = (status: string) => {
    switch (status) {
      case "published":
        return "default";
      case "scheduled":
        return "secondary";
      case "draft":
        return "outline";
      case "pending_approval":
        return "secondary";
      case "approved":
        return "default";
      default:
        return "secondary";
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case "published":
        return "bg-green-100 text-green-800";
      case "scheduled":
        return "bg-blue-100 text-blue-800";
      case "draft":
        return "bg-gray-100 text-gray-800";
      case "pending_approval":
        return "bg-yellow-100 text-yellow-800";
      case "approved":
        return "bg-emerald-100 text-emerald-800";
      case "needs_revision":
        return "bg-orange-100 text-orange-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  };

  const getPlatformIcon = (platform: string) => {
    const Icon = platformIcons[platform as keyof typeof platformIcons];
    return Icon ? <Icon className="h-4 w-4" /> : null;
  };

  const filteredContent = content.filter((item) => selectedPlatform === "all" || item.platform === selectedPlatform);

  if (isLoading) {
    return (
      <Card>
        <CardContent className="flex items-center justify-center py-12">
          <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
        </CardContent>
      </Card>
    );
  }

  if (error) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Error Loading Content Calendar</CardTitle>
          <CardDescription>{error}</CardDescription>
        </CardHeader>
      </Card>
    );
  }

  if (content.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>No Content Scheduled</CardTitle>
          <CardDescription>Start planning your content by creating your first calendar item</CardDescription>
        </CardHeader>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-4">
        <Select value={selectedPlatform} onValueChange={setSelectedPlatform}>
          <SelectTrigger className="w-[180px]">
            <SelectValue placeholder="Filter by platform" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Platforms</SelectItem>
            <SelectItem value="facebook">Facebook</SelectItem>
            <SelectItem value="instagram">Instagram</SelectItem>
            <SelectItem value="linkedin">LinkedIn</SelectItem>
            <SelectItem value="twitter">Twitter/X</SelectItem>
            <SelectItem value="tiktok">TikTok</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Tabs defaultValue="calendar" className="space-y-4">
        <TabsList>
          <TabsTrigger value="calendar">
            <CalendarIcon className="mr-2 h-4 w-4" />
            Calendar View
          </TabsTrigger>
          <TabsTrigger value="list">
            <List className="mr-2 h-4 w-4" />
            List View
          </TabsTrigger>
        </TabsList>

        <TabsContent value="calendar" className="space-y-4">
          <div className="grid gap-4 md:grid-cols-[300px_1fr]">
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Select Date</CardTitle>
              </CardHeader>
              <CardContent>
                <Calendar mode="single" selected={date} onSelect={setDate} className="rounded-md border" />
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>{date ? formatDate(date) : "Select a date"}</CardTitle>
                <CardDescription>Scheduled content for this day</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {filteredContent
                    .filter((item) => {
                      if (!date || !item.scheduledFor) return false;
                      const itemDate = new Date(item.scheduledFor);
                      return itemDate.toDateString() === date.toDateString();
                    })
                    .map((item) => (
                      <div key={item.id} className="flex items-start gap-4 rounded-lg border p-4">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                          {getPlatformIcon(item.platform)}
                        </div>
                        <div className="flex-1 space-y-1">
                          <div className="flex items-center justify-between">
                            <h4 className="font-semibold">{item.title}</h4>
                            <Badge className={getStatusColor(item.status)}>{item.status}</Badge>
                          </div>
                          <p className="text-sm text-muted-foreground line-clamp-2">{item.content}</p>
                          <div className="flex items-center gap-2 text-xs text-muted-foreground">
                            <span className="capitalize">{item.platform}</span>
                            <span>•</span>
                            <span>{item.contentType}</span>
                          </div>
                        </div>
                      </div>
                    ))}
                  {filteredContent.filter((item) => {
                    if (!date || !item.scheduledFor) return false;
                    const itemDate = new Date(item.scheduledFor);
                    return itemDate.toDateString() === date.toDateString();
                  }).length === 0 && (
                    <p className="text-center text-muted-foreground">No content scheduled for this day</p>
                  )}
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="list" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>All Scheduled Content</CardTitle>
              <CardDescription>View and manage all your scheduled content</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {filteredContent.map((item) => (
                  <div key={item.id} className="flex items-start gap-4 rounded-lg border p-4">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                      {getPlatformIcon(item.platform)}
                    </div>
                    <div className="flex-1 space-y-1">
                      <div className="flex items-center justify-between">
                        <h4 className="font-semibold">{item.title}</h4>
                        <Badge className={getStatusColor(item.status)}>{item.status}</Badge>
                      </div>
                      <p className="text-sm text-muted-foreground line-clamp-2">{item.content}</p>
                      <div className="flex items-center gap-4 text-xs text-muted-foreground">
                        <span className="capitalize">{item.platform}</span>
                        <span>•</span>
                        <span>{item.contentType}</span>
                        {item.scheduledFor && (
                          <>
                            <span>•</span>
                            <span>Scheduled for {formatDate(new Date(item.scheduledFor))}</span>
                          </>
                        )}
                      </div>
                    </div>
                    <Button variant="outline" size="sm" onClick={() => router.push(`/marketing/content-calendar/${item.id}/edit`)}>
                      Edit
                    </Button>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
