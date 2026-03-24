import { createClient } from "@/lib/supabase/server";
import { generateInvoicePdfBuffer } from "@/lib/invoices/generate-invoice-pdf-buffer";
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

    let buffer: Buffer;
    let invoiceNumber: string;
    try {
      const generated = await generateInvoicePdfBuffer(supabase, id);
      buffer = generated.buffer;
      invoiceNumber = generated.invoiceNumber;
    } catch {
      return NextResponse.json({ error: "Invoice not found" }, { status: 404 });
    }

    // Return PDF with appropriate headers
    return new NextResponse(new Uint8Array(buffer), {
      headers: {
        "Content-Type": "application/pdf",
        "Content-Disposition": `attachment; filename="invoice-${invoiceNumber}.pdf"`,
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
