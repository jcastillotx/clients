import { NextRequest } from "next/server";
import { db } from "@/lib/db";
import { messageReads, messages, conversationParticipants } from "@/lib/db/schema/messages";
import { eq, and } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

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
      return apiUnauthorized(request);
    }

    const { id: messageId } = await params;

    const [message] = await db.select().from(messages).where(eq(messages.id, messageId)).limit(1);

    if (!message) {
      return apiNotFound(request, "Message not found");
    }

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
      return apiForbidden(request, "Access denied to this conversation");
    }

    const [existingRead] = await db
      .select()
      .from(messageReads)
      .where(and(eq(messageReads.messageId, messageId), eq(messageReads.userId, user.id)))
      .limit(1);

    if (existingRead) {
      return apiSuccess(request, existingRead, {
        extra: { message: "Already marked as read" },
      });
    }

    const [read] = await db
      .insert(messageReads)
      .values({
        messageId,
        userId: user.id,
        readAt: new Date(),
      })
      .returning();

    return apiSuccess(request, read, { extra: { read } });
  } catch (error) {
    console.error("Error marking message as read:", error);
    return apiInternalError(request, "Failed to mark message as read");
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
      return apiUnauthorized(request);
    }

    const { id: messageId } = await params;

    await db.delete(messageReads).where(and(eq(messageReads.messageId, messageId), eq(messageReads.userId, user.id)));

    return apiSuccess(request, { removed: true }, {
      extra: { message: "Read status removed" },
    });
  } catch (error) {
    console.error("Error removing read status:", error);
    return apiInternalError(request, "Failed to remove read status");
  }
}
