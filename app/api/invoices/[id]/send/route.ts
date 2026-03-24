import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { sendInvoiceEmailWithPdf } from "@/lib/email/send-invoice-email";
import {
  resolveInvoiceRecipientEmail,
} from "@/lib/email/invoice-email-context";
import { hasPermission, Permissions } from "@/lib/rbac/permissions";

/**
 * POST /api/invoices/[id]/send
 * Sends the invoice email (PDF attachment + pay link) and sets status to "sent".
 */
export async function POST(_request: Request, { params }: { params: Promise<{ id: string }> }) {
  try {
    const { id } = await params;
    const supabase = await createClient();

    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const canSend =
      (await hasPermission(Permissions.INVOICES_SEND, { supabase, userId: user.id })) ||
      (await hasPermission(Permissions.INVOICES_UPDATE, { supabase, userId: user.id }));

    if (!canSend) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const { data: invoice, error: invError } = await supabase
      .from("invoices")
      .select(
        `
        id,
        status,
        client_id,
        client:clients(
          company_name,
          contact_name,
          email,
          primary_contact_id
        )
      `,
      )
      .eq("id", id)
      .is("deleted_at", null)
      .maybeSingle();

    if (invError || !invoice) {
      return NextResponse.json({ error: "Invoice not found" }, { status: 404 });
    }

    if (!["draft", "sent"].includes(invoice.status)) {
      return NextResponse.json(
        { error: "This invoice cannot be emailed in its current status." },
        { status: 400 },
      );
    }

    const client = Array.isArray(invoice.client) ? invoice.client[0] : invoice.client;
    if (!client) {
      return NextResponse.json({ error: "Client not found for invoice" }, { status: 400 });
    }

    let primaryContact: { id: string; name: string | null; email: string | null } | null = null;
    if (typeof client.primary_contact_id === "string" && client.primary_contact_id.length > 0) {
      const { data: contact } = await supabase
        .from("users")
        .select("id, name, email")
        .eq("id", client.primary_contact_id)
        .maybeSingle();
      primaryContact = contact;
    }

    const recipient = resolveInvoiceRecipientEmail(client, primaryContact);
    if (!recipient) {
      return NextResponse.json(
        { error: "No billing email on file. Add a primary contact email or client email." },
        { status: 400 },
      );
    }

    const emailed = await sendInvoiceEmailWithPdf(supabase, {
      invoiceId: id,
      to: recipient,
      templateType: "invoice_sent",
    });

    if (!emailed.success) {
      return NextResponse.json(
        { error: emailed.error ?? "Failed to send invoice email" },
        { status: 502 },
      );
    }

    const { error: updateError } = await supabase
      .from("invoices")
      .update({
        status: "sent",
        updated_at: new Date().toISOString(),
      })
      .eq("id", id);

    if (updateError) {
      console.error("Invoice sent email ok but status update failed:", updateError);
      return NextResponse.json(
        { error: "Email sent but failed to update invoice status. Please refresh." },
        { status: 500 },
      );
    }

    return NextResponse.json({ success: true, sentTo: recipient });
  } catch (error) {
    console.error("POST /api/invoices/[id]/send:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to send invoice" },
      { status: 500 },
    );
  }
}
