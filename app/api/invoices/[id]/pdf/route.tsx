import { createClient } from "@/lib/supabase/server";
import { generateInvoicePdfBuffer } from "@/lib/invoices/generate-invoice-pdf-buffer";
import { NextResponse } from "next/server";
import {
  apiInternalError,
  apiNotFound,
  apiUnauthorized,
} from "@/lib/api/response";

export async function GET(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();

    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    let buffer: Buffer;
    let invoiceNumber: string;
    try {
      const generated = await generateInvoicePdfBuffer(supabase, id);
      buffer = generated.buffer;
      invoiceNumber = generated.invoiceNumber;
    } catch {
      return apiNotFound(request, "Invoice not found");
    }

    return new NextResponse(new Uint8Array(buffer), {
      headers: {
        "Content-Type": "application/pdf",
        "Content-Disposition": `attachment; filename="invoice-${invoiceNumber}.pdf"`,
        "Cache-Control": "no-cache",
      },
    });
  } catch (error) {
    console.error("Error generating PDF:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to generate PDF",
    );
  }
}
