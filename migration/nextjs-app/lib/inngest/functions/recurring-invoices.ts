import { inngest } from "../client";
import { createClient } from "@/lib/supabase/server";
import { sendEmail } from "@/lib/email/client";
import { renderEmailTemplate } from "@/lib/email/templates";

// Generate recurring invoices daily
export const generateRecurringInvoices = inngest.createFunction(
  {
    id: "generate-recurring-invoices",
    name: "Generate Recurring Invoices",
  },
  { cron: "0 2 * * *" }, // Daily at 2am UTC
  async ({ step }) => {
    const supabase = createClient();

    // Step 1: Find active recurring invoices due for generation
    const recurringInvoices = await step.run("find-recurring-invoices", async () => {
      const { data } = await supabase
        .from("invoices")
        .select(
          `
          *,
          client:clients(id, company_name, email, contact_name)
        `,
        )
        .eq("is_recurring", true)
        .eq("status", "active")
        .lte("next_generation_date", new Date().toISOString());

      return data || [];
    });

    // Step 2: Generate new invoices
    const generatedInvoices = [];

    for (const template of recurringInvoices) {
      const newInvoice = await step.run(`generate-invoice-${template.id}`, async () => {
        // Calculate next invoice number
        const { data: invoiceNumber } = await supabase.rpc("generate_invoice_number");

        // Calculate next due date
        const nextDueDate = new Date();
        const daysUntilDue = template.payment_terms || 30;
        nextDueDate.setDate(nextDueDate.getDate() + daysUntilDue);

        // Calculate next generation date based on frequency
        const nextGenDate = new Date();
        switch (template.recurring_frequency) {
          case "monthly":
            nextGenDate.setMonth(nextGenDate.getMonth() + 1);
            break;
          case "quarterly":
            nextGenDate.setMonth(nextGenDate.getMonth() + 3);
            break;
          case "annually":
            nextGenDate.setFullYear(nextGenDate.getFullYear() + 1);
            break;
        }

        // Create new invoice
        const { data: newInvoice, error } = await supabase
          .from("invoices")
          .insert({
            invoice_number: invoiceNumber,
            client_id: template.client_id,
            issue_date: new Date().toISOString(),
            due_date: nextDueDate.toISOString(),
            amount: template.amount,
            tax_amount: template.tax_amount,
            discount_amount: template.discount_amount,
            currency: template.currency,
            status: "draft",
            payment_terms: template.payment_terms,
            notes: template.notes,
            is_recurring: false, // Generated invoice is not recurring
            recurring_parent_id: template.id,
          })
          .select()
          .single();

        if (error) {
          console.error(`Failed to generate invoice from template ${template.id}:`, error);
          return null;
        }

        // Copy invoice items
        const { data: items } = await supabase.from("invoice_items").select("*").eq("invoice_id", template.id);

        if (items && items.length > 0) {
          await supabase.from("invoice_items").insert(
            items.map((item) => ({
              invoice_id: newInvoice.id,
              description: item.description,
              quantity: item.quantity,
              unit_price: item.unit_price,
              amount: item.amount,
            })),
          );
        }

        // Update template's next generation date
        await supabase
          .from("invoices")
          .update({
            next_generation_date: nextGenDate.toISOString(),
            last_generated_at: new Date().toISOString(),
          })
          .eq("id", template.id);

        return newInvoice;
      });

      if (newInvoice) {
        generatedInvoices.push(newInvoice);
      }
    }

    // Step 3: Send notifications
    for (const invoice of generatedInvoices) {
      await step.run(`send-notification-${invoice.id}`, async () => {
        // Fetch client info
        const { data: client } = await supabase
          .from("clients")
          .select("company_name, email, contact_name")
          .eq("id", invoice.client_id)
          .single();

        if (!client?.email) return;

        const templateData = {
          invoice: {
            invoice_number: invoice.invoice_number,
            amount: invoice.amount,
            currency: invoice.currency,
            due_date: invoice.due_date,
          },
          client: {
            company_name: client.company_name,
            contact_name: client.contact_name,
          },
          invoice_url: `${process.env.NEXT_PUBLIC_APP_URL}/invoices/${invoice.id}`,
        };

        const rendered = await renderEmailTemplate("invoice_generated", templateData);
        if (!rendered) return;

        await sendEmail({
          to: client.email,
          subject: rendered.subject,
          html: rendered.html,
          text: rendered.plainText,
        });
      });
    }

    return {
      templates: recurringInvoices.length,
      generated: generatedInvoices.length,
    };
  },
);
