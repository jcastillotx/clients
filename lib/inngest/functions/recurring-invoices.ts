import { inngest } from "../client";
import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { sendInvoiceEmailWithPdf } from "@/lib/email/send-invoice-email";
import { resolveInvoiceRecipientEmail } from "@/lib/email/invoice-email-context";
import { calculateNextRecurringDate, type RecurringInterval } from "@/lib/invoices/recurring";

// Generate recurring invoices daily
export const generateRecurringInvoices = inngest.createFunction(
  {
    id: "generate-recurring-invoices",
    name: "Generate Recurring Invoices",
  },
  { cron: "0 2 * * *" }, // Daily at 2am UTC
  async ({ step }) => {
    const supabase = createAdminClientIfAvailable() ?? (await createClient());

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
        .neq("status", "cancelled")
        .lte("next_recurring_date", new Date().toISOString());

      return data || [];
    });

    // Step 2: Generate new invoices
    const generatedInvoices = [];

    for (const template of recurringInvoices) {
      const newInvoice = await step.run(`generate-invoice-${template.id}`, async () => {
        // Calculate next invoice number
        const { data: invoiceNumber, error: invoiceNumberError } = await supabase.rpc("generate_invoice_number");
        if (invoiceNumberError) {
          console.warn("generateRecurringInvoices: generate_invoice_number unavailable", invoiceNumberError);
        }
        const generatedInvoiceNumber =
          typeof invoiceNumber === "string" && invoiceNumber.trim().length > 0
            ? invoiceNumber
            : `${template.invoice_number}-${Date.now()}`;

        // Calculate next due date
        const nextDueDate = new Date();
        const daysUntilDue = template.payment_terms || 30;
        nextDueDate.setDate(nextDueDate.getDate() + daysUntilDue);

        const recurringInterval = (template.recurring_interval || "monthly") as RecurringInterval;
        const nextRecurringDate = calculateNextRecurringDate(new Date(), recurringInterval);

        // Create new invoice
        const { data: newInvoice, error } = await supabase
          .from("invoices")
          .insert({
            invoice_number: generatedInvoiceNumber,
            client_id: template.client_id,
            due_date: nextDueDate.toISOString(),
            amount: template.amount,
            subtotal: template.subtotal,
            tax_rate: template.tax_rate,
            tax_amount: template.tax_amount,
            discount_type: template.discount_type,
            discount_value: template.discount_value,
            discount_amount: template.discount_amount,
            status: "draft",
            notes: template.notes,
            is_recurring: false, // Generated invoice is not recurring
            created_by: template.created_by,
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
              details: item.details,
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
            next_recurring_date: nextRecurringDate.toISOString(),
            updated_at: new Date().toISOString(),
          })
          .eq("id", template.id);

        return newInvoice;
      });

      if (newInvoice) {
        generatedInvoices.push(newInvoice);
      }
    }

    // Step 3: Email PDF + pay link (same template as manual send)
    const admin = createAdminClientIfAvailable();
    if (admin) {
      for (const invoice of generatedInvoices) {
        await step.run(`send-notification-${invoice.id}`, async () => {
          const { data: client } = await admin
            .from("clients")
            .select("company_name, email, contact_name, primary_contact_id")
            .eq("id", invoice.client_id)
            .maybeSingle();

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
            templateType: "invoice_sent",
          });

          if (sent.success) {
            await admin
              .from("invoices")
              .update({ status: "sent", updated_at: new Date().toISOString() })
              .eq("id", invoice.id);
          } else {
            console.error(`Recurring invoice email failed for ${invoice.id}:`, sent.error);
          }
        });
      }
    } else {
      console.error("generateRecurringInvoices: SUPABASE_SERVICE_KEY missing; skipping invoice emails.");
    }

    return {
      templates: recurringInvoices.length,
      generated: generatedInvoices.length,
    };
  },
);
