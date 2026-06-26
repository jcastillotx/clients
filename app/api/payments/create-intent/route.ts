import { createClient } from "@/lib/supabase/server";
import { getStripe } from "@/lib/stripe/client";
import { resolveStripeCredentialsForClient } from "@/lib/stripe/settings";
import {
  apiError,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

export async function POST(request: Request) {
  try {
    const supabase = await createClient();

    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    const { invoiceId } = await request.json();

    if (!invoiceId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Invoice ID is required",
      });
    }

    const { data: invoice, error: invoiceError } = await supabase
      .from("invoices")
      .select(
        `
        *,
        client:clients(
          *
        )
      `,
      )
      .eq("id", invoiceId)
      .single();

    if (invoiceError || !invoice) {
      return apiNotFound(request, "Invoice not found");
    }

    const client = Array.isArray(invoice.client) ? invoice.client[0] : invoice.client;

    if (!client) {
      return apiNotFound(request, "Invoice client not found");
    }

    let primaryContact: { id: string; name: string; email: string } | null = null;

    if (typeof client.primary_contact_id === "string" && client.primary_contact_id.length > 0) {
      const { data: contact, error: contactError } = await supabase
        .from("users")
        .select("id, name, email")
        .eq("id", client.primary_contact_id)
        .maybeSingle();

      if (contactError) {
        console.error("Error fetching client primary contact:", contactError);
      } else {
        primaryContact = contact;
      }
    }

    if (invoice.status === "paid") {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Invoice is already paid",
      });
    }

    if (invoice.status === "cancelled") {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Invoice is cancelled",
      });
    }

    const stripeCredentials = await resolveStripeCredentialsForClient(invoice.client_id);
    if (!stripeCredentials.secret_key) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Online payments are not configured yet. Please contact support to pay this invoice.",
      });
    }

    const stripe = getStripe(stripeCredentials.secret_key);
    let customerId = client.stripe_customer_id;

    if (!customerId && primaryContact?.email) {
      const customer = await stripe.customers.create({
        email: primaryContact.email,
        name: client.company_name,
        metadata: {
          client_id: client.id,
          supabase_user_id: primaryContact.id,
        },
      });

      customerId = customer.id;

      await supabase.from("clients").update({ stripe_customer_id: customerId }).eq("id", client.id);
    }

    const paymentIntent = await stripe.paymentIntents.create({
      amount: Math.round(invoice.amount * 100),
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

    await supabase
      .from("invoices")
      .update({
        stripe_payment_intent_id: paymentIntent.id,
        status: "sent",
      })
      .eq("id", invoice.id);

    const payload = {
      clientSecret: paymentIntent.client_secret,
      paymentIntentId: paymentIntent.id,
    };

    return apiSuccess(request, payload, { extra: payload });
  } catch (error) {
    console.error("Error creating payment intent:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to create payment intent",
    );
  }
}
