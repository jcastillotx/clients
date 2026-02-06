"use client";

import { useState, useEffect } from "react";
import { format } from "date-fns";
import { MessageCircle, Users, Clock } from "lucide-react";
import { cn } from "@/lib/utils";

interface Participant {
  id: string;
  name: string;
  email: string;
  avatar: string | null;
}

interface Conversation {
  id: string;
  title: string | null;
  lastMessage: string | null;
  lastMessageType: string | null;
  lastMessageAt: string;
  unreadCount: number;
  participants: Participant[];
  isClosed: boolean;
}

interface ConversationListProps {
  selectedConversationId?: string;
  onConversationSelect: (conversationId: string) => void;
}

export function ConversationList({ selectedConversationId, onConversationSelect }: ConversationListProps) {
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchConversations();
  }, []);

  const fetchConversations = async () => {
    try {
      setLoading(true);
      const response = await fetch("/api/messages/conversations");
      const data = await response.json();
      setConversations(data.conversations || []);
    } catch (error) {
      console.error("Error fetching conversations:", error);
    } finally {
      setLoading(false);
    }
  };

  const getConversationTitle = (conversation: Conversation) => {
    if (conversation.title) return conversation.title;
    // Generate title from participants
    const otherParticipants = conversation.participants?.slice(0, 3) || [];
    if (otherParticipants.length === 0) return "Empty Conversation";
    return otherParticipants.map((p) => p.name).join(", ");
  };

  const truncateMessage = (text: string | null, maxLength = 60) => {
    if (!text) return "No messages yet";
    return text.length > maxLength ? `${text.substring(0, maxLength)}...` : text;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="text-muted-foreground">Loading conversations...</div>
      </div>
    );
  }

  if (conversations.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center h-full p-6 text-center">
        <MessageCircle className="w-12 h-12 text-muted-foreground mb-4" />
        <h3 className="text-lg font-semibold mb-2">No conversations yet</h3>
        <p className="text-sm text-muted-foreground">Start a new conversation to begin messaging</p>
      </div>
    );
  }

  return (
    <div className="h-full overflow-y-auto">
      <div className="divide-y">
        {conversations.map((conversation) => {
          const isSelected = conversation.id === selectedConversationId;
          const hasUnread = conversation.unreadCount > 0;

          return (
            <button
              key={conversation.id}
              onClick={() => onConversationSelect(conversation.id)}
              className={cn(
                "w-full p-4 text-left hover:bg-accent transition-colors",
                isSelected && "bg-accent",
                hasUnread && "bg-blue-50 dark:bg-blue-950/20",
              )}
            >
              <div className="flex items-start gap-3">
                {/* Avatar */}
                <div className="flex-shrink-0">
                  <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                    {conversation.participants?.[0]?.avatar ? (
                      <img src={conversation.participants[0].avatar} alt="" className="w-10 h-10 rounded-full" />
                    ) : (
                      <Users className="w-5 h-5 text-primary" />
                    )}
                  </div>
                </div>

                {/* Content */}
                <div className="flex-1 min-w-0">
                  <div className="flex items-center justify-between mb-1">
                    <h4 className={cn("text-sm font-medium truncate", hasUnread && "font-semibold")}>
                      {getConversationTitle(conversation)}
                    </h4>
                    {hasUnread && (
                      <span className="flex-shrink-0 ml-2 px-2 py-0.5 text-xs font-semibold text-white bg-blue-600 rounded-full">
                        {conversation.unreadCount}
                      </span>
                    )}
                  </div>

                  <p
                    className={cn(
                      "text-sm text-muted-foreground truncate mb-1",
                      hasUnread && "font-medium text-foreground",
                    )}
                  >
                    {conversation.lastMessageType === "file"
                      ? "📎 Attachment"
                      : truncateMessage(conversation.lastMessage)}
                  </p>

                  <div className="flex items-center text-xs text-muted-foreground">
                    <Clock className="w-3 h-3 mr-1" />
                    {format(new Date(conversation.lastMessageAt), "MMM d, h:mm a")}
                  </div>
                </div>
              </div>
            </button>
          );
        })}
      </div>
    </div>
  );
}
