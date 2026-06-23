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
    const today = new Date();

    const { data: invoices } = await supabase
      .from("invoices")
      .select("id, amount, created_at, due_date")
      .eq("status", "sent");

    const aging = {
      current: { count: 0, amount: 0 },
      days30: { count: 0, amount: 0 },
      days60: { count: 0, amount: 0 },
      days90: { count: 0, amount: 0 },
    };

    invoices?.forEach((invoice) => {
      const createdDate = new Date(invoice.created_at);
      const daysOld = Math.floor(
        (today.getTime() - createdDate.getTime()) / (1000 * 60 * 60 * 24),
      );

      if (daysOld <= 30) {
        aging.current.count++;
        aging.current.amount += invoice.amount;
      } else if (daysOld <= 60) {
        aging.days30.count++;
        aging.days30.amount += invoice.amount;
      } else if (daysOld <= 90) {
        aging.days60.count++;
        aging.days60.amount += invoice.amount;
      } else {
        aging.days90.count++;
        aging.days90.amount += invoice.amount;
      }
    });

    return apiSuccess(request, aging, { extra: { aging } });
  } catch (error) {
    console.error("Error fetching accounts receivable:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch aging data",
    );
  }
}
