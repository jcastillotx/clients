import { createClient, createAdminClientIfAvailable } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

const OAUTH_KEYS = [
  "oauth_provider",
  "oauth_account_email",
  "oauth_access_token",
  "oauth_refresh_token",
  "oauth_token_expiry",
];

/**
 * POST /api/admin/email/disconnect
 * Clears any stored OAuth tokens for the email provider.
 */
export async function POST(request: Request) {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return apiUnauthorized(request);
  if (!(await isUserAdmin(user.id))) return apiForbidden(request);

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) {
    return apiError(request, {
      status: 503,
      code: "SERVICE_UNAVAILABLE",
      message: "Admin client not configured",
    });
  }

  const { error } = await adminClient
    .from("system_settings")
    .delete()
    .eq("category", "email")
    .in("key", OAUTH_KEYS);

  if (error) {
    console.error("[admin/email/disconnect] Failed to disconnect OAuth provider:", error);
    return apiInternalError(request, "Failed to disconnect provider");
  }

  return apiSuccess(request, { disconnected: true }, { extra: { success: true } });
}
