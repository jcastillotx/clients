import type { SupabaseClient } from "@supabase/supabase-js";
import { InvoicePDF } from "@/lib/pdf/invoice-pdf";
import { renderToStream } from "@react-pdf/renderer";

/**
 * Build the same invoice payload as GET /api/invoices/[id]/pdf and return a PDF buffer.
 * Used for email attachments and the PDF download route.
 */
export async function generateInvoicePdfBuffer(
  supabase: SupabaseClient,
  invoiceId: string,
): Promise<{ buffer: Buffer; invoiceNumber: string }> {
  const { data: invoice, error: invoiceError } = await supabase
    .from("invoices")
    .select(
      `
        *,
        client:clients(
          *
        ),
        invoice_items(
          id,
          description,
          quantity,
          unit_price,
          amount
        )
      `,
    )
    .eq("id", invoiceId)
    .single();

  if (invoiceError || !invoice) {
    throw new Error("Invoice not found");
  }

  const client = Array.isArray(invoice.client) ? invoice.client[0] : invoice.client;

  if (!client) {
    throw new Error("Invoice client not found");
  }

  let primaryContact: { id: string; name: string; email: string } | null = null;

  if (typeof client.primary_contact_id === "string" && client.primary_contact_id.length > 0) {
    const { data: contact, error: contactError } = await supabase
      .from("users")
      .select("id, name, email")
      .eq("id", client.primary_contact_id)
      .maybeSingle();

    if (contactError) {
      console.error("Error fetching client primary contact for PDF:", contactError);
    } else {
      primaryContact = contact;
    }
  }

  const invoiceWithPrimaryContact = {
    ...invoice,
    client: {
      ...client,
      primary_contact: primaryContact,
    },
  };

  const stream = await renderToStream(<InvoicePDF invoice={invoiceWithPrimaryContact} />);

  const chunks: Buffer[] = [];
  for await (const chunk of stream) {
    chunks.push(Buffer.isBuffer(chunk) ? chunk : Buffer.from(chunk));
  }

  const buffer = Buffer.concat(chunks);
  return { buffer, invoiceNumber: String(invoice.invoice_number) };
}
