import { NextRequest } from "next/server";
import { z } from "zod";

import {
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiValidationError,
} from "@/lib/api/response";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { lookupPublicInvoiceSchema } from "@/lib/validations/public-invoice-payment";

function formatAmount(value: unknown): string {
  const amount = Number(value);
  return Number.isFinite(amount) ? amount.toFixed(2) : "";
}

export async function GET(request: NextRequest) {
  try {
    const parsed = lookupPublicInvoiceSchema.parse({
      invoice: request.nextUrl.searchParams.get("invoice") ?? "",
    });

    const adminClient = createAdminClientIfAvailable();
    if (!adminClient) {
      return apiInternalError(
        request,
        "Payment service is unavailable. Missing server configuration.",
      );
    }

    const { data: invoice, error } = await adminClient
      .from("invoices")
      .select(
        `
        invoice_number,
        amount,
        status,
        due_date,
        client:clients(company_name, email)
      `,
      )
      .eq("invoice_number", parsed.invoice.trim())
      .is("deleted_at", null)
      .single();

    if (error || !invoice) {
      return apiNotFound(request, "Invoice not found. Please verify the invoice number.");
    }

    const clientRecord = Array.isArray(invoice.client) ? invoice.client[0] : invoice.client;
    const payload = {
      invoiceNumber: invoice.invoice_number,
      amount: Number(invoice.amount),
      amountFormatted: formatAmount(invoice.amount),
      status: invoice.status,
      dueDate: invoice.due_date,
      businessName: clientRecord?.company_name ?? "",
      email: clientRecord?.email ?? "",
    };

    return apiSuccess(request, payload, { extra: payload });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }

    console.error("Error looking up public invoice:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to load invoice",
    );
  }
}
