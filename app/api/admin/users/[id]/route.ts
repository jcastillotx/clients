import { createAdminClient, createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";

async function fetchCompleteUserForResponse(adminClient: ReturnType<typeof createAdminClient>, userId: string) {
  type UserRow = {
    id: string;
    client_id?: string | null;
    [key: string]: unknown;
  };
  type RoleRow = {
    id: string;
    name: string;
    description: string | null;
  };
  type JoinedRoleRow = {
    role: RoleRow | RoleRow[] | null;
  };
  type PlainRoleRow = {
    role_id: string;
  };

  const userResult = await adminClient.from("users").select("*").eq("id", userId).maybeSingle();
  if (userResult.error) {
    throw userResult.error;
  }

  const userRow = (userResult.data as UserRow | null) ?? null;
  if (!userRow) {
    return null;
  }

  let client: { id: string; company_name: string } | null = null;
  if (typeof userRow.client_id === "string" && userRow.client_id.length > 0) {
    const clientResult = await adminClient
      .from("clients")
      .select("id, company_name")
      .eq("id", userRow.client_id)
      .maybeSingle();
    if (clientResult.error) {
      console.warn("Failed to hydrate user client in response", clientResult.error);
    } else {
      client = clientResult.data ?? null;
    }
  }

  const userRoles: Array<{ role: RoleRow }> = [];
  const joinedRolesResult = await adminClient
    .from("user_roles")
    .select("role:roles!user_roles_role_id_fkey(id, name, description)")
    .eq("user_id", userId);

  if (!joinedRolesResult.error) {
    for (const row of (joinedRolesResult.data || []) as JoinedRoleRow[]) {
      const roleValue = Array.isArray(row.role) ? row.role[0] : row.role;
      if (roleValue && typeof roleValue.id === "string" && typeof roleValue.name === "string") {
        userRoles.push({
          role: {
            id: roleValue.id,
            name: roleValue.name,
            description: roleValue.description ?? null,
          },
        });
      }
    }
  } else {
    console.warn("Failed to join user roles in response; falling back to manual role hydration", joinedRolesResult.error);

    const plainRolesResult = await adminClient.from("user_roles").select("role_id").eq("user_id", userId);
    if (plainRolesResult.error) {
      console.warn("Failed to load role IDs in response fallback", plainRolesResult.error);
    } else {
      const roleIds = Array.from(
        new Set(
          ((plainRolesResult.data || []) as PlainRoleRow[])
            .map((row) => row.role_id)
            .filter((roleId): roleId is string => typeof roleId === "string" && roleId.length > 0),
        ),
      );
      if (roleIds.length > 0) {
        const rolesResult = await adminClient.from("roles").select("id, name, description").in("id", roleIds);
        if (rolesResult.error) {
          console.warn("Failed to load roles in response fallback", rolesResult.error);
        } else {
          for (const role of (rolesResult.data || []) as RoleRow[]) {
            userRoles.push({ role });
          }
        }
      }
    }
  }

  return {
    ...userRow,
    client,
    user_roles: userRoles,
  };
}

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
    if (Array.isArray(roles) && (canAssignRoles || canManageUsers || isAdminMetadataRole)) {
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

    const completeUser = await fetchCompleteUserForResponse(adminClient, id);

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
