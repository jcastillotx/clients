import { Inngest } from "inngest";

// Create Inngest client
export const inngest = new Inngest({
  id: "kre8iv-clients",
  name: "Kre8iv Clients Platform",
  eventKey: process.env.INNGEST_EVENT_KEY,
});

// Event type definitions for type safety
export interface InvoiceReminderEvent {
  name: "invoice/reminder.scheduled";
  data: {
    invoiceId: string;
    type: "due_soon" | "overdue";
  };
}

export interface RecurringInvoiceEvent {
  name: "invoice/recurring.generate";
  data: {
    templateId: string;
  };
}

export interface SLACheckEvent {
  name: "sla/check.scheduled";
  data: {
    requestId?: string;
  };
}

export interface BrandMonitoringEvent {
  name: "brand/monitor.scheduled";
  data: {
    clientId?: string;
    keywords?: string[];
  };
}

export interface AnalyticsReportEvent {
  name: "analytics/report.scheduled";
  data: {
    period: "daily" | "weekly" | "monthly" | "quarterly";
    clientId?: string;
  };
}

// Type union for all events
export type Events =
  | InvoiceReminderEvent
  | RecurringInvoiceEvent
  | SLACheckEvent
  | BrandMonitoringEvent
  | AnalyticsReportEvent;
