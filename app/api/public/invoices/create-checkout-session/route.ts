import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { getStripe } from "@/lib/stripe/client";
import { createPublicInvoiceCheckoutSchema } from "@/lib/validations/public-invoice-payment";

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
    const adminClient = createAdminClientIfAvailable();
    if (!adminClient) {
      return NextResponse.json(
        { error: "Payment service is unavailable. Missing server configuration." },
        { status: 500 },
      );
    }

    const body = await request.json();
    const payload = createPublicInvoiceCheckoutSchema.parse(body);
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
      return NextResponse.json({ error: "Invoice not found. Please verify the invoice number." }, { status: 404 });
    }

    if (invoice.status === "paid") {
      return NextResponse.json({ error: "This invoice is already paid." }, { status: 400 });
    }

    if (invoice.status === "cancelled") {
      return NextResponse.json({ error: "This invoice has been cancelled and cannot be paid." }, { status: 400 });
    }

    const invoiceAmount = Number(invoice.amount);
    if (!Number.isFinite(invoiceAmount) || invoiceAmount <= 0) {
      return NextResponse.json({ error: "Invoice amount is invalid. Please contact support." }, { status: 500 });
    }

    const requestedAmountCents = toCents(payload.paymentAmount);
    const invoiceAmountCents = toCents(invoiceAmount);
    if (requestedAmountCents !== invoiceAmountCents) {
      return NextResponse.json(
        {
          error: "Payment amount must match the full invoice total.",
          invoiceAmount,
        },
        { status: 400 },
      );
    }

    // Move draft invoices to sent before payment collection.
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
      return NextResponse.json({ error: "Unable to initialize checkout session." }, { status: 500 });
    }

    return NextResponse.json({
      checkoutUrl: session.url,
      sessionId: session.id,
    });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }

    console.error("Error creating public invoice checkout session:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to create checkout session",
      },
      { status: 500 },
    );
  }
}
