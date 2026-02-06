"use client";

import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { formatDistanceToNow } from "date-fns";
import { Send } from "lucide-react";
import { useQueryClient, useMutation, useQuery } from "@tanstack/react-query";
import { createClient } from "@/lib/supabase/client";

interface Comment {
  id: string;
  content: string;
  created_at: string;
  updated_at?: string;
  user: {
    id: string;
    name: string;
    avatar?: string;
  };
}

interface RequestCommentsProps {
  requestId: string;
  initialComments: Comment[];
}

export function RequestComments({ requestId, initialComments }: RequestCommentsProps) {
  const [newComment, setNewComment] = useState("");
  const queryClient = useQueryClient();
  const supabase = createClient();

  // Use React Query for comments with real-time updates
  const { data: comments = initialComments } = useQuery({
    queryKey: ["request-comments", requestId],
    queryFn: async () => {
      const { data } = await supabase
        .from("request_comments")
        .select(
          `
          id,
          content,
          created_at,
          updated_at,
          user:users(id, name, avatar)
        `,
        )
        .eq("request_id", requestId)
        .order("created_at", { ascending: true });

      return (data as any) || [];
    },
    initialData: initialComments,
  });

  // Subscribe to real-time comment updates
  useState(() => {
    const channel = supabase
      .channel(`request-comments:${requestId}`)
      .on(
        "postgres_changes",
        {
          event: "*",
          schema: "public",
          table: "request_comments",
          filter: `request_id=eq.${requestId}`,
        },
        () => {
          // Invalidate and refetch comments when changes occur
          queryClient.invalidateQueries({ queryKey: ["request-comments", requestId] });
        },
      )
      .subscribe();

    return () => {
      supabase.removeChannel(channel);
    };
  });

  // Mutation for creating new comments
  const createComment = useMutation({
    mutationFn: async (content: string) => {
      const {
        data: { user },
      } = await supabase.auth.getUser();
      if (!user) throw new Error("Not authenticated");

      const { data, error } = await supabase
        .from("request_comments")
        .insert({
          request_id: requestId,
          user_id: user.id,
          content,
        })
        .select(
          `
          id,
          content,
          created_at,
          user:users(id, name, avatar)
        `,
        )
        .single();

      if (error) throw error;
      return data;
    },
    onSuccess: () => {
      setNewComment("");
      queryClient.invalidateQueries({ queryKey: ["request-comments", requestId] });
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (newComment.trim()) {
      createComment.mutate(newComment);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Comments ({comments.length})</CardTitle>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Comment list */}
        <div className="space-y-4">
          {comments.length === 0 ? (
            <p className="text-sm text-muted-foreground text-center py-8">No comments yet. Be the first to comment!</p>
          ) : (
            comments.map((comment: Comment) => (
              <div key={comment.id} className="flex gap-4">
                <Avatar className="h-10 w-10">
                  <AvatarImage src={comment.user.avatar} />
                  <AvatarFallback>
                    {comment.user.name
                      .split(" ")
                      .map((n: string) => n[0])
                      .join("")}
                  </AvatarFallback>
                </Avatar>
                <div className="flex-1 space-y-1">
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-medium">{comment.user.name}</span>
                    <span className="text-xs text-muted-foreground">
                      {formatDistanceToNow(new Date(comment.created_at), {
                        addSuffix: true,
                      })}
                    </span>
                  </div>
                  <p className="text-sm text-muted-foreground whitespace-pre-wrap">{comment.content}</p>
                </div>
              </div>
            ))
          )}
        </div>

        {/* New comment form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          <Textarea
            placeholder="Add a comment..."
            value={newComment}
            onChange={(e) => setNewComment(e.target.value)}
            rows={3}
            className="resize-none"
          />
          <div className="flex justify-end">
            <Button type="submit" disabled={!newComment.trim() || createComment.isPending}>
              <Send className="mr-2 h-4 w-4" />
              {createComment.isPending ? "Posting..." : "Post Comment"}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
