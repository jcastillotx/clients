import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { getClientMarketingPlan } from "@/lib/ai/marketing-skills/plans";
import {
  connectionGroup,
  type GoogleMarketingProvider,
} from "./google-connections";
export async function marketingConnectionAccess(
  provider?: GoogleMarketingProvider,
) {
  const access = await resolveStaffAccess();
  if (!access?.clientId) return null;
  const plan = await getClientMarketingPlan(access.clientId);
  if (!plan || (provider && !plan.groups.includes(connectionGroup(provider))))
    return null;
  return { ...access, clientId: access.clientId, plan };
}
