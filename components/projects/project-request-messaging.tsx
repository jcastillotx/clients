"use client";

import { useCallback, useEffect, useState } from "react";
import { Loader2, MessageCircle, RefreshCw } from "lucide-react";
import { createClient } from "@/lib/supabase/client";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ChatPane } from "@/components/messages/chat-pane";
import { fetchApi } from "@/lib/api/client";

interface ProjectRequestMessagingProps {
  requestId: string;
}

export function ProjectRequestMessaging({ requestId }: ProjectRequestMessagingProps) {
  const [currentUserId, setCurrentUserId] = useState<string | null>(null);
  const [conversationId, setConversationId] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadConversation = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      const supabase = createClient();
      const {
        data: { user },
        error: authError,
      } = await supabase.auth.getUser();

      if (authError || !user) {
        throw new Error("You must be logged in to access project messaging");
      }
      setCurrentUserId(user.id);

      const conversation = await fetchApi<{ id: string }>(
        `/api/projects/requests/${requestId}/conversation`,
        { method: "GET", cache: "no-store" },
        { fallbackMessage: "Failed to open project conversation" },
      );
      setConversationId(conversation?.id || null);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Failed to open messaging");
    } finally {
      setLoading(false);
    }
  }, [requestId]);

  useEffect(() => {
    void loadConversation();
  }, [loadConversation]);

  return (
    <Card className="h-[680px]">
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Online Messaging</CardTitle>
          <CardDescription>Dedicated chat thread for this project request.</CardDescription>
        </div>
        <Button variant="outline" size="sm" onClick={() => void loadConversation()} disabled={loading}>
          {loading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <RefreshCw className="mr-2 h-4 w-4" />}
          Refresh
        </Button>
      </CardHeader>
      <CardContent className="h-[580px]">
        {error ? <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</div> : null}

        {loading ? (
          <div className="flex h-full items-center justify-center">
            <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
          </div>
        ) : conversationId && currentUserId ? (
          <div className="h-full rounded-md border">
            <ChatPane conversationId={conversationId} currentUserId={currentUserId} />
          </div>
        ) : (
          <div className="flex h-full flex-col items-center justify-center rounded-md border border-dashed text-center">
            <MessageCircle className="mb-3 h-10 w-10 text-muted-foreground" />
            <p className="text-sm text-muted-foreground">Could not initialize conversation for this request.</p>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
