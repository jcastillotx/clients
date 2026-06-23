import { NextRequest } from "next/server";
import { db } from "@/lib/db";
import { conversations, conversationParticipants, messages } from "@/lib/db/schema/messages";
import { users } from "@/lib/db/schema/users";
import { eq, desc, sql } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

/**
 * GET /api/messages/conversations
 * List all conversations for the current user
 */
export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const userConversations = await db
      .select({
        id: conversations.id,
        clientId: conversations.clientId,
        title: conversations.title,
        contextType: conversations.contextType,
        contextId: conversations.contextId,
        isClosed: conversations.isClosed,
        lastMessageAt: conversations.lastMessageAt,
        createdAt: conversations.createdAt,
        updatedAt: conversations.updatedAt,
        lastMessage: sql<string>`(
          SELECT body
          FROM ${messages}
          WHERE ${messages.conversationId} = ${conversations.id}
          ORDER BY created_at DESC
          LIMIT 1
        )`,
        lastMessageType: sql<string>`(
          SELECT type
          FROM ${messages}
          WHERE ${messages.conversationId} = ${conversations.id}
          ORDER BY created_at DESC
          LIMIT 1
        )`,
        unreadCount: sql<number>`(
          SELECT COUNT(*)::int
          FROM ${messages} m
          LEFT JOIN message_reads mr ON m.id = mr.message_id AND mr.user_id = ${user.id}
          WHERE m.conversation_id = ${conversations.id}
          AND m.sender_id != ${user.id}
          AND mr.id IS NULL
        )`,
        participants: sql<Array<{ id: string; name: string; email: string; avatar: string | null }>>`(
          SELECT json_agg(json_build_object(
            'id', u.id,
            'name', u.name,
            'email', u.email,
            'avatar', u.avatar
          ))
          FROM ${conversationParticipants} cp
          JOIN ${users} u ON cp.user_id = u.id
          WHERE cp.conversation_id = ${conversations.id}
        )`,
      })
      .from(conversations)
      .innerJoin(conversationParticipants, eq(conversationParticipants.conversationId, conversations.id))
      .where(eq(conversationParticipants.userId, user.id))
      .orderBy(desc(conversations.lastMessageAt))
      .execute();

    return apiSuccess(request, userConversations, {
      extra: { conversations: userConversations },
    });
  } catch (error) {
    console.error("Error fetching conversations:", error);
    return apiInternalError(request, "Failed to fetch conversations");
  }
}

/**
 * POST /api/messages/conversations
 * Create a new conversation
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const body = await request.json();
    const { clientId, title, participantIds, contextType, contextId } = body;

    if (!clientId || !participantIds || participantIds.length === 0) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Missing required fields",
      });
    }

    const allParticipants = Array.from(new Set([...participantIds, user.id]));

    const [conversation] = await db
      .insert(conversations)
      .values({
        clientId,
        title,
        contextType,
        contextId,
        lastMessageAt: new Date(),
      })
      .returning();

    await db.insert(conversationParticipants).values(
      allParticipants.map((userId) => ({
        conversationId: conversation.id,
        userId,
        role: (userId === user.id ? "staff" : "client") as "staff" | "client",
      })),
    );

    return apiSuccess(request, conversation, {
      status: 201,
      extra: { conversation },
    });
  } catch (error) {
    console.error("Error creating conversation:", error);
    return apiInternalError(request, "Failed to create conversation");
  }
}
