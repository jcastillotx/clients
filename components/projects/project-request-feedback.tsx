"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import { formatDistanceToNow } from "date-fns";
import { Loader2, MessageSquareText, RefreshCw, Send, Star } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";

interface FeedbackUser {
  id: string;
  name: string;
  avatar?: string | null;
}

interface FeedbackEntry {
  id: string;
  createdAt: string;
  updatedAt: string | null;
  rating: number | null;
  message: string;
  user: FeedbackUser | null;
}

interface ProjectRequestFeedbackProps {
  requestId: string;
}

export function ProjectRequestFeedback({ requestId }: ProjectRequestFeedbackProps) {
  const [entries, setEntries] = useState<FeedbackEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [rating, setRating] = useState<string>("none");
  const [message, setMessage] = useState("");

  const load = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await fetch(`/api/projects/requests/${requestId}/feedback`, {
        method: "GET",
        cache: "no-store",
      });
      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.error || "Failed to load feedback");
      }
      setEntries(payload.data || []);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Failed to load feedback");
    } finally {
      setLoading(false);
    }
  }, [requestId]);

  useEffect(() => {
    void load();
  }, [load]);

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!message.trim()) {
      return;
    }

    try {
      setIsSubmitting(true);
      setError(null);

      const response = await fetch(`/api/projects/requests/${requestId}/feedback`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          rating: rating === "none" ? undefined : Number(rating),
          message: message.trim(),
        }),
      });
      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.error || "Failed to post feedback");
      }

      const newEntry = payload.data as FeedbackEntry;
      setEntries((previous) => [...previous, newEntry]);
      setRating("none");
      setMessage("");
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : "Failed to post feedback");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Feedback & Online Review</CardTitle>
          <CardDescription>Share input, decisions, and review notes directly on this project request.</CardDescription>
        </div>
        <Button variant="outline" size="sm" onClick={() => void load()} disabled={loading}>
          {loading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <RefreshCw className="mr-2 h-4 w-4" />}
          Refresh
        </Button>
      </CardHeader>
      <CardContent className="space-y-6">
        {error ? <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</div> : null}

        <form onSubmit={handleSubmit} className="space-y-4 rounded-md border p-4">
          <div className="space-y-2">
            <Label htmlFor="feedback-rating">Rating (optional)</Label>
            <Select value={rating} onValueChange={setRating}>
              <SelectTrigger id="feedback-rating" className="max-w-[240px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">No rating</SelectItem>
                <SelectItem value="5">5 - Excellent</SelectItem>
                <SelectItem value="4">4 - Good</SelectItem>
                <SelectItem value="3">3 - Fair</SelectItem>
                <SelectItem value="2">2 - Needs work</SelectItem>
                <SelectItem value="1">1 - Poor</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label htmlFor="feedback-message">Feedback</Label>
            <Textarea
              id="feedback-message"
              value={message}
              onChange={(event) => setMessage(event.target.value)}
              rows={4}
              placeholder="Share your review notes, clarifications, or approval feedback."
              required
            />
          </div>
          <div className="flex justify-end">
            <Button type="submit" disabled={isSubmitting || !message.trim()}>
              {isSubmitting ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Send className="mr-2 h-4 w-4" />}
              Post Feedback
            </Button>
          </div>
        </form>

        {loading ? (
          <div className="flex items-center justify-center py-10">
            <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
          </div>
        ) : entries.length === 0 ? (
          <div className="rounded-md border border-dashed py-10 text-center text-sm text-muted-foreground">
            No feedback yet. Add your first online review entry above.
          </div>
        ) : (
          <div className="space-y-4">
            {entries.map((entry) => (
              <div key={entry.id} className="rounded-md border p-4">
                <div className="mb-2 flex items-center gap-3">
                  <Avatar className="h-8 w-8">
                    <AvatarImage src={entry.user?.avatar || undefined} />
                    <AvatarFallback>{entry.user?.name?.slice(0, 1).toUpperCase() || "U"}</AvatarFallback>
                  </Avatar>
                  <div>
                    <div className="text-sm font-medium">{entry.user?.name || "Unknown user"}</div>
                    <div className="text-xs text-muted-foreground">
                      {formatDistanceToNow(new Date(entry.createdAt), { addSuffix: true })}
                    </div>
                  </div>
                  {entry.rating ? (
                    <Badge variant="secondary" className="ml-auto">
                      <Star className="mr-1 h-3.5 w-3.5 fill-current" />
                      {entry.rating}/5
                    </Badge>
                  ) : null}
                </div>
                <div className="flex items-start gap-2 text-sm">
                  <MessageSquareText className="mt-0.5 h-4 w-4 text-muted-foreground" />
                  <p className="whitespace-pre-wrap text-muted-foreground">{entry.message}</p>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
