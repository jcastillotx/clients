import { NextRequest } from "next/server";
import { z } from "zod";
import { rateLimitExceededResponse } from "@/lib/api/rate-limit-response";
import {
  apiError,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiValidationError,
} from "@/lib/api/response";
import { getClientIp, limiters, rateLimitLimits } from "@/lib/rate-limit";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { getStripe } from "@/lib/stripe/client";
import { createPublicInvoiceCheckoutSchema } from "@/lib/validations/public-invoice-payment";
import { assertTurnstileToken } from "@/lib/turnstile/verify";

const toCents = (value: number) => Math.round(value * 100);

const resolveOrigin = (request: NextRequest) => {
  const configuredOrigin = process.env.NEXT_PUBLIC_APP_URL?.trim();
  if (configuredOrigin) {
    return configuredOrigin.replace(/\/+$/, "");
  }

  const forwardedHost = request.headers.get("x-forwarded-host");
  const forwardedProto = request.headers.get("x-forwarded-proto") || "https";
  if (forwardedHost) {
    return `${forwardedProto}://${forwardedHost}`.replace(/\/+$/, "");
  }

  return request.nextUrl.origin.replace(/\/+$/, "");
};

/**
 * POST /api/public/invoices/create-checkout-session
 *
 * Public endpoint that creates a Stripe Checkout session for invoice payment.
 */
export async function POST(request: NextRequest) {
  try {
    const rateLimitResult = await limiters.publicPayment(getClientIp(request));
    if (!rateLimitResult.success) {
      return rateLimitExceededResponse(
        request,
        rateLimitResult,
        rateLimitLimits.publicPayment,
      );
    }

    const adminClient = createAdminClientIfAvailable();
    if (!adminClient) {
      return apiInternalError(
        request,
        "Payment service is unavailable. Missing server configuration.",
      );
    }

    const body = await request.json();
    const payload = createPublicInvoiceCheckoutSchema.parse(body);

    const captcha = await assertTurnstileToken(
      payload.turnstileToken,
      getClientIp(request),
    );
    if (!captcha.ok) {
      return apiError(request, {
        status: captcha.status,
        code: "BAD_REQUEST",
        message: captcha.error,
      });
    }

    const normalizedInvoiceNumber = payload.invoiceNumber.trim();

    const { data: invoice, error: invoiceError } = await adminClient
      .from("invoices")
      .select(
        `
        id,
        invoice_number,
        client_id,
        amount,
        status,
        due_date,
        client:clients(id, company_name, email)
      `,
      )
      .eq("invoice_number", normalizedInvoiceNumber)
      .is("deleted_at", null)
      .single();

    if (invoiceError || !invoice) {
      return apiNotFound(request, "Invoice not found. Please verify the invoice number.");
    }

    if (invoice.status === "paid") {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "This invoice is already paid.",
      });
    }

    if (invoice.status === "cancelled") {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "This invoice has been cancelled and cannot be paid.",
      });
    }

    const invoiceAmount = Number(invoice.amount);
    if (!Number.isFinite(invoiceAmount) || invoiceAmount <= 0) {
      return apiInternalError(
        request,
        "Invoice amount is invalid. Please contact support.",
      );
    }

    const requestedAmountCents = toCents(payload.paymentAmount);
    const invoiceAmountCents = toCents(invoiceAmount);
    if (requestedAmountCents !== invoiceAmountCents) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Payment amount must match the full invoice total.",
        details: { invoiceAmount },
      });
    }

    if (invoice.status === "draft") {
      await adminClient.from("invoices").update({ status: "sent" }).eq("id", invoice.id);
    }

    const stripe = getStripe();
    const origin = resolveOrigin(request);
    const clientRecord = Array.isArray(invoice.client) ? invoice.client[0] : invoice.client;

    const session = await stripe.checkout.sessions.create({
      mode: "payment",
      customer_email: payload.email,
      client_reference_id: invoice.id,
      line_items: [
        {
          quantity: 1,
          price_data: {
            currency: "usd",
            unit_amount: requestedAmountCents,
            product_data: {
              name: `Invoice ${invoice.invoice_number}`,
              description: clientRecord?.company_name
                ? `Payment for ${clientRecord.company_name}`
                : `Payment for invoice ${invoice.invoice_number}`,
            },
          },
        },
      ],
      metadata: {
        source: "public_invoice_payment_page",
        invoice_id: invoice.id,
        invoice_number: invoice.invoice_number,
        client_id: invoice.client_id,
        payer_email: payload.email,
        business_name: payload.businessName,
        contact_name: payload.contactName,
        phone: payload.phone || "",
      },
      payment_intent_data: {
        description: `Payment for invoice ${invoice.invoice_number}`,
        metadata: {
          source: "public_invoice_payment_page",
          invoice_id: invoice.id,
          invoice_number: invoice.invoice_number,
          client_id: invoice.client_id,
          payer_email: payload.email,
          business_name: payload.businessName,
          contact_name: payload.contactName,
          phone: payload.phone || "",
          business_info: payload.businessInfo || "",
        },
      },
      billing_address_collection: "required",
      phone_number_collection: { enabled: true },
      success_url: `${origin}/pay-invoice?status=success&invoice=${encodeURIComponent(invoice.invoice_number)}`,
      cancel_url: `${origin}/pay-invoice?status=cancelled&invoice=${encodeURIComponent(invoice.invoice_number)}`,
    });

    if (!session.url) {
      return apiInternalError(request, "Unable to initialize checkout session.");
    }

    const checkout = {
      checkoutUrl: session.url,
      sessionId: session.id,
    };

    return apiSuccess(request, checkout, { extra: checkout });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }

    console.error("Error creating public invoice checkout session:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to create checkout session",
    );
  }
}
