"use client";

import { useState, useEffect } from "react";
import { ConversationList } from "@/components/messages/conversation-list";
import { ChatPane } from "@/components/messages/chat-pane";
import { MessageCircle, Plus, Menu } from "lucide-react";
import { Button } from "@/components/ui/button";
import { createClient } from "@/lib/supabase/client";
import { cn } from "@/lib/utils";

export default function MessagesPage() {
  const [selectedConversationId, setSelectedConversationId] = useState<string | undefined>();
  const [currentUserId, setCurrentUserId] = useState<string>("");
  const [showSidebar, setShowSidebar] = useState(true);

  useEffect(() => {
    getCurrentUser();
  }, []);

  const getCurrentUser = async () => {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (user) {
      setCurrentUserId(user.id);
    }
  };

  return (
    <div className="h-[calc(100vh-4rem)] flex flex-col">
      {/* Header */}
      <div className="border-b bg-background p-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Button variant="ghost" size="icon" className="lg:hidden" onClick={() => setShowSidebar(!showSidebar)}>
              <Menu className="w-5 h-5" />
            </Button>
            <MessageCircle className="w-6 h-6 text-primary" />
            <h1 className="text-2xl font-bold">Messages</h1>
          </div>
          <Button size="sm" className="gap-2">
            <Plus className="w-4 h-4" />
            New Conversation
          </Button>
        </div>
      </div>

      {/* Main Content */}
      <div className="flex-1 flex overflow-hidden">
        {/* Conversations Sidebar */}
        <div
          className={cn(
            "w-full lg:w-80 border-r bg-background transition-all",
            showSidebar ? "block" : "hidden lg:block",
          )}
        >
          <ConversationList
            selectedConversationId={selectedConversationId}
            onConversationSelect={(id) => {
              setSelectedConversationId(id);
              // Hide sidebar on mobile after selection
              if (window.innerWidth < 1024) {
                setShowSidebar(false);
              }
            }}
          />
        </div>

        {/* Chat Area */}
        <div className="flex-1 bg-background">
          {selectedConversationId && currentUserId ? (
            <ChatPane conversationId={selectedConversationId} currentUserId={currentUserId} />
          ) : (
            <div className="h-full flex flex-col items-center justify-center text-center p-6">
              <MessageCircle className="w-20 h-20 text-muted-foreground mb-6" />
              <h2 className="text-2xl font-bold mb-2">Welcome to Messages</h2>
              <p className="text-muted-foreground mb-6 max-w-md">
                Select a conversation from the sidebar to start chatting, or create a new conversation to begin.
              </p>
              <Button className="gap-2">
                <Plus className="w-4 h-4" />
                Start New Conversation
              </Button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
