import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { subDays } from "date-fns";

export async function GET() {
  try {
    const canView = await hasPermission("reports.financial");
    if (!canView) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const supabase = createClient();
    const today = new Date();

    // Calculate date ranges
    const days30Ago = subDays(today, 30);
    const days60Ago = subDays(today, 60);
    const days90Ago = subDays(today, 90);

    // Fetch all unpaid invoices
    const { data: invoices } = await supabase
      .from("invoices")
      .select("id, amount, created_at, due_date")
      .eq("status", "sent");

    // Categorize by age
    const aging = {
      current: { count: 0, amount: 0 }, // 0-30 days
      days30: { count: 0, amount: 0 }, // 31-60 days
      days60: { count: 0, amount: 0 }, // 61-90 days
      days90: { count: 0, amount: 0 }, // 90+ days
    };

    invoices?.forEach((invoice) => {
      const createdDate = new Date(invoice.created_at);
      const daysOld = Math.floor((today.getTime() - createdDate.getTime()) / (1000 * 60 * 60 * 24));

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

    return NextResponse.json({ aging });
  } catch (error) {
    console.error("Error fetching accounts receivable:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to fetch aging data" },
      { status: 500 },
    );
  }
}
