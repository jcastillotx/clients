import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { messages, conversations, messageAttachments, conversationParticipants, users } from "@/lib/db/schema/messages";
import { eq, desc, and, sql } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";

/**
 * GET /api/messages?conversationId={id}
 * Get messages for a specific conversation
 */
export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const { searchParams } = new URL(request.url);
    const conversationId = searchParams.get("conversationId");
    const limit = parseInt(searchParams.get("limit") || "50");
    const offset = parseInt(searchParams.get("offset") || "0");

    if (!conversationId) {
      return NextResponse.json({ error: "conversationId is required" }, { status: 400 });
    }

    // Verify user is a participant in this conversation
    const [participant] = await db
      .select()
      .from(conversationParticipants)
      .where(
        and(eq(conversationParticipants.conversationId, conversationId), eq(conversationParticipants.userId, user.id)),
      )
      .limit(1);

    if (!participant) {
      return NextResponse.json({ error: "Access denied to this conversation" }, { status: 403 });
    }

    // Get messages with sender info and attachments
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

    return NextResponse.json({ messages: conversationMessages });
  } catch (error) {
    console.error("Error fetching messages:", error);
    return NextResponse.json({ error: "Failed to fetch messages" }, { status: 500 });
  }
}

/**
 * POST /api/messages
 * Send a new message
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const body = await request.json();
    const { conversationId, messageBody, type = "text", attachments = [] } = body;

    if (!conversationId || (!messageBody && attachments.length === 0)) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    // Verify user is a participant
    const [participant] = await db
      .select()
      .from(conversationParticipants)
      .where(
        and(eq(conversationParticipants.conversationId, conversationId), eq(conversationParticipants.userId, user.id)),
      )
      .limit(1);

    if (!participant) {
      return NextResponse.json({ error: "Access denied to this conversation" }, { status: 403 });
    }

    // Create message
    const [message] = await db
      .insert(messages)
      .values({
        conversationId,
        senderId: user.id,
        body: messageBody,
        type,
      })
      .returning();

    // Add attachments if provided
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

    // Update conversation last_message_at
    await db.update(conversations).set({ lastMessageAt: new Date() }).where(eq(conversations.id, conversationId));

    return NextResponse.json({ message }, { status: 201 });
  } catch (error) {
    console.error("Error sending message:", error);
    return NextResponse.json({ error: "Failed to send message" }, { status: 500 });
  }
}
