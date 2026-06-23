import { createAdminClient, createClient } from "@/lib/supabase/server";
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
import { logger, auditLog } from "@/lib/logger";

export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;

  try {
    const supabase = await createClient();
    const {
      data: { user: currentUser },
    } = await supabase.auth.getUser();

    if (!currentUser) {
      return apiUnauthorized(request, "Authentication required");
    }

    const metadataRole = String(currentUser.user_metadata?.role ?? currentUser.user_metadata?.app_role ?? "").toLowerCase();
    const isAdminMetadataRole =
      currentUser.user_metadata?.is_super_admin === true ||
      metadataRole === Roles.SUPER_ADMIN ||
      metadataRole === Roles.ADMIN;
    const hasManagementMetadataRole = isAdminMetadataRole || metadataRole === Roles.ACCOUNT_MANAGER;

    const accessOptions = { supabase, userId: currentUser.id };
    const [canManageUsers, canUpdateUsers, hasManagementRoleDb] = await Promise.all([
      hasPermission(Permissions.USERS_MANAGE, accessOptions),
      hasPermission(Permissions.USERS_UPDATE, accessOptions),
      hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
    ]);
    const hasManagementRole = hasManagementRoleDb || hasManagementMetadataRole;

    if (!(canManageUsers || canUpdateUsers || hasManagementRole)) {
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
    if (targetUser.deleted_at) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Cannot reset password for a deleted user",
      });
    }

    const { error: resetError } = await adminClient.auth.resetPasswordForEmail(targetUser.email, {
      redirectTo: getAuthConfirmUrl("/reset-password"),
    });

    if (resetError) throw resetError;

    auditLog("admin.password_reset_sent", currentUser.id, { targetUserId: id });

    return apiSuccess(
      request,
      { sent: true },
      { extra: { message: "Password reset email sent" } },
    );
  } catch (error) {
    logger.error("Error sending password reset email", error, { targetUserId: id });
    return apiInternalError(request, "An unexpected error occurred. Please try again.");
  }
}
