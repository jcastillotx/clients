import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";
import { getAuthConfirmUrl } from "@/lib/supabase/redirect-url";
import { auditLog, logger } from "@/lib/logger";

/**
 * POST /api/clients/[id]/resend-login
 *
 * Sends the primary contact a password recovery email (Supabase) so they can set/reset
 * their password and sign in — same mechanism as "Forgot password".
 */
export async function POST(_request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id: clientId } = await params;

  try {
    const supabase = await createClient();
    const {
      data: { user: currentUser },
    } = await supabase.auth.getUser();

    if (!currentUser) {
      return NextResponse.json({ error: "Authentication required" }, { status: 401 });
    }

    const metadataRole = String(currentUser.user_metadata?.role ?? currentUser.user_metadata?.app_role ?? "").toLowerCase();
    const hasManagementMetadataRole =
      currentUser.user_metadata?.is_super_admin === true ||
      metadataRole === Roles.SUPER_ADMIN ||
      metadataRole === Roles.ADMIN ||
      metadataRole === Roles.ACCOUNT_MANAGER;

    const accessOptions = { supabase, userId: currentUser.id };
    const [canUpdateClients, hasManagementRoleDb] = await Promise.all([
      hasPermission(Permissions.CLIENTS_UPDATE, accessOptions),
      hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
    ]);
    const hasManagementRole = hasManagementRoleDb || hasManagementMetadataRole;

    if (!(canUpdateClients || hasManagementRole)) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const adminForDb = hasManagementRole ? createAdminClientIfAvailable() : null;
    const dbClient = adminForDb ?? supabase;

    if (hasManagementRole && !adminForDb) {
      console.warn(
        "resend-login: SUPABASE_SERVICE_KEY missing; using session client for client lookup",
      );
    }

    const { data: client, error: clientError } = await dbClient
      .from("clients")
      .select("id, company_name, primary_contact_id")
      .eq("id", clientId)
      .is("deleted_at", null)
      .maybeSingle();

    if (clientError) throw clientError;
    if (!client) {
      return NextResponse.json({ error: "Client not found" }, { status: 404 });
    }

    if (typeof client.primary_contact_id !== "string" || !client.primary_contact_id.length) {
      return NextResponse.json(
        { error: "Assign a primary contact before sending login information." },
        { status: 400 },
      );
    }

    const { data: contact, error: contactError } = await dbClient
      .from("users")
      .select("id, email, deleted_at")
      .eq("id", client.primary_contact_id)
      .maybeSingle();

    if (contactError) throw contactError;
    if (!contact?.email?.trim()) {
      return NextResponse.json(
        { error: "Primary contact has no email address on file." },
        { status: 400 },
      );
    }
    if (contact.deleted_at) {
      return NextResponse.json({ error: "Primary contact user is inactive." }, { status: 400 });
    }

    const authAdmin = createAdminClientIfAvailable();
    if (!authAdmin) {
      return NextResponse.json(
        { error: "Email could not be sent: server mail configuration is incomplete." },
        { status: 503 },
      );
    }

    const email = contact.email.trim();
    const { error: resetError } = await authAdmin.auth.resetPasswordForEmail(email, {
      redirectTo: getAuthConfirmUrl("/reset-password"),
    });

    if (resetError) {
      logger.error("resend-login: resetPasswordForEmail failed", resetError, {
        clientId,
        targetUserId: contact.id,
      });
      return NextResponse.json(
        { error: resetError.message || "Failed to send email" },
        { status: 502 },
      );
    }

    auditLog("client.login_info_sent", currentUser.id, {
      clientId,
      targetUserId: contact.id,
      companyName: client.company_name,
    });

    return NextResponse.json({
      success: true,
      message: "Login email sent",
      sentTo: email,
    });
  } catch (error) {
    logger.error("POST /api/clients/[id]/resend-login", error, { clientId });
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Unexpected error" },
      { status: 500 },
    );
  }
}
