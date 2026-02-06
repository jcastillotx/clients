import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { messageReads, messages, conversationParticipants } from "@/lib/db/schema/messages";
import { eq, and } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";

/**
 * POST /api/messages/[id]/read
 * Mark a message as read
 */
export async function POST(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const { id: messageId } = await params;

    // Get the message to verify conversation access
    const [message] = await db.select().from(messages).where(eq(messages.id, messageId)).limit(1);

    if (!message) {
      return NextResponse.json({ error: "Message not found" }, { status: 404 });
    }

    // Verify user is a participant in the conversation
    const [participant] = await db
      .select()
      .from(conversationParticipants)
      .where(
        and(
          eq(conversationParticipants.conversationId, message.conversationId),
          eq(conversationParticipants.userId, user.id),
        ),
      )
      .limit(1);

    if (!participant) {
      return NextResponse.json({ error: "Access denied to this conversation" }, { status: 403 });
    }

    // Check if already marked as read
    const [existingRead] = await db
      .select()
      .from(messageReads)
      .where(and(eq(messageReads.messageId, messageId), eq(messageReads.userId, user.id)))
      .limit(1);

    if (existingRead) {
      return NextResponse.json({ message: "Already marked as read" });
    }

    // Mark as read
    const [read] = await db
      .insert(messageReads)
      .values({
        messageId,
        userId: user.id,
        readAt: new Date(),
      })
      .returning();

    return NextResponse.json({ read });
  } catch (error) {
    console.error("Error marking message as read:", error);
    return NextResponse.json({ error: "Failed to mark message as read" }, { status: 500 });
  }
}

/**
 * DELETE /api/messages/[id]/read
 * Unmark a message as read (for testing/undo)
 */
export async function DELETE(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const { id: messageId } = await params;

    await db.delete(messageReads).where(and(eq(messageReads.messageId, messageId), eq(messageReads.userId, user.id)));

    return NextResponse.json({ message: "Read status removed" });
  } catch (error) {
    console.error("Error removing read status:", error);
    return NextResponse.json({ error: "Failed to remove read status" }, { status: 500 });
  }
}
