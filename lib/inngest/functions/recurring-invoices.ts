import { z } from "zod";
import { inngest } from "../client";
import {
  createJobClient,
  scheduledJobsEnabled,
  assertJobResult,
} from "../job-runtime";
import { generateRecurringInvoice } from "@/lib/invoices/generate-recurring";
import { sendInvoiceEmailWithPdf } from "@/lib/email/send-invoice-email";
import { resolveInvoiceRecipientEmail } from "@/lib/email/invoice-email-context";

export const generateRecurringInvoices = inngest.createFunction(
  {
    id: "generate-recurring-invoices",
    name: "Generate Recurring Invoices",
    concurrency: 1,
  },
  { cron: "0 2 * * *" },
  async ({ step }) => {
    if (!scheduledJobsEnabled())
      return { skipped: true, reason: "schedules_disabled" };
    const admin = createJobClient();
    const templates = await step.run("find-recurring-invoices", async () => {
      const { data, error } = await admin
        .from("invoices")
        .select("id, next_recurring_date")
        .eq("is_recurring", true)
        .neq("status", "cancelled")
        .is("deleted_at", null)
        .lte("next_recurring_date", new Date().toISOString());
      assertJobResult(error, "Read recurring invoices");
      return z
        .array(
          z.object({ id: z.string().guid(), next_recurring_date: z.string() }),
        )
        .parse(data ?? []);
    });
    let generated = 0;
    for (const template of templates) {
      const invoice = await step.run(`generate-invoice-${template.id}`, () =>
        generateRecurringInvoice(template.id, template.next_recurring_date),
      );
      if (!invoice) continue;
      generated++;
      await step.run(`send-notification-${invoice.id}`, async () => {
        const { data: current, error: invoiceError } = await admin
          .from("invoices")
          .select("status")
          .eq("id", invoice.id)
          .single();
        assertJobResult(invoiceError, "Read generated invoice state");
        if (current?.status !== "draft") return;
        const { data: client, error } = await admin
          .from("clients")
          .select("company_name, email, contact_name, primary_contact_id")
          .eq("id", invoice.clientId)
          .is("deleted_at", null)
          .single();
        assertJobResult(error, "Read recurring invoice client");
        if (!client) throw new Error("Recurring invoice client unavailable");
        let primaryContact = null;
        if (client.primary_contact_id) {
          const { data: contact, error: contactError } = await admin
            .from("users")
            .select("id, name, email")
            .eq("id", client.primary_contact_id)
            .maybeSingle();
          assertJobResult(contactError, "Read recurring invoice contact");
          primaryContact = contact;
        }
        const to = resolveInvoiceRecipientEmail(client, primaryContact);
        if (!to) throw new Error("Recurring invoice recipient unavailable");
        const sent = await sendInvoiceEmailWithPdf(admin, {
          invoiceId: invoice.id,
          to,
          templateType: "invoice_sent",
        });
        if (!sent.success)
          throw new Error("Recurring invoice email delivery failed");
        const { error: updateError } = await admin
          .from("invoices")
          .update({ status: "sent", updated_at: new Date().toISOString() })
          .eq("id", invoice.id)
          .eq("status", "draft");
        assertJobResult(updateError, "Save recurring invoice delivery state");
      });
    }
    return { templates: templates.length, generated };
  },
);
