import { and, desc, eq, sql } from "drizzle-orm";
import { db } from "@/lib/db";
import {
  messageReads,
  messages,
  conversations,
} from "@/lib/db/schema/messages";
import { users } from "@/lib/db/schema/users";
import {
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { createClient } from "@/lib/supabase/server";

const SYSTEM_CONVERSATION_TITLE = "System Notifications";

async function getCurrentUser() {
  const supabase = await createClient();
  const {
    data: { user },
    error,
  } = await supabase.auth.getUser();

  if (error || !user) {
    return null;
  }

  return user;
}

export async function GET(request: Request) {
  try {
    const user = await getCurrentUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const [dbUser] = await db
      .select({ clientId: users.clientId })
      .from(users)
      .where(eq(users.id, user.id))
      .limit(1);

    if (!dbUser?.clientId) {
      const empty = { notifications: [], unreadCount: 0 };
      return apiSuccess(request, empty, { extra: empty });
    }

    const [conversation] = await db
      .select({ id: conversations.id })
      .from(conversations)
      .where(
        and(
          eq(conversations.clientId, dbUser.clientId),
          eq(conversations.title, SYSTEM_CONVERSATION_TITLE),
          eq(conversations.contextType, "general"),
        ),
      )
      .limit(1);

    if (!conversation) {
      const empty = { notifications: [], unreadCount: 0 };
      return apiSuccess(request, empty, { extra: empty });
    }

    const notifications = await db
      .select({
        id: messages.id,
        body: messages.body,
        meta: messages.meta,
        createdAt: messages.createdAt,
        isRead: sql<boolean>`EXISTS(
          SELECT 1 FROM ${messageReads}
          WHERE ${messageReads.messageId} = ${messages.id}
          AND ${messageReads.userId} = ${user.id}
        )`,
      })
      .from(messages)
      .where(
        and(
          eq(messages.conversationId, conversation.id),
          eq(messages.type, "system"),
        ),
      )
      .orderBy(desc(messages.createdAt))
      .limit(50);

    const unreadCount = notifications.reduce(
      (count, notification) => count + (notification.isRead ? 0 : 1),
      0,
    );

    const payload = { notifications, unreadCount };

    return apiSuccess(request, payload, { extra: payload });
  } catch (error) {
    console.error("Error fetching notifications:", error);
    return apiInternalError(request, "Failed to fetch notifications");
  }
}

export async function POST(request: Request) {
  try {
    const user = await getCurrentUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const [dbUser] = await db
      .select({ clientId: users.clientId })
      .from(users)
      .where(eq(users.id, user.id))
      .limit(1);

    if (!dbUser?.clientId) {
      return apiSuccess(request, { marked: 0 }, { extra: { marked: 0 } });
    }

    const [conversation] = await db
      .select({ id: conversations.id })
      .from(conversations)
      .where(
        and(
          eq(conversations.clientId, dbUser.clientId),
          eq(conversations.title, SYSTEM_CONVERSATION_TITLE),
          eq(conversations.contextType, "general"),
        ),
      )
      .limit(1);

    if (!conversation) {
      return apiSuccess(request, { marked: 0 }, { extra: { marked: 0 } });
    }

    const unread = await db
      .select({ id: messages.id })
      .from(messages)
      .leftJoin(
        messageReads,
        and(
          eq(messageReads.messageId, messages.id),
          eq(messageReads.userId, user.id),
        ),
      )
      .where(
        and(
          eq(messages.conversationId, conversation.id),
          eq(messages.type, "system"),
          sql`${messageReads.id} IS NULL`,
        ),
      );

    if (unread.length === 0) {
      return apiSuccess(request, { marked: 0 }, { extra: { marked: 0 } });
    }

    await db.insert(messageReads).values(
      unread.map((row) => ({
        messageId: row.id,
        userId: user.id,
        readAt: new Date(),
      })),
    );

    const payload = { marked: unread.length };

    return apiSuccess(request, payload, { extra: payload });
  } catch (error) {
    console.error("Error marking notifications as read:", error);
    return apiInternalError(request, "Failed to mark notifications as read");
  }
}
