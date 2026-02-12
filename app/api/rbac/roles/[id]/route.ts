import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";

// GET /api/rbac/roles/[id] - Get a specific role
export async function GET(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();

    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    // Enforce least-privilege: require specific roles.read permission
    const canReadRbac = await hasPermission("roles.read");
    if (!canReadRbac) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const { data: role, error } = await supabase
      .from("roles")
      .select(
        `
        *,
        role_permissions(
          permission:permissions(*)
        )
      `,
      )
      .eq("id", id)
      .single();

    if (error) throw error;
    if (!role) {
      return NextResponse.json({ error: "Role not found" }, { status: 404 });
    }

    return NextResponse.json({ role });
  } catch (error) {
    console.error("Error fetching role:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to fetch role" },
      { status: 500 },
    );
  }
}

// PATCH /api/rbac/roles/[id] - Update a role
export async function PATCH(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canUpdate = await hasPermission("roles.update");
    if (!canUpdate) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const supabase = await createClient();
    const body = await request.json();

    const { name, description, permissionIds } = body;

    // Check if role is system role
    const { data: existingRole } = await supabase.from("roles").select("is_system").eq("id", id).single();

    if (existingRole?.is_system && name) {
      return NextResponse.json({ error: "Cannot rename system roles" }, { status: 400 });
    }

    // Update role
    const { data: role, error: roleError } = await supabase
      .from("roles")
      .update({
        name,
        description,
        updated_at: new Date().toISOString(),
      })
      .eq("id", id)
      .select()
      .single();

    if (roleError) throw roleError;

    // Update permissions if provided
    if (permissionIds !== undefined) {
      // Delete existing permissions
      await supabase.from("role_permissions").delete().eq("role_id", id);

      // Insert new permissions
      if (permissionIds.length > 0) {
        const rolePermissions = permissionIds.map((permissionId: string) => ({
          role_id: id,
          permission_id: permissionId,
        }));

        const { error: permError } = await supabase.from("role_permissions").insert(rolePermissions);

        if (permError) throw permError;
      }
    }

    return NextResponse.json({ role });
  } catch (error) {
    console.error("Error updating role:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to update role" },
      { status: 500 },
    );
  }
}

// DELETE /api/rbac/roles/[id] - Delete a role
export async function DELETE(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canDelete = await hasPermission("roles.delete");
    if (!canDelete) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const supabase = await createClient();

    // Check if role is system role
    const { data: role } = await supabase.from("roles").select("is_system, name").eq("id", id).single();

    if (role?.is_system) {
      return NextResponse.json({ error: "Cannot delete system roles" }, { status: 400 });
    }

    // Delete role (cascade will delete role_permissions and user_roles)
    const { error } = await supabase.from("roles").delete().eq("id", id);

    if (error) throw error;

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting role:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to delete role" },
      { status: 500 },
    );
  }
}
