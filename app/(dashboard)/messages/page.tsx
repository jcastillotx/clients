"use client";

import { useState, useEffect } from "react";
import { ConversationList } from "@/components/messages/conversation-list";
import { ChatPane } from "@/components/messages/chat-pane";
import { MessageCircle, Plus, Menu } from "lucide-react";
import { Button } from "@/components/ui/button";
import { createClient } from "@/lib/supabase/client";
import { cn } from "@/lib/utils";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";

interface ConversationParticipant {
  id: string;
  name: string;
  email: string;
}

export default function MessagesPage() {
  const [selectedConversationId, setSelectedConversationId] = useState<string | undefined>();
  const [currentUserId, setCurrentUserId] = useState<string>("");
  const [clientId, setClientId] = useState<string>("");
  const [showSidebar, setShowSidebar] = useState(true);
  const [showNewConversationDialog, setShowNewConversationDialog] = useState(false);
  const [conversationTitle, setConversationTitle] = useState("");
  const [participants, setParticipants] = useState<ConversationParticipant[]>([]);
  const [selectedParticipantIds, setSelectedParticipantIds] = useState<string[]>([]);
  const [isCreatingConversation, setIsCreatingConversation] = useState(false);
  const [conversationListVersion, setConversationListVersion] = useState(0);

  useEffect(() => {
    getCurrentUser();
  }, []);

  const getCurrentUser = async () => {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) return;

    setCurrentUserId(user.id);

    const { data: userRow } = await supabase.from("users").select("client_id").eq("id", user.id).maybeSingle();
    const resolvedClientId = userRow?.client_id || "";
    setClientId(resolvedClientId);

    if (!resolvedClientId) return;

    const { data: participantRows } = await supabase
      .from("users")
      .select("id, name, email")
      .eq("client_id", resolvedClientId)
      .neq("id", user.id)
      .order("name", { ascending: true });

    setParticipants(participantRows || []);
  };

  const toggleParticipant = (participantId: string) => {
    setSelectedParticipantIds((prev) =>
      prev.includes(participantId) ? prev.filter((id) => id !== participantId) : [...prev, participantId],
    );
  };

  const startNewConversation = async () => {
    if (!clientId || selectedParticipantIds.length === 0) return;

    try {
      setIsCreatingConversation(true);

      const response = await fetch("/api/messages/conversations", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          clientId,
          title: conversationTitle.trim() || null,
          participantIds: selectedParticipantIds,
        }),
      });

      if (!response.ok) {
        throw new Error("Failed to create conversation");
      }

      const data = await response.json();
      const newConversationId = data?.conversation?.id;

      setShowNewConversationDialog(false);
      setConversationTitle("");
      setSelectedParticipantIds([]);
      setConversationListVersion((prev) => prev + 1);

      if (newConversationId) {
        setSelectedConversationId(newConversationId);
      }

      if (window.innerWidth < 1024) {
        setShowSidebar(false);
      }
    } catch (error) {
      console.error("Error creating conversation:", error);
    } finally {
      setIsCreatingConversation(false);
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
          <Button size="sm" className="gap-2" onClick={() => setShowNewConversationDialog(true)}>
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
            key={conversationListVersion}
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
              <Button className="gap-2" onClick={() => setShowNewConversationDialog(true)}>
                <Plus className="w-4 h-4" />
                Start New Conversation
              </Button>
            </div>
          )}
        </div>
      </div>

      <Dialog open={showNewConversationDialog} onOpenChange={setShowNewConversationDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Start a new conversation</DialogTitle>
            <DialogDescription>Choose who to message and optionally add a title.</DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="conversation-title">Conversation title (optional)</Label>
              <Input
                id="conversation-title"
                placeholder="e.g. Website launch updates"
                value={conversationTitle}
                onChange={(e) => setConversationTitle(e.target.value)}
              />
            </div>

            <div className="space-y-2">
              <Label>Participants</Label>
              <div className="max-h-56 space-y-2 overflow-y-auto rounded-md border p-3">
                {participants.length === 0 ? (
                  <p className="text-sm text-muted-foreground">No available participants for your account.</p>
                ) : (
                  participants.map((participant) => (
                    <label key={participant.id} className="flex cursor-pointer items-center gap-3 rounded-md p-2 hover:bg-muted/50">
                      <Checkbox
                        checked={selectedParticipantIds.includes(participant.id)}
                        onCheckedChange={() => toggleParticipant(participant.id)}
                      />
                      <div className="min-w-0">
                        <p className="text-sm font-medium truncate">{participant.name}</p>
                        <p className="text-xs text-muted-foreground truncate">{participant.email}</p>
                      </div>
                    </label>
                  ))
                )}
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setShowNewConversationDialog(false)}>
              Cancel
            </Button>
            <Button onClick={startNewConversation} disabled={isCreatingConversation || !clientId || selectedParticipantIds.length === 0}>
              {isCreatingConversation ? "Starting..." : "Start conversation"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
