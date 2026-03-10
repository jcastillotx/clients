import { createAdminClient, createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";
import { getAuthConfirmUrl } from "@/lib/supabase/redirect-url";
import { logger, auditLog } from "@/lib/logger";

export async function POST(_request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;

  try {
    const supabase = await createClient();
    const {
      data: { user: currentUser },
    } = await supabase.auth.getUser();

    if (!currentUser) {
      return NextResponse.json({ error: "Authentication required" }, { status: 401 });
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
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const adminClient = createAdminClient();
    const { data: targetUser, error: targetUserError } = await adminClient
      .from("users")
      .select("id, email, deleted_at")
      .eq("id", id)
      .maybeSingle();

    if (targetUserError) throw targetUserError;
    if (!targetUser || !targetUser.email) {
      return NextResponse.json({ error: "User not found" }, { status: 404 });
    }
    if (targetUser.deleted_at) {
      return NextResponse.json({ error: "Cannot reset password for a deleted user" }, { status: 400 });
    }

    const { error: resetError } = await adminClient.auth.resetPasswordForEmail(targetUser.email, {
      redirectTo: getAuthConfirmUrl("/reset-password"),
    });

    if (resetError) throw resetError;

    auditLog("admin.password_reset_sent", currentUser.id, { targetUserId: id });

    return NextResponse.json({
      success: true,
      message: "Password reset email sent",
    });
  } catch (error) {
    logger.error("Error sending password reset email", error, { targetUserId: id });
    return NextResponse.json(
      { error: "An unexpected error occurred. Please try again." },
      { status: 500 },
    );
  }
}
