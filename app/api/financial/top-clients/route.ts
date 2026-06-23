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

    const { data: invoices } = await supabase.from("invoices").select(
      `
        id,
        amount,
        status,
        client_id,
        clients!inner(
          id,
          company_name
        )
      `,
    );

    const clientMap = new Map<
      string,
      {
        id: string;
        company_name: string;
        total_revenue: number;
        invoice_count: number;
        paid_count: number;
      }
    >();

    invoices?.forEach(
      (invoice: {
        client_id: string;
        amount: number;
        status: string;
        clients?: { company_name?: string } | Array<{ company_name?: string }>;
      }) => {
        const clientId = invoice.client_id;
        const clientName = Array.isArray(invoice.clients)
          ? (invoice.clients[0]?.company_name ?? "Unknown")
          : invoice.clients?.company_name || "Unknown";

        if (!clientMap.has(clientId)) {
          clientMap.set(clientId, {
            id: clientId,
            company_name: clientName,
            total_revenue: 0,
            invoice_count: 0,
            paid_count: 0,
          });
        }

        const client = clientMap.get(clientId)!;
        client.total_revenue += invoice.amount;
        client.invoice_count++;
        if (invoice.status === "paid") {
          client.paid_count++;
        }
      },
    );

    const clients = Array.from(clientMap.values())
      .sort((a, b) => b.total_revenue - a.total_revenue)
      .slice(0, 10);

    return apiSuccess(request, clients, { extra: { clients } });
  } catch (error) {
    console.error("Error fetching top clients:", error);
    return apiInternalError(request, "An unexpected error occurred. Please try again.");
  }
}
