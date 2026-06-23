import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";
import { getAuthConfirmUrl } from "@/lib/supabase/redirect-url";
import { auditLog, logger } from "@/lib/logger";

/**
 * POST /api/clients/[id]/resend-login
 *
 * Sends the primary contact a password recovery email (Supabase) so they can set/reset
 * their password and sign in — same mechanism as "Forgot password".
 */
export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id: clientId } = await params;

  try {
    const supabase = await createClient();
    const {
      data: { user: currentUser },
    } = await supabase.auth.getUser();

    if (!currentUser) {
      return apiUnauthorized(request, "Authentication required");
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
      return apiForbidden(request, "Permission denied");
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
      return apiNotFound(request, "Client not found");
    }

    if (typeof client.primary_contact_id !== "string" || !client.primary_contact_id.length) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Assign a primary contact before sending login information.",
      });
    }

    const { data: contact, error: contactError } = await dbClient
      .from("users")
      .select("id, email, deleted_at")
      .eq("id", client.primary_contact_id)
      .maybeSingle();

    if (contactError) throw contactError;
    if (!contact?.email?.trim()) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Primary contact has no email address on file.",
      });
    }
    if (contact.deleted_at) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Primary contact user is inactive.",
      });
    }

    const authAdmin = createAdminClientIfAvailable();
    if (!authAdmin) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Email could not be sent: server mail configuration is incomplete.",
      });
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
      return apiError(request, {
        status: 502,
        code: "INTERNAL_ERROR",
        message: resetError.message || "Failed to send email",
      });
    }

    auditLog("client.login_info_sent", currentUser.id, {
      clientId,
      targetUserId: contact.id,
      companyName: client.company_name,
    });

    return apiSuccess(
      request,
      { sentTo: email },
      {
        extra: {
          success: true,
          message: "Login email sent",
          sentTo: email,
        },
      },
    );
  } catch (error) {
    logger.error("POST /api/clients/[id]/resend-login", error, { clientId });
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Unexpected error",
    );
  }
}
