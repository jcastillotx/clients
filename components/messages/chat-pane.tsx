"use client";

import { useState, useEffect, useRef } from "react";
import { MessageBubble } from "./message-bubble";
import { MessageComposer } from "./message-composer";
import { MessageCircle, Loader2 } from "lucide-react";
import { createClient } from "@/lib/supabase/client";

interface Attachment {
  id: string;
  filename: string;
  mimeType: string | null;
  sizeBytes: number;
  path: string;
}

interface Sender {
  id: string;
  name: string;
  email: string;
  avatar: string | null;
}

interface Message {
  id: string;
  body: string | null;
  type: string;
  senderId: string;
  sender: Sender;
  createdAt: string;
  updatedAt: string;
  isPinned: boolean;
  attachments: Attachment[] | null;
  isRead: boolean;
}

interface ChatPaneProps {
  conversationId: string;
  currentUserId: string;
}

export function ChatPane({ conversationId, currentUserId }: ChatPaneProps) {
  const [messages, setMessages] = useState<Message[]>([]);
  const [loading, setLoading] = useState(true);
  const [isTyping, setIsTyping] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const messagesContainerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (conversationId) {
      fetchMessages();
      setupRealtime();
    }
  }, [conversationId]);

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const fetchMessages = async () => {
    try {
      setLoading(true);
      const response = await fetch(`/api/messages?conversationId=${conversationId}`);
      const data = await response.json();
      setMessages((data.messages || []).reverse());

      // Mark unread messages as read
      markMessagesAsRead(data.messages || []);
    } catch (error) {
      console.error("Error fetching messages:", error);
    } finally {
      setLoading(false);
    }
  };

  const markMessagesAsRead = async (messagesToCheck: Message[]) => {
    const unreadMessages = messagesToCheck.filter((msg) => !msg.isRead && msg.senderId !== currentUserId);

    for (const message of unreadMessages) {
      try {
        await fetch(`/api/messages/${message.id}/read`, {
          method: "POST",
        });
      } catch (error) {
        console.error("Error marking message as read:", error);
      }
    }
  };

  const setupRealtime = async () => {
    const supabase = createClient();

    // Subscribe to new messages
    const channel = supabase
      .channel(`conversation:${conversationId}`)
      .on(
        "postgres_changes",
        {
          event: "INSERT",
          schema: "public",
          table: "messages",
          filter: `conversation_id=eq.${conversationId}`,
        },
        async (payload) => {
          // Fetch the full message with sender info
          const response = await fetch(`/api/messages?conversationId=${conversationId}&limit=1`);
          const data = await response.json();
          if (data.messages?.[0]) {
            setMessages((prev) => [...prev, data.messages[0]]);

            // Mark as read if not sent by current user
            if (data.messages[0].senderId !== currentUserId) {
              await fetch(`/api/messages/${data.messages[0].id}/read`, {
                method: "POST",
              });
            }
          }
        },
      )
      .subscribe();

    // Typing indicator channel
    const typingChannel = supabase.channel(`typing:${conversationId}`).on("presence", { event: "sync" }, () => {
      const state = typingChannel.presenceState();
      const typingUsers = Object.values(state).filter((users: any) => users[0]?.userId !== currentUserId);
      setIsTyping(typingUsers.length > 0);
    });

    typingChannel.subscribe();

    return () => {
      channel.unsubscribe();
      typingChannel.unsubscribe();
    };
  };

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  const handleMessageSent = () => {
    // Message will be added via realtime subscription
    // Just scroll to bottom
    setTimeout(scrollToBottom, 100);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-full">
        <Loader2 className="w-8 h-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return (
    <div className="flex flex-col h-full">
      {/* Messages Area */}
      <div ref={messagesContainerRef} className="flex-1 overflow-y-auto p-4 space-y-2">
        {messages.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-full text-center">
            <MessageCircle className="w-16 h-16 text-muted-foreground mb-4" />
            <h3 className="text-lg font-semibold mb-2">No messages yet</h3>
            <p className="text-sm text-muted-foreground">Start the conversation by sending a message below</p>
          </div>
        ) : (
          <>
            {messages.map((message) => (
              <MessageBubble
                key={message.id}
                message={message}
                isOwnMessage={message.senderId === currentUserId}
                showSender={true}
              />
            ))}
            {isTyping && (
              <div className="flex items-center gap-2 text-sm text-muted-foreground px-4">
                <div className="flex gap-1">
                  <span className="w-2 h-2 bg-muted-foreground rounded-full animate-bounce" />
                  <span
                    className="w-2 h-2 bg-muted-foreground rounded-full animate-bounce"
                    style={{ animationDelay: "0.1s" }}
                  />
                  <span
                    className="w-2 h-2 bg-muted-foreground rounded-full animate-bounce"
                    style={{ animationDelay: "0.2s" }}
                  />
                </div>
                <span>Someone is typing...</span>
              </div>
            )}
            <div ref={messagesEndRef} />
          </>
        )}
      </div>

      {/* Message Composer */}
      <MessageComposer conversationId={conversationId} onMessageSent={handleMessageSent} />
    </div>
  );
}
