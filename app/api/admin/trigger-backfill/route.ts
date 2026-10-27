import { inngest } from "@/lib/inngest/client";
import { createClient } from "@/lib/supabase/server";
import { hasAnyRole, Roles } from "@/lib/rbac/permissions";
import {
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    if (
      !(await hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN], {
        supabase,
        userId: user.id,
      }))
    ) {
      return apiForbidden(request);
    }

    await inngest.send({
      name: "client.backfill",
      data: {},
    });

    return apiSuccess(
      request,
      { triggered: true },
      { extra: { success: true, message: "Backfill triggered" } },
    );
  } catch (error) {
    console.error("Error triggering backfill:", error);
    return apiInternalError(request, "Internal server error");
  }
}
