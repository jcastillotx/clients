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
          id,
          company_name,
          domain,
          primary_contact:users!clients_primary_contact_id_fkey(
            id,
            name,
            email
          )
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

    // Generate PDF stream
    const stream = await renderToStream(<InvoicePDF invoice={invoice} />);

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
        "Content-Disposition": `attachment; filename="invoice-${invoice.invoice_number}.pdf"`,
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
