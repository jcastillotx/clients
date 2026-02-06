"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { ExternalLink, MessageCircle, ThumbsUp, ThumbsDown, Minus } from "lucide-react";
import { formatDistanceToNow } from "date-fns";
import { toast } from "sonner";

interface BrandMention {
  id: string;
  platform: string;
  mentionText: string;
  sentiment: "positive" | "neutral" | "negative" | null;
  author: string | null;
  url: string | null;
  postedAt: string | null;
  respondedAt: string | null;
  responseNotes: string | null;
  meta: {
    reach?: number;
    engagement?: number;
    likes?: number;
    shares?: number;
  } | null;
}

interface BrandMentionsListProps {
  sentiment?: "positive" | "neutral" | "negative";
  needsResponse?: boolean;
}

export function BrandMentionsList({ sentiment, needsResponse }: BrandMentionsListProps) {
  const [mentions, setMentions] = useState<BrandMention[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [responseText, setResponseText] = useState("");

  useEffect(() => {
    fetchMentions();
  }, [sentiment, needsResponse]);

  const fetchMentions = async () => {
    try {
      const params = new URLSearchParams();
      if (sentiment) params.append("sentiment", sentiment);
      if (needsResponse) params.append("needsResponse", "true");

      const response = await fetch(`/api/brand/mentions?${params}`);
      if (!response.ok) throw new Error("Failed to fetch mentions");
      const data = await response.json();
      setMentions(data);
    } catch (error) {
      console.error("Failed to fetch mentions:", error);
    } finally {
      setIsLoading(false);
    }
  };

  const markAsResponded = async (id: string, notes: string) => {
    try {
      const response = await fetch(`/api/brand/mentions/${id}/respond`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ notes }),
      });

      if (!response.ok) throw new Error("Failed to mark as responded");

      toast.success("Marked as responded");
      fetchMentions();
    } catch (error) {
      toast.error("Failed to mark as responded");
      console.error(error);
    }
  };

  const getSentimentIcon = (sentiment: string | null) => {
    switch (sentiment) {
      case "positive":
        return <ThumbsUp className="h-4 w-4 text-green-500" />;
      case "negative":
        return <ThumbsDown className="h-4 w-4 text-red-500" />;
      case "neutral":
        return <Minus className="h-4 w-4 text-gray-500" />;
      default:
        return null;
    }
  };

  const getSentimentBadge = (sentiment: string | null) => {
    const variants: Record<string, "default" | "secondary" | "destructive"> = {
      positive: "default",
      neutral: "secondary",
      negative: "destructive",
    };

    return (
      <Badge variant={variants[sentiment || ""] || "secondary"} className="capitalize">
        {sentiment || "Unknown"}
      </Badge>
    );
  };

  if (isLoading) {
    return <div>Loading mentions...</div>;
  }

  return (
    <div className="space-y-4">
      {mentions.map((mention) => (
        <Card key={mention.id}>
          <CardHeader>
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-2">
                {getSentimentIcon(mention.sentiment)}
                <div>
                  <CardTitle className="text-base">{mention.author || "Anonymous"}</CardTitle>
                  <CardDescription>
                    {mention.platform} •{" "}
                    {mention.postedAt
                      ? formatDistanceToNow(new Date(mention.postedAt), { addSuffix: true })
                      : "Date unknown"}
                  </CardDescription>
                </div>
              </div>
              {getSentimentBadge(mention.sentiment)}
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            <p className="text-sm">{mention.mentionText}</p>

            {mention.meta && (
              <div className="flex gap-4 text-xs text-muted-foreground">
                {mention.meta.reach && <span>Reach: {mention.meta.reach.toLocaleString()}</span>}
                {mention.meta.engagement && <span>Engagement: {mention.meta.engagement.toLocaleString()}</span>}
                {mention.meta.likes && <span>Likes: {mention.meta.likes.toLocaleString()}</span>}
                {mention.meta.shares && <span>Shares: {mention.meta.shares.toLocaleString()}</span>}
              </div>
            )}

            <div className="flex gap-2">
              {mention.url && (
                <Button size="sm" variant="outline" asChild>
                  <a href={mention.url} target="_blank" rel="noopener noreferrer">
                    <ExternalLink className="mr-1 h-4 w-4" />
                    View Source
                  </a>
                </Button>
              )}

              {!mention.respondedAt && mention.sentiment === "negative" && (
                <Dialog>
                  <DialogTrigger asChild>
                    <Button size="sm" variant="outline">
                      <MessageCircle className="mr-1 h-4 w-4" />
                      Mark as Responded
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Mark as Responded</DialogTitle>
                      <DialogDescription>Add notes about how you responded to this mention</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                      <Textarea
                        placeholder="Describe your response..."
                        value={responseText}
                        onChange={(e) => setResponseText(e.target.value)}
                        rows={4}
                      />
                      <Button onClick={() => markAsResponded(mention.id, responseText)}>Save Response</Button>
                    </div>
                  </DialogContent>
                </Dialog>
              )}

              {mention.respondedAt && (
                <Badge variant="outline">
                  Responded {formatDistanceToNow(new Date(mention.respondedAt), { addSuffix: true })}
                </Badge>
              )}
            </div>

            {mention.responseNotes && (
              <div className="rounded-lg bg-muted p-3">
                <p className="text-sm font-medium mb-1">Response Notes:</p>
                <p className="text-sm text-muted-foreground">{mention.responseNotes}</p>
              </div>
            )}
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
