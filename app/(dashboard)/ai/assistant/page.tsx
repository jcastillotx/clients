import { Metadata } from "next";
import { createClient } from "@/lib/supabase/server";
import { AssistantClient } from "@/components/ai/assistant-client";

export const metadata: Metadata = {
  title: "AI Assistant",
  description: "Chat with AI to get help with your tasks",
};

export default async function AiAssistantPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  let conversationCount = 0;
  let tokensUsed = 0;
  let estimatedCost = 0;

  if (user) {
    const startOfMonth = new Date();
    startOfMonth.setDate(1);
    startOfMonth.setHours(0, 0, 0, 0);

    const [convResult, usageResult] = await Promise.all([
      supabase
        .from("ai_conversations")
        .select("id", { count: "exact", head: true })
        .eq("user_id", user.id)
        .gte("created_at", startOfMonth.toISOString()),
      supabase
        .from("ai_usage_tracking")
        .select("tokens_used, cost")
        .eq("user_id", user.id)
        .gte("created_at", startOfMonth.toISOString()),
    ]);

    conversationCount = convResult.count ?? 0;

    if (usageResult.data) {
      tokensUsed = usageResult.data.reduce((sum, row) => sum + (row.tokens_used ?? 0), 0);
      estimatedCost = usageResult.data.reduce((sum, row) => sum + parseFloat(String(row.cost ?? "0")), 0);
    }
  }

  return (
    <div className="container mx-auto p-6">
      <div className="mb-6">
        <h1 className="text-3xl font-bold tracking-tight">AI Assistant</h1>
        <p className="text-muted-foreground mt-2">
          Get instant help with client management, task automation, and business insights
        </p>
      </div>

      <AssistantClient
        stats={{ conversationCount, tokensUsed, estimatedCost }}
      />
    </div>
  );
}
