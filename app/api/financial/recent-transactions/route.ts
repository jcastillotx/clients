import { createClient } from "@/lib/supabase/server";
import {
  apiForbidden,
  apiInternalError,
  apiSuccess,
} from "@/lib/api/response";
import { hasPermission } from "@/lib/rbac/permissions";
import { requireAuthenticatedUser } from "@/lib/auth/route-guards";

export async function GET(request: Request) {
  try {
    const auth = await requireAuthenticatedUser(request);
    if ("error" in auth) {
      return auth.error;
    }

    const canView = await hasPermission("reports.financial");
    if (!canView) {
      return apiForbidden(request);
    }

    const supabase = await createClient();

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

    const list = transactions || [];
    return apiSuccess(request, list, { extra: { transactions: list } });
  } catch (error) {
    console.error("Error fetching recent transactions:", error);
    return apiInternalError(request, "An unexpected error occurred. Please try again.");
  }
}
