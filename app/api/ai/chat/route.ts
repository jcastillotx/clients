import { OpenAIStream, StreamingTextResponse } from "ai";
import { and, eq } from "drizzle-orm";
import { Configuration, OpenAIApi } from "openai-edge";
import { z } from "zod";
import { resolveRouteAccess } from "@/lib/auth/route-access";
import { db } from "@/lib/db";
import {
  aiConversations,
  aiMessages,
  aiUsageTracking,
} from "@/lib/db/schema/ai-features";
import { createClient } from "@/lib/supabase/server";

const requestSchema = z.object({
  conversationId: z.string().uuid().optional(),
  messages: z
    .array(
      z.object({
        role: z.enum(["user", "assistant", "system"]),
        content: z.string().min(1),
      }),
    )
    .min(1),
});

const config = new Configuration({
  apiKey: process.env.OPENAI_API_KEY,
});
const openai = new OpenAIApi(config);

export const runtime = "nodejs";

export async function POST(req: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return new Response("Unauthorized", { status: 401 });
    }

    if (!process.env.OPENAI_API_KEY) {
      return new Response("AI provider unavailable", { status: 503 });
    }

    const access = await resolveRouteAccess(supabase, user);
    const json = await req.json();
    const parsed = requestSchema.safeParse(json);
    if (!parsed.success) {
      return new Response("Invalid request payload", { status: 400 });
    }

    const { messages, conversationId } = parsed.data;

    let conversation: typeof aiConversations.$inferSelect | undefined;
    if (conversationId) {
      const existing = await db
        .select()
        .from(aiConversations)
        .where(
          and(
            eq(aiConversations.id, conversationId),
            eq(aiConversations.userId, user.id),
          ),
        )
        .limit(1);
      conversation = existing[0];
    }

    if (!conversation) {
      const [newConversation] = await db
        .insert(aiConversations)
        .values({
          userId: user.id,
          clientId: access.clientId,
          title: messages[0]?.content?.substring(0, 50) || "New conversation",
          context: {
            systemPrompt:
              "You are a helpful AI assistant for client management.",
            temperature: 0.7,
            maxTokens: 2000,
          },
          metadata: {
            tags: ["chat"],
            category: "general",
          },
        })
        .returning();
      conversation = newConversation;
    }

    const response = await openai.createChatCompletion({
      model: "gpt-4",
      messages: [
        {
          role: "system",
          content:
            "You are a helpful AI assistant for client management. Help users with tasks related to clients, invoices, projects, and business analytics.",
        },
        ...messages,
      ],
      temperature: 0.7,
      stream: true,
    });

    const stream = OpenAIStream(response, {
      async onCompletion(completion) {
        try {
          const userMessage = messages[messages.length - 1];
          const assistantMessage = completion;

          await db.insert(aiMessages).values({
            conversationId: conversation.id,
            role: "user",
            content: userMessage.content,
            tokensUsed: Math.ceil(userMessage.content.length / 4),
            model: "gpt-4",
          });

          await db.insert(aiMessages).values({
            conversationId: conversation.id,
            role: "assistant",
            content: assistantMessage,
            tokensUsed: Math.ceil(assistantMessage.length / 4),
            model: "gpt-4",
            cost: (Math.ceil(assistantMessage.length / 4) * 0.00003).toFixed(6),
          });

          await db.insert(aiUsageTracking).values({
            userId: user.id,
            clientId: access.clientId,
            provider: "openai",
            model: "gpt-4",
            tokensUsed: Math.ceil(
              (userMessage.content.length + assistantMessage.length) / 4,
            ),
            cost: (
              Math.ceil(
                (userMessage.content.length + assistantMessage.length) / 4,
              ) * 0.00003
            ).toFixed(6),
            requestType: "chat",
            metadata: {
              conversationId: conversation.id,
            },
          });
        } catch (error) {
          console.error("Error saving messages:", error);
        }
      },
    });

    return new StreamingTextResponse(stream);
  } catch (error) {
    console.error("Chat API error:", error);
    return new Response("Internal Server Error", { status: 500 });
  }
}
