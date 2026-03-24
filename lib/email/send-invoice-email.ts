import type { SupabaseClient } from "@supabase/supabase-js";
import { sendEmail } from "@/lib/email/client";
import { renderEmailTemplate } from "@/lib/email/templates";
import { generateInvoicePdfBuffer } from "@/lib/invoices/generate-invoice-pdf-buffer";
import {
  buildPublicPayInvoiceUrl,
  formatInvoiceAmount,
  formatInvoiceDate,
  resolveInvoiceClientDisplayName,
  resolveSenderCompanyName,
} from "@/lib/email/invoice-email-context";

type InvoiceRow = {
  id: string;
  invoice_number: string;
  amount: unknown;
  due_date: string | null;
  created_at?: string | null;
};

type ClientRow = {
  company_name?: string | null;
  contact_name?: string | null;
  email?: string | null;
  primary_contact_id?: string | null;
};

async function loadInvoiceForEmail(
  supabase: SupabaseClient,
  invoiceId: string,
): Promise<{
  invoice: InvoiceRow;
  client: ClientRow;
  primaryContact: { id: string; name: string | null; email: string | null } | null;
} | null> {
  const { data: invoice, error } = await supabase
    .from("invoices")
    .select(
      `
      id,
      invoice_number,
      amount,
      due_date,
      created_at,
      client:clients(
        company_name,
        contact_name,
        email,
        primary_contact_id
      )
    `,
    )
    .eq("id", invoiceId)
    .maybeSingle();

  if (error || !invoice) {
    console.error("send-invoice-email: invoice load failed", error);
    return null;
  }

  const client = Array.isArray(invoice.client) ? invoice.client[0] : invoice.client;
  if (!client) return null;

  let primaryContact: { id: string; name: string | null; email: string | null } | null = null;
  if (typeof client.primary_contact_id === "string" && client.primary_contact_id.length > 0) {
    const { data: contact } = await supabase
      .from("users")
      .select("id, name, email")
      .eq("id", client.primary_contact_id)
      .maybeSingle();
    primaryContact = contact;
  }

  return {
    invoice: invoice as InvoiceRow,
    client,
    primaryContact,
  };
}

function baseTemplateData(
  invoice: InvoiceRow,
  client: ClientRow,
  primaryContact: { name: string | null; email: string | null } | null,
): Record<string, string> {
  const issue = invoice.created_at ?? new Date().toISOString();
  return {
    client_name: resolveInvoiceClientDisplayName(client, primaryContact),
    company_name: resolveSenderCompanyName(),
    invoice_number: invoice.invoice_number,
    invoice_date: formatInvoiceDate(issue),
    due_date: formatInvoiceDate(invoice.due_date),
    amount: formatInvoiceAmount(invoice.amount),
    invoice_url: buildPublicPayInvoiceUrl(invoice.invoice_number),
  };
}

export async function sendInvoiceEmailWithPdf(
  supabase: SupabaseClient,
  args: {
    invoiceId: string;
    to: string;
    templateType: "invoice_sent" | "invoice_reminder" | "invoice_overdue";
    /** Merged onto base keys (e.g. days_until_due, overdue_status, days_overdue) */
    extraTemplateData?: Record<string, string>;
  },
): Promise<{ success: boolean; error?: string }> {
  const loaded = await loadInvoiceForEmail(supabase, args.invoiceId);
  if (!loaded) {
    return { success: false, error: "Invoice not found" };
  }

  const { invoice, client, primaryContact } = loaded;
  const data: Record<string, string> = {
    ...baseTemplateData(invoice, client, primaryContact),
    ...(args.extraTemplateData ?? {}),
  };

  const rendered = await renderEmailTemplate(args.templateType, data);
  if (!rendered) {
    return {
      success: false,
      error: `No active email template for type "${args.templateType}"`,
    };
  }

  let pdfBuffer: Buffer;
  let pdfInvoiceNumber: string;
  try {
    const generated = await generateInvoicePdfBuffer(supabase, args.invoiceId);
    pdfBuffer = generated.buffer;
    pdfInvoiceNumber = generated.invoiceNumber;
  } catch (e) {
    console.error("send-invoice-email: PDF generation failed", e);
    return { success: false, error: "Failed to generate invoice PDF" };
  }

  const safeNumber = pdfInvoiceNumber.replace(/[^\w.-]+/g, "_");
  const result = await sendEmail({
    to: args.to,
    subject: rendered.subject,
    html: rendered.html,
    text: rendered.plainText,
    attachments: [
      {
        filename: `invoice-${safeNumber}.pdf`,
        content: pdfBuffer,
      },
    ],
  });

  if (!result.success) {
    return { success: false, error: result.error ?? "Failed to send email" };
  }

  return { success: true };
}
