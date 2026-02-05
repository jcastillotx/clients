import { createClient } from "@/lib/supabase/server";
import { stripe } from "@/lib/stripe/client";
import { NextResponse } from "next/server";

export async function POST(request: Request) {
  try {
    const supabase = createClient();

    // Check authentication
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const { invoiceId } = await request.json();

    if (!invoiceId) {
      return NextResponse.json({ error: "Invoice ID is required" }, { status: 400 });
    }

    // Fetch invoice with client details
    const { data: invoice, error: invoiceError } = await supabase
      .from("invoices")
      .select(
        `
        *,
        client:clients(
          id,
          company_name,
          stripe_customer_id,
          primary_contact:users!clients_primary_contact_id_fkey(
            id,
            name,
            email
          )
        )
      `,
      )
      .eq("id", invoiceId)
      .single();

    if (invoiceError || !invoice) {
      return NextResponse.json({ error: "Invoice not found" }, { status: 404 });
    }

    // Check if invoice is already paid
    if (invoice.status === "paid") {
      return NextResponse.json({ error: "Invoice is already paid" }, { status: 400 });
    }

    // Check if invoice is cancelled
    if (invoice.status === "cancelled") {
      return NextResponse.json({ error: "Invoice is cancelled" }, { status: 400 });
    }

    // Get or create Stripe customer
    let customerId = invoice.client.stripe_customer_id;

    if (!customerId && invoice.client.primary_contact) {
      // Create Stripe customer
      const customer = await stripe.customers.create({
        email: invoice.client.primary_contact.email,
        name: invoice.client.company_name,
        metadata: {
          client_id: invoice.client.id,
          supabase_user_id: invoice.client.primary_contact.id,
        },
      });

      customerId = customer.id;

      // Update client with Stripe customer ID
      await supabase.from("clients").update({ stripe_customer_id: customerId }).eq("id", invoice.client.id);
    }

    // Create payment intent
    const paymentIntent = await stripe.paymentIntents.create({
      amount: Math.round(invoice.amount * 100), // Convert to cents
      currency: "usd",
      customer: customerId || undefined,
      metadata: {
        invoice_id: invoice.id,
        invoice_number: invoice.invoice_number,
        client_id: invoice.client_id,
      },
      description: `Payment for Invoice ${invoice.invoice_number}`,
      automatic_payment_methods: {
        enabled: true,
      },
    });

    // Update invoice with payment intent ID
    await supabase
      .from("invoices")
      .update({
        stripe_payment_intent_id: paymentIntent.id,
        status: "sent", // Ensure status is at least "sent"
      })
      .eq("id", invoice.id);

    return NextResponse.json({
      clientSecret: paymentIntent.client_secret,
      paymentIntentId: paymentIntent.id,
    });
  } catch (error) {
    console.error("Error creating payment intent:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to create payment intent" },
      { status: 500 },
    );
  }
}
