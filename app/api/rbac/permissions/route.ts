import { createClient } from "@/lib/supabase/server";
import {
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { hasAnyPermission } from "@/lib/rbac/permissions";

/** GET /api/rbac/permissions */
export async function GET(request: Request) {
  try {
    const supabase = await createClient();

    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    const canReadRbac = await hasAnyPermission(["roles.read", "settings.read"], {
      supabase,
      userId: user.id,
    });
    if (!canReadRbac) {
      return apiForbidden(request);
    }

    const { data: permissions, error } = await supabase
      .from("permissions")
      .select("*")
      .order("resource")
      .order("action");

    if (error) throw error;

    const groupedPermissions = (permissions ?? []).reduce(
      (acc, perm) => {
        if (!acc[perm.resource]) {
          acc[perm.resource] = [];
        }
        acc[perm.resource].push(perm);
        return acc;
      },
      {} as Record<string, typeof permissions>,
    );

    const payload = {
      permissions: permissions ?? [],
      groupedPermissions,
    };

    return apiSuccess(request, payload, { extra: payload });
  } catch (error) {
    console.error("Error fetching permissions:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch permissions",
    );
  }
}
