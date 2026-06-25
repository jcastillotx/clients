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
  const proposalTitleForFeedback = String(data.proposalTitle || "Proposal");
  const ticketSubject = String(data.ticketSubject || "Support ticket");
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

    case "project_request_feedback_created": {
      const subject = `New Project Request Feedback: ${requestTitle}`;
      const message = `New feedback was submitted for project request: ${requestTitle}.`;
      return { subject, message, html: htmlWrap(subject, message) };
    }

    case "service_request_created": {
      const title = String(data.requestTitle || data.request_title || requestTitle);
      const subject = `New service request: ${title}`;
      const message = `A new service request was created: ${title}.`;
      return { subject, message, html: htmlWrap(subject, message) };
    }

    case "support_ticket_created": {
      const subject = `New Support Ticket: ${ticketSubject}`;
      const message = `A new support ticket was submitted: ${ticketSubject}.`;
      return { subject, message, html: htmlWrap(subject, message) };
    }

    case "proposal_feedback_created": {
      const subject = `New Proposal Feedback: ${proposalTitleForFeedback}`;
      const message = `New feedback was submitted for proposal: ${proposalTitleForFeedback}.`;
      return { subject, message, html: htmlWrap(subject, message) };
    }

    case "staff_task_created": {
      const taskTitle = String(data.taskTitle || data.task_title || "Task");
      const subject = `New task: ${taskTitle}`;
      const message = `A new task was created: ${taskTitle}.`;
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
