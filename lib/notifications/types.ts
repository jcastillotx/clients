export const notificationEventTypes = [
  "proposal_sent",
  "proposal_accepted",
  "proposal_rejected",
  "project_request_created",
  "invoice_paid",
  "invoice_payment_failed",
  "invoice_refunded",
  "subscription_updated",
] as const;

export type NotificationEventType = (typeof notificationEventTypes)[number];

export type NotificationPayload = {
  eventType: NotificationEventType;
  clientId: string;
  subjectType: "proposal" | "invoice" | "request" | "client";
  subjectId: string;
  actorUserId?: string;
  recipientUserIds?: string[];
  extraEmails?: string[];
  data?: Record<string, unknown>;
};
