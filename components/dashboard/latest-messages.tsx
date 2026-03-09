"use client";

import Link from "next/link";
import { format } from "date-fns";
import { MessageSquareText, Users } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

interface LatestMessageConversation {
  id: string;
  title: string | null;
  lastMessage: string | null;
  lastMessageType: string | null;
  lastMessageAt: string | null;
  unreadCount: number;
  participants: Array<{
    id: string;
    name: string;
    email: string;
  }> | null;
}

interface LatestMessagesProps {
  conversations: LatestMessageConversation[];
}

export function LatestMessages({ conversations }: LatestMessagesProps) {
  const getTitle = (conversation: LatestMessageConversation) => {
    if (conversation.title) return conversation.title;
    return conversation.participants?.slice(0, 3).map((participant) => participant.name).join(", ") || "Conversation";
  };

  return (
    <Card className="bg-gradient-to-br from-card to-secondary/20">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <MessageSquareText className="h-5 w-5" />
          Latest Messages
        </CardTitle>
      </CardHeader>
      <CardContent>
        {conversations.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted-foreground">No recent messages</p>
        ) : (
          <div className="space-y-4">
            {conversations.map((conversation) => (
              <Link
                key={conversation.id}
                href="/messages"
                className="block rounded-xl border border-border/70 bg-background/80 p-3.5 transition-all hover:-translate-y-0.5 hover:border-primary/30 hover:bg-primary/5"
              >
                <div className="mb-2 flex items-start justify-between gap-2">
                  <div className="min-w-0 flex-1">
                    <h4 className="truncate text-sm font-medium">{getTitle(conversation)}</h4>
                    <p className="mt-1 truncate text-xs text-muted-foreground">
                      {conversation.lastMessageType === "file"
                        ? "Attachment shared"
                        : conversation.lastMessage || "No messages yet"}
                    </p>
                  </div>
                  {conversation.unreadCount > 0 ? <Badge>{conversation.unreadCount} new</Badge> : null}
                </div>
                <div className="flex items-center justify-between text-xs text-muted-foreground">
                  <span className="inline-flex items-center gap-1">
                    <Users className="h-3.5 w-3.5" />
                    {conversation.participants?.length || 0} participants
                  </span>
                  <span>{conversation.lastMessageAt ? format(new Date(conversation.lastMessageAt), "MMM d, h:mm a") : "No activity"}</span>
                </div>
              </Link>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
