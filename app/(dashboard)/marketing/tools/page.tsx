import { and, asc, eq, isNull } from "drizzle-orm";
import { redirect } from "next/navigation";
import { MarketingSkillTools } from "@/components/marketing/marketing-skill-tools";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { db } from "@/lib/db";
import { clients } from "@/lib/db/schema/clients";

export const metadata = {
  title: "Marketing Tools | KRE8IV",
  description: "Client marketing strategy, content and analysis tools",
};
export default async function MarketingToolsPage() {
  const access = await resolveStaffAccess();
  if (!access) redirect("/login");
  if (!access.isStaff && !access.clientId)
    return (
      <p>Your account needs a client association to use marketing tools.</p>
    );
  const clientRows = await db
    .select({ id: clients.id, companyName: clients.companyName })
    .from(clients)
    .where(
      and(
        isNull(clients.deletedAt),
        access.isStaff ? undefined : eq(clients.id, access.clientId!),
      ),
    )
    .orderBy(asc(clients.companyName));
  return <MarketingSkillTools clients={clientRows} isStaff={access.isStaff} />;
}
