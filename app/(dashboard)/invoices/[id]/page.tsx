import { createClient } from "@/lib/supabase/server";
import { notFound } from "next/navigation";
import { InvoiceDetail } from "@/components/invoices/invoice-detail";
import { InvoiceItems } from "@/components/invoices/invoice-items";
import { InvoiceActions } from "@/components/invoices/invoice-actions";

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

  // Fetch invoice with all related data
  const { data: invoice, error } = await supabase
    .from("invoices")
    .select(
      `
      *,
      client:clients(id, company_name, domain, primary_contact:users!clients_primary_contact_id_fkey(id, name, email)),
      created_by_user:users!invoices_created_by_fkey(id, name, avatar),
      invoice_items(id, description, quantity, unit_price, amount)
    `,
    )
    .eq("id", id)
    .single();

  if (error || !invoice) {
    notFound();
  }

  return (
    <div className="flex flex-col gap-8 p-8 max-w-6xl mx-auto">
      {/* Invoice header and details */}
      <InvoiceDetail invoice={invoice} />

      {/* Invoice items table */}
      <InvoiceItems items={invoice.invoice_items || []} total={invoice.amount} />

      {/* Invoice actions (send, mark paid, download PDF, etc.) */}
      <InvoiceActions invoice={invoice} />
    </div>
  );
}
