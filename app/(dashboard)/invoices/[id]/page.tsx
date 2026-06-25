import { createClient } from "@/lib/supabase/server";
import { notFound, redirect } from "next/navigation";
import { InvoiceDetail } from "@/components/invoices/invoice-detail";
import { InvoiceItems } from "@/components/invoices/invoice-items";
import { InvoiceActions } from "@/components/invoices/invoice-actions";
import { hasAnyRole } from "@/lib/rbac/permissions";

interface InvoiceDetailPageProps {
  params: Promise<{
    id: string;
  }>;
}

/**
 * Invoice detail page (Server Component)
 *
 * Fetches invoice with all related data (items, client, payment history).
 */
export default async function InvoiceDetailPage({ params }: InvoiceDetailPageProps) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  const canManageInvoices = user
    ? await hasAnyRole(["super_admin", "admin", "account_manager"], {
        supabase,
        userId: user.id,
      })
    : false;

  const isPlainStaff = user
    ? await hasAnyRole(["staff"], {
        supabase,
        userId: user.id,
      })
    : false;

  if (isPlainStaff && !canManageInvoices) {
    redirect("/dashboard");
  }

  // Fetch invoice with all related data
  const { data: invoice, error } = await supabase
    .from("invoices")
    .select(
      `
      *,
      client:clients(*),
      invoice_items(id, description, details, quantity, unit_price, amount)
    `,
    )
    .eq("id", id)
    .single();

  if (error || !invoice) {
    notFound();
  }

  const client = Array.isArray(invoice.client) ? invoice.client[0] : invoice.client;

  if (!client) {
    notFound();
  }

  const userIds = Array.from(
    new Set(
      [invoice.created_by, client.primary_contact_id].filter(
        (id): id is string => typeof id === "string" && id.length > 0,
      ),
    ),
  );

  let usersById = new Map<string, { id: string; name: string; email: string; avatar?: string | null }>();

  if (userIds.length > 0) {
    const { data: users, error: usersError } = await supabase
      .from("users")
      .select("id, name, email, avatar")
      .in("id", userIds);

    if (usersError) {
      console.error("Error fetching invoice users:", usersError);
    } else {
      usersById = new Map((users ?? []).map((user) => [user.id, user]));
    }
  }

  const createdByUser =
    (typeof invoice.created_by === "string" ? usersById.get(invoice.created_by) : null) ?? {
      id: typeof invoice.created_by === "string" ? invoice.created_by : "unknown",
      name: "Unknown User",
      avatar: null,
    };

  const invoiceWithUsers = {
    ...invoice,
    client: {
      ...client,
      primary_contact:
        typeof client.primary_contact_id === "string" ? usersById.get(client.primary_contact_id) ?? null : null,
    },
    created_by_user: createdByUser,
  };

  return (
    <div className="flex flex-col gap-8 p-8 max-w-6xl mx-auto">
      {/* Invoice header and details */}
      <InvoiceDetail invoice={invoiceWithUsers} canManageInvoices={canManageInvoices} />

      {/* Invoice items table */}
      <InvoiceItems
        items={invoiceWithUsers.invoice_items || []}
        subtotal={invoiceWithUsers.subtotal}
        taxRate={invoiceWithUsers.tax_rate}
        taxAmount={invoiceWithUsers.tax_amount}
        discountAmount={invoiceWithUsers.discount_amount}
        total={invoiceWithUsers.amount}
      />

      {/* Invoice actions (send, mark paid, download PDF, etc.) */}
      <InvoiceActions invoice={invoiceWithUsers} canManageInvoices={canManageInvoices} />
    </div>
  );
}
