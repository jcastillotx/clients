import { canManageUserTarget } from "@/lib/auth/user-management";
import { createAdminClient, createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import {
  hasAnyRole,
  hasPermission,
  Permissions,
  Roles,
} from "@/lib/rbac/permissions";
import { getAuthConfirmUrl } from "@/lib/supabase/redirect-url";
import { logger, auditLog } from "@/lib/logger";

export async function POST(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;

  try {
    const supabase = await createClient();
    const {
      data: { user: currentUser },
    } = await supabase.auth.getUser();

    if (!currentUser) {
      return apiUnauthorized(request, "Authentication required");
    }

    const accessOptions = { supabase, userId: currentUser.id };
    const [canManageUsers, hasAdminRoleDb] = await Promise.all([
      hasPermission(Permissions.USERS_MANAGE, accessOptions),
      hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN], accessOptions),
    ]);
    const hasAdminRole = hasAdminRoleDb;

    if (!(canManageUsers || hasAdminRole)) {
      return apiForbidden(request);
    }

    const adminClient = createAdminClient();
    const { data: targetUser, error: targetUserError } = await adminClient
      .from("users")
      .select("id, email, deleted_at")
      .eq("id", id)
      .maybeSingle();

    if (targetUserError) throw targetUserError;
    if (!targetUser || !targetUser.email) {
      return apiNotFound(request, "User not found");
    }
    if (!(await canManageUserTarget(adminClient, id, hasAdminRoleDb)))
      return apiForbidden(request);
    if (targetUser.deleted_at) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Cannot reset password for a deleted user",
      });
    }

    // Use the authenticated client for the recovery request. The service-role
    // client is still used above for the admin-only user lookup, but GoTrue's
    // recovery endpoint is intended to be called with the public auth client.
    const { error: resetError } = await supabase.auth.resetPasswordForEmail(
      targetUser.email,
      {
        redirectTo: getAuthConfirmUrl("/reset-password"),
      },
    );

    if (resetError) {
      logger.error("Supabase rejected password reset email", resetError, {
        targetUserId: id,
      });
      return apiError(request, {
        status: 502,
        code: "INTERNAL_ERROR",
        message:
          resetError.message ||
          "Supabase could not send the password reset email.",
      });
    }

    auditLog("admin.password_reset_sent", currentUser.id, { targetUserId: id });

    return apiSuccess(
      request,
      { sent: true },
      { extra: { message: "Password reset email sent" } },
    );
  } catch (error) {
    logger.error("Error sending password reset email", error, {
      targetUserId: id,
    });
    return apiInternalError(
      request,
      error instanceof Error
        ? error.message
        : "An unexpected error occurred. Please try again.",
    );
  }
}
