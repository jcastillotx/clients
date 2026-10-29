import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { getClientMarketingPlan } from "@/lib/ai/marketing-skills/plans";
import { marketingProviderGroup, type MarketingProvider } from "./providers";
export async function marketingConnectionAccess(provider?: MarketingProvider) {
  const access = await resolveStaffAccess();
  if (!access?.clientId) return null;
  const plan = await getClientMarketingPlan(access.clientId);
  if (
    !plan ||
    (provider && !plan.groups.includes(marketingProviderGroup(provider)))
  )
    return null;
  return { ...access, clientId: access.clientId, plan };
}
