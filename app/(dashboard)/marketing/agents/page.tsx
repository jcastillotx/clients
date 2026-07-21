import type { Metadata } from "next";
import { asc, isNull } from "drizzle-orm";
import { Bot } from "lucide-react";
import { redirect } from "next/navigation";
import { MarketingAgentConsole } from "@/components/marketing/marketing-agent-console";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { db } from "@/lib/db";
import { clients } from "@/lib/db/schema/clients";

export const metadata: Metadata = {
  title: "Marketing Agents",
  description: "Run governed AI marketing workflows for client accounts",
};

export default async function MarketingAgentsPage() {
  const access = await resolveStaffAccess();
  if (!access) redirect("/login");
  if (!access.isStaff) redirect("/marketing");

  const clientRows = await db
    .select({ id: clients.id, companyName: clients.companyName })
    .from(clients)
    .where(isNull(clients.deletedAt))
    .orderBy(asc(clients.companyName));

  return (
    <div className="flex flex-col gap-6">
      <div>
        <div className="flex items-center gap-2">
          <Bot className="h-7 w-7" />
          <h1 className="text-3xl font-bold tracking-tight">Marketing Agents</h1>
        </div>
        <p className="mt-2 text-muted-foreground">
          Build client marketing drafts with agent handoffs, brand review, quality checks, and human approval.
        </p>
      </div>
      <MarketingAgentConsole clients={clientRows} />
    </div>
  );
}
