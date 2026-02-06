"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Calendar } from "@/components/ui/calendar";
import { Badge } from "@/components/ui/badge";
import { format, isSameDay } from "date-fns";

interface Post {
  id: string;
  content: string;
  scheduled_for: string;
  status: string;
  account: {
    platform: string;
    account_name: string;
  };
}

interface PostsCalendarProps {
  posts: Post[];
}

export function PostsCalendar({ posts }: PostsCalendarProps) {
  const [selectedDate, setSelectedDate] = useState<Date | undefined>(new Date());

  const postsOnSelectedDate = posts.filter((post) =>
    selectedDate ? isSameDay(new Date(post.scheduled_for), selectedDate) : false,
  );

  const datesWithPosts = posts.map((post) => new Date(post.scheduled_for));

  return (
    <div className="grid gap-6 md:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>Calendar</CardTitle>
          <CardDescription>View scheduled posts by date</CardDescription>
        </CardHeader>
        <CardContent>
          <Calendar
            mode="single"
            selected={selectedDate}
            onSelect={setSelectedDate}
            modifiers={{
              hasPost: datesWithPosts,
            }}
            modifiersStyles={{
              hasPost: {
                fontWeight: "bold",
                backgroundColor: "hsl(var(--primary))",
                color: "white",
              },
            }}
            className="rounded-md border"
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Posts on {selectedDate ? format(selectedDate, "PPP") : "Selected Date"}</CardTitle>
          <CardDescription>{postsOnSelectedDate.length} posts scheduled</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {postsOnSelectedDate.length === 0 ? (
              <p className="text-sm text-muted-foreground">No posts scheduled for this date.</p>
            ) : (
              postsOnSelectedDate.map((post) => (
                <Card key={post.id}>
                  <CardContent className="p-4">
                    <div className="flex items-start gap-3">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                          <Badge variant="secondary" className="capitalize">
                            {post.account.platform}
                          </Badge>
                          <span className="text-sm text-muted-foreground">
                            {format(new Date(post.scheduled_for), "HH:mm")}
                          </span>
                        </div>
                        <p className="text-sm">
                          {post.content.substring(0, 100)}
                          {post.content.length > 100 ? "..." : ""}
                        </p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
