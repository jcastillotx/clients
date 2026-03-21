import type { NotificationEventType } from "./types";

type BuiltNotification = {
  subject: string;
  message: string;
  html: string;
};

function htmlWrap(title: string, body: string): string {
  return `
    <div style="font-family: Arial, sans-serif; line-height: 1.5; color: #111827;">
      <h2 style="margin: 0 0 12px;">${title}</h2>
      <p style="margin: 0;">${body}</p>
    </div>
  `;
}

export function buildNotificationTemplate(
  eventType: NotificationEventType,
  data: Record<string, unknown> = {},
): BuiltNotification {
  const proposalTitle = String(data.proposalTitle || "Proposal");
  const invoiceNumber = String(data.invoiceNumber || "Invoice");
  const requestTitle = String(data.requestTitle || "Request");
  const amount = data.amount ? String(data.amount) : null;

  switch (eventType) {
    case "proposal_sent": {
      const subject = `Proposal Sent: ${proposalTitle}`;
      const message = `${proposalTitle} has been sent and is ready for client review.`;
      return { subject, message, html: htmlWrap(subject, message) };
    }

    case "proposal_accepted": {
      const subject = `Proposal Accepted: ${proposalTitle}`;
      const message = `${proposalTitle} was accepted by the client.`;
      return { subject, message, html: htmlWrap(subject, message) };
    }

    case "proposal_rejected": {
      const subject = `Proposal Rejected: ${proposalTitle}`;
      const message = `${proposalTitle} was rejected by the client.`;
      return { subject, message, html: htmlWrap(subject, message) };
    }

    case "project_request_created": {
      const subject = `New Project Request: ${requestTitle}`;
      const message = `A new project request was submitted: ${requestTitle}.`;
      return { subject, message, html: htmlWrap(subject, message) };
    }

    case "invoice_paid": {
      const subject = `Invoice Paid: ${invoiceNumber}`;
      const message = amount
        ? `Payment received for ${invoiceNumber} (${amount}).`
        : `Payment received for ${invoiceNumber}.`;
      return { subject, message, html: htmlWrap(subject, message) };
    }

    case "invoice_payment_failed": {
      const subject = `Payment Failed: ${invoiceNumber}`;
      const message = `Payment failed for ${invoiceNumber}. Please review and retry.`;
      return { subject, message, html: htmlWrap(subject, message) };
    }

    case "invoice_refunded": {
      const subject = `Invoice Refunded: ${invoiceNumber}`;
      const message = amount
        ? `${invoiceNumber} has been refunded (${amount}).`
        : `${invoiceNumber} has been refunded.`;
      return { subject, message, html: htmlWrap(subject, message) };
    }

    case "subscription_updated": {
      const subject = "Subscription Updated";
      const message =
        "A subscription update event was received for this client.";
      return { subject, message, html: htmlWrap(subject, message) };
    }

    default: {
      const subject = "System Notification";
      const message = "A new system event was recorded.";
      return { subject, message, html: htmlWrap(subject, message) };
    }
  }
}
