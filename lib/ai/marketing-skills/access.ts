import { and, eq, inArray, isNull } from "drizzle-orm";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { db } from "@/lib/db";
import { clients } from "@/lib/db/schema/clients";
import {
  clientFeatures,
  features,
  roleFeatures,
  userFeatures,
} from "@/lib/db/schema/feature-flags";
import { userRoles } from "@/lib/db/schema/rbac";
import { getClientMarketingPlan } from "./plans";
import { z } from "zod";

export function canAccessMarketingClient(
  access: { isStaff: boolean; clientId: string | null },
  clientId: string,
) {
  return access.isStaff || access.clientId === clientId;
}

export async function resolveMarketingToolAccess(
  requestedClientId?: string | null,
) {
  const access = await resolveStaffAccess();
  if (!access) return { error: "unauthorized" as const };
  const clientId = requestedClientId || access.clientId;
  if (
    !clientId ||
    !z.string().uuid().safeParse(clientId).success ||
    !canAccessMarketingClient(access, clientId)
  )
    return { error: "forbidden" as const };
  const [client] = await db
    .select({ id: clients.id })
    .from(clients)
    .where(and(eq(clients.id, clientId), isNull(clients.deletedAt)))
    .limit(1);
  if (!client) return { error: "forbidden" as const };
  const [feature] = await db
    .select()
    .from(features)
    .where(eq(features.name, "marketing_skill_tools"))
    .limit(1);
  if (!feature) return { error: "disabled" as const };
  const [userOverride] = await db
    .select()
    .from(userFeatures)
    .where(
      and(
        eq(userFeatures.userId, access.userId),
        eq(userFeatures.featureId, feature.id),
      ),
    )
    .limit(1);
  const roles = await db
    .select({ roleId: userRoles.roleId })
    .from(userRoles)
    .where(eq(userRoles.userId, access.userId));
  const roleOverrides = roles.length
    ? await db
        .select()
        .from(roleFeatures)
        .where(
          and(
            inArray(
              roleFeatures.roleId,
              roles.map((r) => r.roleId),
            ),
            eq(roleFeatures.featureId, feature.id),
          ),
        )
    : [];
  const [clientOverride] = await db
    .select()
    .from(clientFeatures)
    .where(
      and(
        eq(clientFeatures.clientId, clientId),
        eq(clientFeatures.featureId, feature.id),
      ),
    )
    .limit(1);
  const enabled =
    userOverride?.isEnabled ??
    (roleOverrides.length
      ? roleOverrides.every((r) => r.isEnabled)
      : undefined) ??
    clientOverride?.isEnabled ??
    feature.isEnabledByDefault;
  if (!enabled) return { error: "disabled" as const };
  const plan = await getClientMarketingPlan(clientId);
  if (!plan || plan.groups.length === 0)
    return { error: "plan_required" as const };
  return { access, clientId, plan };
}
