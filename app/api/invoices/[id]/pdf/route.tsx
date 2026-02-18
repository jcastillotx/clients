import { createClient } from "@/lib/supabase/server";
import { InvoicePDF } from "@/lib/pdf/invoice-pdf";
import { renderToStream } from "@react-pdf/renderer";
import { NextResponse } from "next/server";

export async function GET(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();

    // Check authentication
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    // Fetch invoice with full details
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
      `
      )
      .eq("id", id)
      .single();

    if (invoiceError || !invoice) {
      return NextResponse.json({ error: "Invoice not found" }, { status: 404 });
    }

    const client = Array.isArray(invoice.client) ? invoice.client[0] : invoice.client;

    if (!client) {
      return NextResponse.json({ error: "Invoice client not found" }, { status: 404 });
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

    // Generate PDF stream
    const stream = await renderToStream(<InvoicePDF invoice={invoiceWithPrimaryContact} />);

    // Convert stream to buffer for Next.js response
    const chunks: any[] = [];
    for await (const chunk of stream) {
      chunks.push(chunk);
    }
    const buffer = Buffer.concat(chunks);

    // Return PDF with appropriate headers
    return new NextResponse(buffer, {
      headers: {
        "Content-Type": "application/pdf",
        "Content-Disposition": `attachment; filename="invoice-${invoiceWithPrimaryContact.invoice_number}.pdf"`,
        "Cache-Control": "no-cache",
      },
    });
  } catch (error) {
    console.error("Error generating PDF:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to generate PDF" },
      { status: 500 }
    );
  }
}
