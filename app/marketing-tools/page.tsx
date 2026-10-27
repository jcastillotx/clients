import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { MarketingStore } from "@/components/marketing/marketing-store";

export const dynamic = "force-dynamic";
export const metadata = { title: "Buy Marketing Tools | Kre8ivDesigns", description: "Monthly marketing software and AI draft tools. Compare packages and buy securely." };
export default async function MarketingToolsStore() {
  const access = await resolveStaffAccess();
  const rows = await db.execute(sql`SELECT id,name,tool_groups,monthly_price_cents,currency FROM marketing_plans WHERE published AND is_active AND monthly_price_cents>0 ORDER BY monthly_price_cents`);
  const plans = rows.map(row => ({ id: String(row.id), name: String(row.name), groups: Array.isArray(row.tool_groups) ? row.tool_groups.map(String) : [], price: Number(row.monthly_price_cents), currency: String(row.currency) }));
  return <MarketingStore plans={plans} signedIn={Boolean(access)} />;
}
