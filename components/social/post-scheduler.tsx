"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Calendar } from "@/components/ui/calendar";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { format } from "date-fns";
import { CalendarIcon, Send, Clock } from "lucide-react";
import { cn } from "@/lib/utils";
import { useToast } from "@/hooks/use-toast";

interface Account {
  id: string;
  platform: string;
  account_name: string;
}

interface PostSchedulerProps {
  clientId: string;
  userId: string;
  accounts: Account[];
  onPostCreated: (post: any) => void;
}

export function PostScheduler({ clientId, userId, accounts, onPostCreated }: PostSchedulerProps) {
  const [content, setContent] = useState("");
  const [accountId, setAccountId] = useState("");
  const [date, setDate] = useState<Date>();
  const [time, setTime] = useState("12:00");
  const [loading, setLoading] = useState(false);
  const { toast } = useToast();

  const handleSubmit = async (publishNow: boolean = false) => {
    if (!content.trim() || !accountId) {
      toast({
        title: "Validation Error",
        description: "Please select an account and enter post content.",
        variant: "destructive",
      });
      return;
    }

    setLoading(true);

    try {
      const scheduledFor = publishNow ? new Date() : date ? new Date(`${format(date, "yyyy-MM-dd")}T${time}`) : null;

      const postData = {
        accountId,
        content,
        scheduledFor: scheduledFor?.toISOString(),
        createdBy: userId,
        publishNow,
        metadata: {
          hashtags: extractHashtags(content),
          mentions: extractMentions(content),
        },
      };

      const response = await fetch("/api/social/posts", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(postData),
      });

      if (!response.ok) {
        throw new Error("Failed to create post");
      }

      const newPost = await response.json();
      const post = newPost.data ?? newPost;

      toast({
        title: "Success",
        description: publishNow ? "Post published successfully!" : "Post scheduled successfully!",
      });

      onPostCreated(post);

      // Reset form
      setContent("");
      setAccountId("");
      setDate(undefined);
      setTime("12:00");
    } catch (error) {
      console.error("Error creating post:", error);
      toast({
        title: "Error",
        description: "Failed to create post. Please try again.",
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  const extractHashtags = (text: string): string[] => {
    const hashtagRegex = /#(\w+)/g;
    const matches = text.match(hashtagRegex);
    return matches ? matches.map((tag) => tag.substring(1)) : [];
  };

  const extractMentions = (text: string): string[] => {
    const mentionRegex = /@(\w+)/g;
    const matches = text.match(mentionRegex);
    return matches ? matches.map((mention) => mention.substring(1)) : [];
  };

  const charCount = content.length;
  const maxChars = 280; // Twitter-like limit

  return (
    <Card>
      <CardHeader>
        <CardTitle>Create Post</CardTitle>
        <CardDescription>Schedule or publish posts to your social media accounts</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-2">
          <Label htmlFor="account">Account</Label>
          <Select value={accountId} onValueChange={setAccountId}>
            <SelectTrigger id="account">
              <SelectValue placeholder="Select an account" />
            </SelectTrigger>
            <SelectContent>
              {accounts.map((account) => (
                <SelectItem key={account.id} value={account.id}>
                  {account.account_name} ({account.platform})
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2">
          <Label htmlFor="content">Post Content</Label>
          <Textarea
            id="content"
            placeholder="What's on your mind?"
            value={content}
            onChange={(e) => setContent(e.target.value)}
            rows={6}
            className="resize-none"
          />
          <div className="flex justify-between text-sm text-muted-foreground">
            <span>Use #hashtags and @mentions</span>
            <span className={cn(charCount > maxChars && "text-destructive")}>
              {charCount} / {maxChars}
            </span>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label>Schedule Date</Label>
            <Popover>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  className={cn("w-full justify-start text-left font-normal", !date && "text-muted-foreground")}
                >
                  <CalendarIcon className="mr-2 h-4 w-4" />
                  {date ? format(date, "PPP") : <span>Pick a date</span>}
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-auto p-0">
                <Calendar mode="single" selected={date} onSelect={setDate} initialFocus />
              </PopoverContent>
            </Popover>
          </div>

          <div className="space-y-2">
            <Label htmlFor="time">Time</Label>
            <Input id="time" type="time" value={time} onChange={(e) => setTime(e.target.value)} />
          </div>
        </div>

        <div className="flex gap-2">
          <Button
            onClick={() => handleSubmit(true)}
            disabled={loading || !accountId || !content.trim()}
            className="flex-1"
          >
            <Send className="mr-2 h-4 w-4" />
            Publish Now
          </Button>
          <Button
            variant="outline"
            onClick={() => handleSubmit(false)}
            disabled={loading || !accountId || !content.trim() || !date}
            className="flex-1"
          >
            <Clock className="mr-2 h-4 w-4" />
            Schedule Post
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}
