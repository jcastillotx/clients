import { inngest } from "@/lib/inngest/client";
import { createClient } from "@/lib/supabase/server";
import { Roles } from "@/lib/rbac/permissions";
import {
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    const role = user.user_metadata?.role || user.user_metadata?.app_role;
    if (role !== Roles.SUPER_ADMIN && role !== Roles.ADMIN && !user.user_metadata?.is_super_admin) {
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
