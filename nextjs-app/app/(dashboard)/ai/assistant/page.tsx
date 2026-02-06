import { Suspense } from "react";
import { Metadata } from "next";
import { ChatInterface } from "@/components/ai/chat-interface";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

export const metadata: Metadata = {
  title: "AI Assistant",
  description: "Chat with AI to get help with your tasks",
};

function ChatLoading() {
  return (
    <div className="space-y-4">
      <Skeleton className="h-12 w-full" />
      <Skeleton className="h-96 w-full" />
      <Skeleton className="h-20 w-full" />
    </div>
  );
}

export default function AiAssistantPage() {
  return (
    <div className="container mx-auto p-6">
      <div className="mb-6">
        <h1 className="text-3xl font-bold tracking-tight">AI Assistant</h1>
        <p className="text-muted-foreground mt-2">
          Get instant help with client management, task automation, and business insights
        </p>
      </div>

      <div className="grid gap-6 lg:grid-cols-[1fr_300px]">
        <Card className="col-span-1">
          <CardHeader>
            <CardTitle>Chat</CardTitle>
            <CardDescription>Ask questions, get recommendations, or automate tasks</CardDescription>
          </CardHeader>
          <CardContent>
            <Suspense fallback={<ChatLoading />}>
              <ChatInterface />
            </Suspense>
          </CardContent>
        </Card>

        <Card className="col-span-1">
          <CardHeader>
            <CardTitle>Quick Actions</CardTitle>
            <CardDescription>Common AI-powered tasks</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            <button className="w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors">
              <div className="font-medium">Summarize Client Activity</div>
              <div className="text-sm text-muted-foreground">Get a summary of recent client interactions</div>
            </button>
            <button className="w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors">
              <div className="font-medium">Generate Invoice</div>
              <div className="text-sm text-muted-foreground">Create an invoice from project data</div>
            </button>
            <button className="w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors">
              <div className="font-medium">Analyze Performance</div>
              <div className="text-sm text-muted-foreground">Review business metrics and trends</div>
            </button>
            <button className="w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors">
              <div className="font-medium">Draft Email</div>
              <div className="text-sm text-muted-foreground">Generate professional client emails</div>
            </button>
          </CardContent>
        </Card>
      </div>

      <div className="mt-6 grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium">Conversations</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">24</div>
            <p className="text-xs text-muted-foreground">Active this month</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium">Tokens Used</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">156.2K</div>
            <p className="text-xs text-muted-foreground">23% of monthly quota</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium">Est. Cost</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">$12.45</div>
            <p className="text-xs text-muted-foreground">This month</p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
