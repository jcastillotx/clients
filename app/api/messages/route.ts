import { NextRequest } from "next/server";
import { db } from "@/lib/db";
import { messages, conversations, messageAttachments, conversationParticipants, users } from "@/lib/db/schema";
import { eq, desc, and, sql } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

/**
 * GET /api/messages?conversationId={id}
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

    const { searchParams } = new URL(request.url);
    const conversationId = searchParams.get("conversationId");
    const limit = parseInt(searchParams.get("limit") || "50");
    const offset = parseInt(searchParams.get("offset") || "0");

    if (!conversationId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "conversationId is required",
      });
    }

    const [participant] = await db
      .select()
      .from(conversationParticipants)
      .where(
        and(eq(conversationParticipants.conversationId, conversationId), eq(conversationParticipants.userId, user.id)),
      )
      .limit(1);

    if (!participant) {
      return apiForbidden(request, "Access denied to this conversation");
    }

    const conversationMessages = await db
      .select({
        id: messages.id,
        conversationId: messages.conversationId,
        senderId: messages.senderId,
        body: messages.body,
        type: messages.type,
        meta: messages.meta,
        mentions: messages.mentions,
        isPinned: messages.isPinned,
        pinnedAt: messages.pinnedAt,
        pinnedBy: messages.pinnedBy,
        createdAt: messages.createdAt,
        updatedAt: messages.updatedAt,
        sender: {
          id: users.id,
          name: users.name,
          email: users.email,
          avatar: users.avatar,
        },
        attachments: sql<
          Array<{
            id: string;
            filename: string;
            mimeType: string | null;
            sizeBytes: number;
            path: string;
          }>
        >`(
          SELECT json_agg(json_build_object(
            'id', id,
            'filename', filename,
            'mimeType', mime_type,
            'sizeBytes', size_bytes,
            'path', path
          ))
          FROM ${messageAttachments}
          WHERE message_id = ${messages.id}
        )`,
        isRead: sql<boolean>`EXISTS(
          SELECT 1 FROM message_reads
          WHERE message_id = ${messages.id}
          AND user_id = ${user.id}
        )`,
      })
      .from(messages)
      .leftJoin(users, eq(messages.senderId, users.id))
      .where(eq(messages.conversationId, conversationId))
      .orderBy(desc(messages.createdAt))
      .limit(limit)
      .offset(offset);

    return apiSuccess(request, conversationMessages, {
      extra: { messages: conversationMessages },
    });
  } catch (error) {
    console.error("Error fetching messages:", error);
    return apiInternalError(request, "Failed to fetch messages");
  }
}

/**
 * POST /api/messages
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
    const { conversationId, messageBody, type = "text", attachments = [] } = body;

    if (!conversationId || (!messageBody && attachments.length === 0)) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Missing required fields",
      });
    }

    const [participant] = await db
      .select()
      .from(conversationParticipants)
      .where(
        and(eq(conversationParticipants.conversationId, conversationId), eq(conversationParticipants.userId, user.id)),
      )
      .limit(1);

    if (!participant) {
      return apiForbidden(request, "Access denied to this conversation");
    }

    const [message] = await db
      .insert(messages)
      .values({
        conversationId,
        senderId: user.id,
        body: messageBody,
        type,
      })
      .returning();

    if (attachments.length > 0) {
      await db.insert(messageAttachments).values(
        attachments.map(
          (att: { path: string; filename: string; mimeType: string; sizeBytes: number; disk?: string }) => ({
            messageId: message.id,
            path: att.path,
            filename: att.filename,
            mimeType: att.mimeType,
            sizeBytes: att.sizeBytes,
            disk: att.disk || "attachments",
          }),
        ),
      );
    }

    await db.update(conversations).set({ lastMessageAt: new Date() }).where(eq(conversations.id, conversationId));

    return apiSuccess(request, message, { status: 201, extra: { message } });
  } catch (error) {
    console.error("Error sending message:", error);
    return apiInternalError(request, "Failed to send message");
  }
}
