import { inngest } from "../client";
import { createClient } from "@/lib/supabase/server";
import { sendEmail } from "@/lib/email/client";
import { renderEmailTemplate, type InvoiceReminderData } from "@/lib/email/templates";

// Send invoice reminders daily at 9am PST
export const sendInvoiceReminders = inngest.createFunction(
  {
    id: "send-invoice-reminders",
    name: "Send Invoice Reminders",
  },
  { cron: "TZ=America/Los_Angeles 0 9 * * *" }, // Daily at 9am PST
  async ({ step }) => {
    const supabase = createClient();

    // Step 1: Find invoices due in 7 days
    const dueSoonInvoices = await step.run("find-due-soon-invoices", async () => {
      const targetDate = new Date();
      targetDate.setDate(targetDate.getDate() + 7);

      const { data } = await supabase
        .from("invoices")
        .select(
          `
          *,
          client:clients(id, company_name, email, contact_name)
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
        if (!invoice.client?.email) return;

        const templateData: InvoiceReminderData = {
          invoice: {
            invoice_number: invoice.invoice_number,
            amount: invoice.amount,
            currency: invoice.currency,
            due_date: invoice.due_date,
          },
          client: {
            company_name: invoice.client.company_name,
            contact_name: invoice.client.contact_name,
          },
          days_until_due: 7,
          payment_url: `${process.env.NEXT_PUBLIC_APP_URL}/invoices/${invoice.id}/pay`,
        };

        const rendered = await renderEmailTemplate("invoice_reminder", templateData);
        if (!rendered) return;

        await sendEmail({
          to: invoice.client.email,
          subject: rendered.subject,
          html: rendered.html,
          text: rendered.plainText,
        });

        // Mark as reminded
        await supabase.from("invoices").update({ reminded_due_7_at: new Date().toISOString() }).eq("id", invoice.id);
      });
    }

    // Step 3: Find overdue invoices
    const overdueInvoices = await step.run("find-overdue-invoices", async () => {
      const { data } = await supabase
        .from("invoices")
        .select(
          `
          *,
          client:clients(id, company_name, email, contact_name)
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
        if (!invoice.client?.email) return;

        const daysOverdue = Math.floor(
          (new Date().getTime() - new Date(invoice.due_date).getTime()) / (1000 * 60 * 60 * 24),
        );

        const templateData: InvoiceReminderData = {
          invoice: {
            invoice_number: invoice.invoice_number,
            amount: invoice.amount,
            currency: invoice.currency,
            due_date: invoice.due_date,
          },
          client: {
            company_name: invoice.client.company_name,
            contact_name: invoice.client.contact_name,
          },
          days_until_due: -daysOverdue,
          payment_url: `${process.env.NEXT_PUBLIC_APP_URL}/invoices/${invoice.id}/pay`,
        };

        const rendered = await renderEmailTemplate("invoice_overdue", templateData);
        if (!rendered) return;

        await sendEmail({
          to: invoice.client.email,
          subject: rendered.subject,
          html: rendered.html,
          text: rendered.plainText,
        });

        // Mark as reminded and update status
        await supabase
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
