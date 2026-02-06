import { StreamingTextResponse, OpenAIStream } from "ai";
import { Configuration, OpenAIApi } from "openai-edge";
import { db } from "@/lib/db";
import { aiConversations, aiMessages, aiUsageTracking } from "@/lib/db/schema/ai-features";
import { eq } from "drizzle-orm";

const config = new Configuration({
  apiKey: process.env.OPENAI_API_KEY,
});
const openai = new OpenAIApi(config);

export const runtime = "nodejs";

export async function POST(req: Request) {
  try {
    const { messages, conversationId } = await req.json();

    // Get or create conversation
    let conversation;
    if (conversationId) {
      const existing = await db.select().from(aiConversations).where(eq(aiConversations.id, conversationId)).limit(1);
      conversation = existing[0];
    }

    if (!conversation) {
      // Create new conversation
      const [newConversation] = await db
        .insert(aiConversations)
        .values({
          userId: "user-id-placeholder", // TODO: Get from session
          title: messages[0]?.content?.substring(0, 50) || "New conversation",
          context: {
            systemPrompt: "You are a helpful AI assistant for client management.",
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

    // Call OpenAI
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

    // Stream the response
    const stream = OpenAIStream(response, {
      async onCompletion(completion) {
        try {
          // Store the messages
          const userMessage = messages[messages.length - 1];
          const assistantMessage = completion;

          // Save user message
          await db.insert(aiMessages).values({
            conversationId: conversation.id,
            role: "user",
            content: userMessage.content,
            tokensUsed: Math.ceil(userMessage.content.length / 4), // Rough estimate
            model: "gpt-4",
          });

          // Save assistant message
          const [savedMessage] = await db
            .insert(aiMessages)
            .values({
              conversationId: conversation.id,
              role: "assistant",
              content: assistantMessage,
              tokensUsed: Math.ceil(assistantMessage.length / 4), // Rough estimate
              model: "gpt-4",
              cost: (Math.ceil(assistantMessage.length / 4) * 0.00003).toFixed(6), // GPT-4 pricing
            })
            .returning();

          // Track usage
          await db.insert(aiUsageTracking).values({
            userId: "user-id-placeholder", // TODO: Get from session
            provider: "openai",
            model: "gpt-4",
            tokensUsed: Math.ceil((userMessage.content.length + assistantMessage.length) / 4),
            cost: (Math.ceil((userMessage.content.length + assistantMessage.length) / 4) * 0.00003).toFixed(6),
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
