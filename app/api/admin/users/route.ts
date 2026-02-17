import { createAdminClient, createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";

export async function POST(request: Request) {
  let createdAuthUserId: string | null = null;

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
    const [canManageUsers, canCreateUsers, canAssignRoles, hasManagementRoleDb] = await Promise.all([
      hasPermission(Permissions.USERS_MANAGE, accessOptions),
      hasPermission(Permissions.USERS_CREATE, accessOptions),
      hasPermission(Permissions.USERS_ASSIGN_ROLES, accessOptions),
      hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
    ]);
    const hasManagementRole = hasManagementRoleDb || hasManagementMetadataRole;

    if (!(canManageUsers || canCreateUsers || hasManagementRole)) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const body = await request.json();
    const { name, email, phone, password, client_id, is_active, roles } = body;

    if (!name || !email || !password) {
      return NextResponse.json({ error: "Name, email, and password are required" }, { status: 400 });
    }

    const adminClient = createAdminClient();

    // Create user in Supabase Auth
    const { data: authData, error: authError } = await adminClient.auth.admin.createUser({
      email,
      password,
      email_confirm: true,
      user_metadata: {
        name,
        phone,
        client_id,
      },
    });

    if (authError) throw authError;
    createdAuthUserId = authData.user.id;

    // Sync user record in database (trigger may already create the row)
    const { data: userRecord, error: dbError } = await adminClient
      .from("users")
      .upsert({
        id: authData.user.id,
        name,
        email,
        phone: phone || null,
        client_id: client_id || null,
        is_active: is_active !== false,
        status: is_active === false ? "inactive" : "active",
        updated_at: new Date().toISOString(),
      }, { onConflict: "id" })
      .select()
      .single();

    if (dbError) throw dbError;

    const canWriteRoles = canAssignRoles || canManageUsers || isAdminMetadataRole;
    const requestedRoleIds =
      Array.isArray(roles) && canWriteRoles
        ? Array.from(new Set(roles.filter((roleId: unknown): roleId is string => typeof roleId === "string")))
        : [];

    let roleIdsToAssign = requestedRoleIds;
    if (!canWriteRoles) {
      // Account managers can create users, but role assignment stays constrained.
      const { data: defaultRole } = await adminClient.from("roles").select("id").eq("name", "client").maybeSingle();
      roleIdsToAssign = defaultRole?.id ? [defaultRole.id] : [];
    }

    // Ensure deterministic role state for newly created users
    if (roleIdsToAssign.length > 0) {
      await adminClient.from("user_roles").delete().eq("user_id", userRecord.id);
      const { error: rolesError } = await adminClient.from("user_roles").insert(
        roleIdsToAssign.map((role_id: string) => ({
          user_id: userRecord.id,
          role_id,
          assigned_by: currentUser.id,
        })),
      );

      if (rolesError) throw rolesError;
    }

    // Fetch complete user with roles
    const { data: completeUser } = await adminClient
      .from("users")
      .select(`
        *,
        client:clients(id, company_name),
        user_roles(role:roles(id, name, description))
      `)
      .eq("id", userRecord.id)
      .single();

    return NextResponse.json({ user: completeUser }, { status: 201 });
  } catch (error) {
    console.error("Error creating user:", error);

    // Roll back auth user if we failed after creation
    if (createdAuthUserId) {
      const adminClient = createAdminClient();
      await adminClient.auth.admin.deleteUser(createdAuthUserId).catch(() => undefined);
    }

    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to create user",
      },
      { status: 500 },
    );
  }
}
