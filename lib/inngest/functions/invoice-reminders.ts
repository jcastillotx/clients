import { inngest } from "../client";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { sendInvoiceEmailWithPdf } from "@/lib/email/send-invoice-email";
import { resolveInvoiceRecipientEmail } from "@/lib/email/invoice-email-context";

// Send invoice reminders daily at 9am PST
export const sendInvoiceReminders = inngest.createFunction(
  {
    id: "send-invoice-reminders",
    name: "Send Invoice Reminders",
  },
  { cron: "TZ=America/Los_Angeles 0 9 * * *" }, // Daily at 9am PST
  async ({ step }) => {
    const admin = createAdminClientIfAvailable();
    if (!admin) {
      console.error("sendInvoiceReminders: SUPABASE_SERVICE_KEY not configured; skipping.");
      return { skipped: true, reason: "no_admin_client" };
    }

    // Step 1: Find invoices due in 7 days
    const dueSoonInvoices = await step.run("find-due-soon-invoices", async () => {
      const targetDate = new Date();
      targetDate.setDate(targetDate.getDate() + 7);

      const { data } = await admin
        .from("invoices")
        .select(
          `
          *,
          client:clients(id, company_name, email, contact_name, primary_contact_id)
        `,
        )
        .eq("status", "sent")
        .lte("due_date", targetDate.toISOString())
        .is("reminded_due_7_at", null);

      return data || [];
    });

    // Step 2: Send reminders for invoices due soon
    for (const invoice of dueSoonInvoices) {
      await step.run(`send-due-soon-reminder-${invoice.id}`, async () => {
        const client = Array.isArray(invoice.client) ? invoice.client[0] : invoice.client;
        if (!client) return;

        let primaryContact: { id: string; name: string | null; email: string | null } | null = null;
        if (typeof client.primary_contact_id === "string" && client.primary_contact_id.length > 0) {
          const { data: contact } = await admin
            .from("users")
            .select("id, name, email")
            .eq("id", client.primary_contact_id)
            .maybeSingle();
          primaryContact = contact;
        }

        const to = resolveInvoiceRecipientEmail(client, primaryContact);
        if (!to) return;

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
          console.error(`Due-soon reminder failed for ${invoice.id}:`, sent.error);
          return;
        }

        await admin.from("invoices").update({ reminded_due_7_at: new Date().toISOString() }).eq("id", invoice.id);
      });
    }

    // Step 3: Find overdue invoices
    const overdueInvoices = await step.run("find-overdue-invoices", async () => {
      const { data } = await admin
        .from("invoices")
        .select(
          `
          *,
          client:clients(id, company_name, email, contact_name, primary_contact_id)
        `,
        )
        .eq("status", "sent")
        .lt("due_date", new Date().toISOString())
        .is("reminded_overdue_at", null);

      return data || [];
    });

    // Step 4: Send overdue reminders
    for (const invoice of overdueInvoices) {
      await step.run(`send-overdue-reminder-${invoice.id}`, async () => {
        const client = Array.isArray(invoice.client) ? invoice.client[0] : invoice.client;
        if (!client) return;

        let primaryContact: { id: string; name: string | null; email: string | null } | null = null;
        if (typeof client.primary_contact_id === "string" && client.primary_contact_id.length > 0) {
          const { data: contact } = await admin
            .from("users")
            .select("id, name, email")
            .eq("id", client.primary_contact_id)
            .maybeSingle();
          primaryContact = contact;
        }

        const to = resolveInvoiceRecipientEmail(client, primaryContact);
        if (!to) return;

        const daysOverdue = Math.max(
          1,
          Math.floor(
            (new Date().getTime() - new Date(invoice.due_date as string).getTime()) / (1000 * 60 * 60 * 24),
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
          console.error(`Overdue reminder failed for ${invoice.id}:`, sent.error);
          return;
        }

        await admin
          .from("invoices")
          .update({
            reminded_overdue_at: new Date().toISOString(),
            status: "overdue",
          })
          .eq("id", invoice.id);
      });
    }

    return {
      dueSoon: dueSoonInvoices.length,
      overdue: overdueInvoices.length,
    };
  },
);
