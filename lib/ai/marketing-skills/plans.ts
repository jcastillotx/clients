import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { marketingToolGroups, type MarketingToolGroupId } from "./menu";

export async function getClientMarketingPlan(clientId: string) {
  const rows = await db.execute(sql`
    SELECT p.id, p.name, p.tool_groups AS groups
    FROM client_marketing_plans a JOIN marketing_plans p ON p.id=a.plan_id
    WHERE a.client_id=${clientId}::uuid AND a.status='active' AND p.is_active
      AND a.starts_at <= now() AND (a.ends_at IS NULL OR a.ends_at > now())
    LIMIT 1
  `);
  const row = rows[0];
  if (!row) return null;
  const groups = Array.isArray(row.groups) ? row.groups : [];
  return {
    id: String(row.id),
    name: String(row.name),
    groups: marketingToolGroups
      .filter((group) => groups.includes(group.id))
      .map((group) => group.id) as MarketingToolGroupId[],
  };
}
