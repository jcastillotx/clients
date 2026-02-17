import { createAdminClient, createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";

export async function PATCH(request: Request, { params }: { params: Promise<{ id: string }> }) {
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
    const [canManageUsers, canUpdateUsers, canAssignRoles, hasManagementRoleDb] = await Promise.all([
      hasPermission(Permissions.USERS_MANAGE, accessOptions),
      hasPermission(Permissions.USERS_UPDATE, accessOptions),
      hasPermission(Permissions.USERS_ASSIGN_ROLES, accessOptions),
      hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
    ]);
    const hasManagementRole = hasManagementRoleDb || hasManagementMetadataRole;

    if (!(canManageUsers || canUpdateUsers || hasManagementRole)) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const body = await request.json();
    const { name, email, phone, password, client_id, is_active, roles } = body;
    const adminClient = createAdminClient();

    // Keep auth profile aligned with app profile changes
    if (password || email || name || phone || client_id !== undefined) {
      const { error: authError } = await adminClient.auth.admin.updateUserById(id, {
        ...(password ? { password } : {}),
        ...(email ? { email } : {}),
        user_metadata: { name, phone, client_id },
      });

      if (authError) throw authError;
    }

    // Update user record
    const { error } = await adminClient
      .from("users")
      .update({
        name,
        email,
        phone: phone || null,
        client_id: client_id || null,
        is_active: is_active !== false,
        status: is_active === false ? "inactive" : "active",
        updated_at: new Date().toISOString(),
      })
      .eq("id", id)

    if (error) throw error;

    // Update roles
    if (roles && (canAssignRoles || canManageUsers || isAdminMetadataRole)) {
      const roleIds = Array.from(new Set(roles.filter((roleId: unknown): roleId is string => typeof roleId === "string")));

      // Remove existing roles
      await adminClient.from("user_roles").delete().eq("user_id", id);

      // Add new roles
      if (roleIds.length > 0) {
        const { error: rolesError } = await adminClient.from("user_roles").insert(
          roleIds.map((role_id: string) => ({
            user_id: id,
            role_id,
            assigned_by: currentUser.id,
          })),
        );

        if (rolesError) throw rolesError;
      }
    }

    // Fetch complete user with roles
    const { data: completeUser } = await adminClient
      .from("users")
      .select(`
        *,
        client:clients(id, company_name),
        user_roles(role:roles(id, name, description))
      `)
      .eq("id", id)
      .single();

    return NextResponse.json({ user: completeUser });
  } catch (error) {
    console.error("Error updating user:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to update user",
      },
      { status: 500 },
    );
  }
}

export async function DELETE(_request: Request, { params }: { params: Promise<{ id: string }> }) {
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

    const accessOptions = { supabase, userId: currentUser.id };
    const [canManageUsers, canDeleteUsers] = await Promise.all([
      hasPermission(Permissions.USERS_MANAGE, accessOptions),
      hasPermission(Permissions.USERS_DELETE, accessOptions),
    ]);

    if (!(canManageUsers || canDeleteUsers || isAdminMetadataRole)) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const adminClient = createAdminClient();

    // Soft delete user
    const { error } = await adminClient
      .from("users")
      .update({ deleted_at: new Date().toISOString() })
      .eq("id", id);

    if (error) throw error;

    // Optionally delete from Supabase Auth
    // await supabase.auth.admin.deleteUser(params.id);

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting user:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to delete user",
      },
      { status: 500 },
    );
  }
}
