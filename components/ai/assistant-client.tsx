"use client";

import { useRef } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ChatInterface, ChatInterfaceHandle } from "@/components/ai/chat-interface";

const QUICK_ACTIONS = [
  {
    label: "Summarize Client Activity",
    description: "Get a summary of recent client interactions",
    prompt: "Please summarize the recent activity and interactions across my clients. Include any open requests, overdue invoices, and upcoming meetings.",
  },
  {
    label: "Generate Invoice",
    description: "Create an invoice from project data",
    prompt: "Help me generate an invoice. Please ask me for the client name, project details, line items, and any other information you need.",
  },
  {
    label: "Analyze Performance",
    description: "Review business metrics and trends",
    prompt: "Please analyze my business performance. What are the key metrics I should focus on, and what trends should I be aware of?",
  },
  {
    label: "Draft Email",
    description: "Generate professional client emails",
    prompt: "Help me draft a professional email to a client. Please ask me for the client name, purpose of the email, and any key points to include.",
  },
] as const;

interface AssistantStats {
  conversationCount: number;
  tokensUsed: number;
  estimatedCost: number;
}

interface AssistantClientProps {
  stats: AssistantStats;
}

export function AssistantClient({ stats }: AssistantClientProps) {
  const chatRef = useRef<ChatInterfaceHandle>(null);

  const handleQuickAction = (prompt: string) => {
    chatRef.current?.setPrompt(prompt);
  };

  const formattedTokens =
    stats.tokensUsed >= 1000
      ? `${(stats.tokensUsed / 1000).toFixed(1)}K`
      : String(stats.tokensUsed);

  return (
    <>
      <div className="grid gap-6 lg:grid-cols-[1fr_300px]">
        <Card className="col-span-1">
          <CardHeader>
            <CardTitle>Chat</CardTitle>
            <CardDescription>Ask questions, get recommendations, or automate tasks</CardDescription>
          </CardHeader>
          <CardContent>
            <ChatInterface ref={chatRef} />
          </CardContent>
        </Card>

        <Card className="col-span-1">
          <CardHeader>
            <CardTitle>Quick Actions</CardTitle>
            <CardDescription>Common AI-powered tasks</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {QUICK_ACTIONS.map((action) => (
              <button
                key={action.label}
                className="w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors"
                onClick={() => handleQuickAction(action.prompt)}
              >
                <div className="font-medium">{action.label}</div>
                <div className="text-sm text-muted-foreground">{action.description}</div>
              </button>
            ))}
          </CardContent>
        </Card>
      </div>

      <div className="mt-6 grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium">Conversations</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.conversationCount}</div>
            <p className="text-xs text-muted-foreground">Active this month</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium">Tokens Used</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formattedTokens}</div>
            <p className="text-xs text-muted-foreground">This month</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium">Est. Cost</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              ${stats.estimatedCost.toFixed(2)}
            </div>
            <p className="text-xs text-muted-foreground">This month</p>
          </CardContent>
        </Card>
      </div>
    </>
  );
}
