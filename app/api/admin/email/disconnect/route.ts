import { NextResponse } from "next/server";
import { createClient, createAdminClientIfAvailable } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";

const OAUTH_KEYS = [
  "oauth_provider",
  "oauth_account_email",
  "oauth_access_token",
  "oauth_refresh_token",
  "oauth_token_expiry",
];

/**
 * POST /api/admin/email/disconnect
 * Clears any stored OAuth tokens for the email provider so the admin can
 * reconnect or switch to manual/API-key credentials.
 */
export async function POST() {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  if (!(await isUserAdmin(user.id)))
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient)
    return NextResponse.json({ error: "Admin client not configured" }, { status: 503 });

  const { error } = await adminClient
    .from("system_settings")
    .delete()
    .eq("category", "email")
    .in("key", OAUTH_KEYS);

  if (error) {
    console.error("[admin/email/disconnect] Failed to disconnect OAuth provider:", error);
    return NextResponse.json({ error: "Failed to disconnect provider" }, { status: 500 });
  }

  return NextResponse.json({ success: true });
}
