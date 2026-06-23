import { createClient } from "@/lib/supabase/server";
import {
  apiError,
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

    const { permission } = await request.json();

    if (!permission) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Permission name required",
      });
    }

    const { data, error } = await supabase.rpc("user_has_permission", {
      p_user_id: user.id,
      p_permission_name: permission,
    });

    if (error) {
      console.error("Error checking permission:", error);
      return apiSuccess(request, { hasPermission: false });
    }

    const hasPermission = data === true;
    return apiSuccess(request, { hasPermission }, { extra: { hasPermission } });
  } catch (error) {
    console.error("Error in check-permission route:", error);
    return apiInternalError(request, "Failed to check permission");
  }
}
