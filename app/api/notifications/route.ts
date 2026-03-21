import { NextResponse } from "next/server";
import { and, desc, eq, sql } from "drizzle-orm";
import { db } from "@/lib/db";
import {
  messageReads,
  messages,
  conversations,
} from "@/lib/db/schema/messages";
import { users } from "@/lib/db/schema/users";
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

export async function GET() {
  try {
    const user = await getCurrentUser();
    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const [dbUser] = await db
      .select({ clientId: users.clientId })
      .from(users)
      .where(eq(users.id, user.id))
      .limit(1);

    if (!dbUser?.clientId) {
      return NextResponse.json({ notifications: [], unreadCount: 0 });
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
      return NextResponse.json({ notifications: [], unreadCount: 0 });
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
      (count: number, notification: (typeof notifications)[number]) =>
        count + (notification.isRead ? 0 : 1),
      0,
    );

    return NextResponse.json({ notifications, unreadCount });
  } catch (error) {
    console.error("Error fetching notifications:", error);
    return NextResponse.json(
      { error: "Failed to fetch notifications" },
      { status: 500 },
    );
  }
}

export async function POST() {
  try {
    const user = await getCurrentUser();
    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const [dbUser] = await db
      .select({ clientId: users.clientId })
      .from(users)
      .where(eq(users.id, user.id))
      .limit(1);

    if (!dbUser?.clientId) {
      return NextResponse.json({ success: true, marked: 0 });
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
      return NextResponse.json({ success: true, marked: 0 });
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
      return NextResponse.json({ success: true, marked: 0 });
    }

    await db.insert(messageReads).values(
      unread.map((row: (typeof unread)[number]) => ({
        messageId: row.id,
        userId: user.id,
        readAt: new Date(),
      })),
    );

    return NextResponse.json({ success: true, marked: unread.length });
  } catch (error) {
    console.error("Error marking notifications as read:", error);
    return NextResponse.json(
      { error: "Failed to mark notifications as read" },
      { status: 500 },
    );
  }
}
