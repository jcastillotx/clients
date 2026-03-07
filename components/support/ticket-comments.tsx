"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Checkbox } from "@/components/ui/checkbox";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { formatDistanceToNow } from "date-fns";
import { toast } from "sonner";
import { LockClosedIcon } from "@radix-ui/react-icons";

interface Comment {
  id: string;
  comment: string;
  is_internal: boolean;
  created_at: string;
  user: {
    id: string;
    name: string;
    email: string;
    avatar?: string;
  } | null;
}

interface SupportTicketCommentsProps {
  ticketId: string;
  initialComments: Comment[];
  currentUserId: string;
  isStaff?: boolean;
}

export function SupportTicketComments({ ticketId, initialComments, currentUserId, isStaff = false }: SupportTicketCommentsProps) {
  const router = useRouter();
  const [comments, setComments] = useState<Comment[]>(
    isStaff ? initialComments : initialComments.filter((c) => !c.is_internal)
  );
  const [newComment, setNewComment] = useState("");
  const [isInternal, setIsInternal] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();

    if (!newComment.trim()) {
      toast.error("Comment cannot be empty");
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await fetch(`/api/support/${ticketId}/comments`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          comment: newComment,
          isInternal,
        }),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || "Failed to add comment");
      }

      const result = await response.json();

      setComments([...comments, result]);
      setNewComment("");
      setIsInternal(false);
      toast.success("Comment added successfully");
      router.refresh();
    } catch (error) {
      console.error("Error adding comment:", error);
      toast.error(error instanceof Error ? error.message : "Failed to add comment");
    } finally {
      setIsSubmitting(false);
    }
  }

  function getInitials(name: string): string {
    return name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase()
      .slice(0, 2);
  }

  function getCommentAuthor(comment: Comment) {
    return {
      name: comment.user?.name || "Unknown User",
      email: comment.user?.email || "",
      avatar: comment.user?.avatar,
    };
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Comments & Timeline</CardTitle>
        <CardDescription>Communicate with the support team and track ticket progress</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Comments List */}
        <div className="space-y-6">
          {comments.length === 0 ? (
            <p className="text-center text-muted-foreground py-8">No comments yet. Be the first to comment!</p>
          ) : (
            comments.map((comment, index) => {
              const author = getCommentAuthor(comment);

              return (
              <div key={comment.id}>
                <div className="flex gap-4">
                  <Avatar className="h-10 w-10">
                    <AvatarImage src={author.avatar} alt={author.name} />
                    <AvatarFallback>{getInitials(author.name)}</AvatarFallback>
                  </Avatar>
                  <div className="flex-1 space-y-2">
                    <div className="flex items-center gap-2">
                      <span className="font-semibold">{author.name}</span>
                      {comment.is_internal && (
                        <Badge variant="secondary" className="text-xs">
                          <LockClosedIcon className="h-3 w-3 mr-1" />
                          Internal
                        </Badge>
                      )}
                      <span className="text-sm text-muted-foreground">
                        {formatDistanceToNow(new Date(comment.created_at), {
                          addSuffix: true,
                        })}
                      </span>
                    </div>
                    <div
                      className={`p-4 rounded-lg ${
                        comment.is_internal
                          ? "bg-yellow-50 border border-yellow-200"
                          : "bg-gray-50 border border-gray-200"
                      }`}
                    >
                      <p className="text-sm whitespace-pre-wrap">{comment.comment}</p>
                    </div>
                  </div>
                </div>
                {index < comments.length - 1 && <Separator className="mt-6" />}
              </div>
            )})
          )}
        </div>

        {/* New Comment Form */}
        <div className="pt-6 border-t">
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <Textarea
                placeholder="Add a comment or update..."
                value={newComment}
                onChange={(e) => setNewComment(e.target.value)}
                className="min-h-[100px]"
                disabled={isSubmitting}
              />
            </div>

            <div className="flex items-center justify-between">
              {isStaff ? (
                <div className="flex items-center gap-2">
                  <Checkbox
                    id="internal"
                    checked={isInternal}
                    onCheckedChange={(checked) => setIsInternal(checked === true)}
                    disabled={isSubmitting}
                  />
                  <label
                    htmlFor="internal"
                    className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                  >
                    <div className="flex items-center gap-2">
                      <LockClosedIcon className="h-4 w-4" />
                      Internal note (not visible to client)
                    </div>
                  </label>
                </div>
              ) : (
                <div />
              )}

              <Button type="submit" disabled={isSubmitting || !newComment.trim()}>
                {isSubmitting ? "Adding..." : "Add Comment"}
              </Button>
            </div>
          </form>
        </div>
      </CardContent>
    </Card>
  );
}
