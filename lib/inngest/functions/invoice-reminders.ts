import { inngest } from "../client";
import {
  createJobClient,
  scheduledJobsEnabled,
  assertJobResult,
} from "../job-runtime";
import { sendInvoiceEmailWithPdf } from "@/lib/email/send-invoice-email";
import { resolveInvoiceRecipientEmail } from "@/lib/email/invoice-email-context";

// Send invoice reminders daily at 9am PST
export const sendInvoiceReminders = inngest.createFunction(
  {
    id: "send-invoice-reminders",
    name: "Send Invoice Reminders",
    concurrency: 1,
  },
  { cron: "TZ=America/Los_Angeles 0 9 * * *" }, // Daily at 9am PST
  async ({ step }) => {
    if (!scheduledJobsEnabled())
      return { skipped: true, reason: "schedules_disabled" };
    const admin = createJobClient();

    // Step 1: Find invoices due in 7 days
    const dueSoonInvoices = await step.run(
      "find-due-soon-invoices",
      async () => {
        const targetDate = new Date();
        targetDate.setDate(targetDate.getDate() + 7);

        const { data, error } = await admin
          .from("invoices")
          .select(
            `
          *,
          client:clients(id, company_name, email, contact_name, primary_contact_id)
        `,
          )
          .eq("status", "sent")
          .is("deleted_at", null)
          .gte("due_date", new Date().toISOString())
          .lte("due_date", targetDate.toISOString())
          .is("reminded_due_7_at", null);

        if (error)
          throw new Error(`Invoice reminder query failed: ${error.message}`);
        return data || [];
      },
    );

    // Step 2: Send reminders for invoices due soon
    for (const invoice of dueSoonInvoices) {
      await step.run(`send-due-soon-reminder-${invoice.id}`, async () => {
        const client = Array.isArray(invoice.client)
          ? invoice.client[0]
          : invoice.client;
        if (!client) throw new Error("Invoice reminder client unavailable");

        let primaryContact: {
          id: string;
          name: string | null;
          email: string | null;
        } | null = null;
        if (
          typeof client.primary_contact_id === "string" &&
          client.primary_contact_id.length > 0
        ) {
          const { data: contact, error: contactError } = await admin
            .from("users")
            .select("id, name, email")
            .eq("id", client.primary_contact_id)
            .maybeSingle();
          assertJobResult(contactError, "Read invoice primary contact");
          primaryContact = contact;
        }

        const to = resolveInvoiceRecipientEmail(client, primaryContact);
        if (!to) throw new Error("Invoice reminder recipient unavailable");

        const sent = await sendInvoiceEmailWithPdf(admin, {
          invoiceId: invoice.id,
          to,
          templateType: "invoice_reminder",
          extraTemplateData: {
            days_until_due: "7",
            overdue_status: "until due",
          },
        });

        if (!sent.success) {
          throw new Error("Due-soon invoice reminder delivery failed");
        }

        const { error: updateError } = await admin
          .from("invoices")
          .update({ reminded_due_7_at: new Date().toISOString() })
          .eq("id", invoice.id);
        assertJobResult(updateError, "Save invoice reminder state");
      });
    }

    // Step 3: Find overdue invoices
    const overdueInvoices = await step.run(
      "find-overdue-invoices",
      async () => {
        const { data, error } = await admin
          .from("invoices")
          .select(
            `
          *,
          client:clients(id, company_name, email, contact_name, primary_contact_id)
        `,
          )
          .eq("status", "sent")
          .is("deleted_at", null)
          .lt("due_date", new Date().toISOString())
          .is("reminded_overdue_at", null);

        if (error)
          throw new Error(`Invoice reminder query failed: ${error.message}`);
        return data || [];
      },
    );

    // Step 4: Send overdue reminders
    for (const invoice of overdueInvoices) {
      await step.run(`send-overdue-reminder-${invoice.id}`, async () => {
        const client = Array.isArray(invoice.client)
          ? invoice.client[0]
          : invoice.client;
        if (!client) throw new Error("Invoice reminder client unavailable");

        let primaryContact: {
          id: string;
          name: string | null;
          email: string | null;
        } | null = null;
        if (
          typeof client.primary_contact_id === "string" &&
          client.primary_contact_id.length > 0
        ) {
          const { data: contact, error: contactError } = await admin
            .from("users")
            .select("id, name, email")
            .eq("id", client.primary_contact_id)
            .maybeSingle();
          assertJobResult(contactError, "Read invoice primary contact");
          primaryContact = contact;
        }

        const to = resolveInvoiceRecipientEmail(client, primaryContact);
        if (!to) throw new Error("Invoice reminder recipient unavailable");

        const daysOverdue = Math.max(
          1,
          Math.floor(
            (new Date().getTime() -
              new Date(invoice.due_date as string).getTime()) /
              (1000 * 60 * 60 * 24),
          ),
        );

        const sent = await sendInvoiceEmailWithPdf(admin, {
          invoiceId: invoice.id,
          to,
          templateType: "invoice_overdue",
          extraTemplateData: {
            days_overdue: String(daysOverdue),
          },
        });

        if (!sent.success) {
          throw new Error("Overdue invoice reminder delivery failed");
        }

        const { error: updateError } = await admin
          .from("invoices")
          .update({
            reminded_overdue_at: new Date().toISOString(),
            status: "overdue",
          })
          .eq("id", invoice.id);
        assertJobResult(updateError, "Save invoice reminder state");
      });
    }

    return {
      dueSoon: dueSoonInvoices.length,
      overdue: overdueInvoices.length,
    };
  },
);
