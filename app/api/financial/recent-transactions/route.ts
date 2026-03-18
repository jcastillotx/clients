import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { requireAuthenticatedUser } from "@/lib/auth/route-guards";

export async function GET() {
  try {
    const auth = await requireAuthenticatedUser();
    if ("error" in auth) {
      return auth.error;
    }

    const canView = await hasPermission("reports.financial");
    if (!canView) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const supabase = await createClient();

    // Fetch recent invoices
    const { data: invoices } = await supabase
      .from("invoices")
      .select(
        `
        id,
        invoice_number,
        amount,
        status,
        created_at,
        paid_at,
        clients!inner(
          company_name
        )
      `,
      )
      .order("created_at", { ascending: false })
      .limit(10);

    // Transform data
    const transactions = invoices?.map(
      (invoice: {
        id: string;
        invoice_number: string | null;
        amount: number;
        status: string;
        created_at: string;
        paid_at: string | null;
        clients?: { company_name?: string } | Array<{ company_name?: string }>;
      }) => ({
        id: invoice.id,
        invoice_number: invoice.invoice_number,
        client_name: Array.isArray(invoice.clients)
          ? (invoice.clients[0]?.company_name ?? "Unknown")
          : invoice.clients?.company_name || "Unknown",
        amount: invoice.amount,
        status: invoice.status,
        date: invoice.paid_at || invoice.created_at,
      }),
    );

    return NextResponse.json({ transactions: transactions || [] });
  } catch (error) {
    console.error("Error fetching recent transactions:", error);
    return NextResponse.json(
      { error: "An unexpected error occurred. Please try again." },
      { status: 500 },
    );
  }
}
