import { and, eq, inArray, isNull } from "drizzle-orm";
import { db } from "@/lib/db";
import { sendEmail } from "@/lib/email/client";
import { renderEmailTemplateAsAdmin } from "@/lib/email/templates";
import {
  conversationParticipants,
  conversations,
  messages,
} from "@/lib/db/schema/messages";
import { users } from "@/lib/db/schema/users";
import { parseNotificationPreferences } from "./preferences";
import { buildNotificationTemplate } from "./templates";
import type { NotificationPayload } from "./types";

const SYSTEM_CONVERSATION_TITLE = "System Notifications";

type Recipient = {
  id: string;
  email: string;
  emailEnabled: boolean;
  inAppEnabled: boolean;
};

async function resolveRecipients(
  payload: NotificationPayload,
): Promise<Recipient[]> {
  if (payload.recipientUserIds !== undefined) {
    if (payload.recipientUserIds.length === 0) {
      return [];
    }
    const rows = await db
      .select({
        id: users.id,
        email: users.email,
        securitySettings: users.securitySettings,
      })
      .from(users)
      .where(
        and(
          inArray(users.id, payload.recipientUserIds),
          eq(users.isActive, true),
          isNull(users.deletedAt),
        ),
      );
    return rows.map(
      (row: { id: string; email: string; securitySettings: unknown }) => {
        const prefs = parseNotificationPreferences(row.securitySettings);
        return {
          id: row.id,
          email: row.email,
          emailEnabled: prefs.emailEnabled,
          inAppEnabled: prefs.inAppEnabled,
        };
      },
    );
  }

  const rows = await db
    .select({
      id: users.id,
      email: users.email,
      securitySettings: users.securitySettings,
    })
    .from(users)
    .where(
      and(
        eq(users.clientId, payload.clientId),
        eq(users.isActive, true),
        isNull(users.deletedAt),
      ),
    );

  return rows.map(
    (row: { id: string; email: string; securitySettings: unknown }) => {
      const prefs = parseNotificationPreferences(row.securitySettings);
      return {
        id: row.id,
        email: row.email,
        emailEnabled: prefs.emailEnabled,
        inAppEnabled: prefs.inAppEnabled,
      };
    },
  );
}

async function ensureSystemConversation(clientId: string): Promise<string> {
  const [existing] = await db
    .select({ id: conversations.id })
    .from(conversations)
    .where(
      and(
        eq(conversations.clientId, clientId),
        eq(conversations.contextType, "general"),
        eq(conversations.title, SYSTEM_CONVERSATION_TITLE),
      ),
    )
    .limit(1);

  if (existing) {
    return existing.id;
  }

  const [created] = await db
    .insert(conversations)
    .values({
      clientId,
      title: SYSTEM_CONVERSATION_TITLE,
      contextType: "general",
      lastMessageAt: new Date(),
    })
    .returning({ id: conversations.id });

  return created.id;
}

async function ensureConversationParticipants(
  conversationId: string,
  recipientUserIds: string[],
) {
  if (recipientUserIds.length === 0) {
    return;
  }

  const existing = await db
    .select({ userId: conversationParticipants.userId })
    .from(conversationParticipants)
    .where(
      and(
        eq(conversationParticipants.conversationId, conversationId),
        inArray(conversationParticipants.userId, recipientUserIds),
      ),
    );

  const existingIds = new Set(
    existing.map((row: { userId: string }) => row.userId),
  );
  const missing = recipientUserIds.filter((id) => !existingIds.has(id));

  if (missing.length === 0) {
    return;
  }

  await db.insert(conversationParticipants).values(
    missing.map((userId) => ({
      conversationId,
      userId,
      role: "staff" as const,
    })),
  );
}

export async function dispatchNotification(
  payload: NotificationPayload,
): Promise<void> {
  const recipients = await resolveRecipients(payload);
  const inAppRecipientIds = recipients
    .filter((r) => r.inAppEnabled)
    .map((r) => r.id)
    .filter((id) => id !== payload.actorUserId);

  const { subject, message, html } = buildNotificationTemplate(
    payload.eventType,
    payload.data,
  );

  if (inAppRecipientIds.length > 0) {
    const conversationId = await ensureSystemConversation(payload.clientId);
    await ensureConversationParticipants(conversationId, inAppRecipientIds);

    await db.insert(messages).values({
      conversationId,
      senderId: payload.actorUserId || null,
      body: message,
      type: "system",
      meta: {
        notification: {
          eventType: payload.eventType,
          subjectType: payload.subjectType,
          subjectId: payload.subjectId,
          data: payload.data || {},
        },
      },
    });

    await db
      .update(conversations)
      .set({ lastMessageAt: new Date() })
      .where(eq(conversations.id, conversationId));
  }

  const recipientEmails = new Set<string>(
    recipients
      .filter((r) => r.emailEnabled)
      .map((r) => r.email)
      .filter(Boolean),
  );
  for (const email of payload.extraEmails || []) {
    if (email) {
      recipientEmails.add(email);
    }
  }

  if (recipientEmails.size > 0) {
    const fromDb = await renderEmailTemplateAsAdmin(payload.eventType, {
      app_name: process.env.NEXT_PUBLIC_APP_NAME || "Kre8iv Clients",
      ...(payload.data && typeof payload.data === "object" ? payload.data : {}),
    });
    if (fromDb) {
      await sendEmail({
        to: Array.from(recipientEmails),
        subject: fromDb.subject,
        html: fromDb.html,
        text: fromDb.plainText,
      });
    } else {
      await sendEmail({
        to: Array.from(recipientEmails),
        subject,
        html,
        text: message,
      });
    }
  }
}
