import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";

// GET /api/rbac/roles - List all roles
export async function GET() {
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

    // Fetch roles with their permissions
    const { data: roles, error } = await supabase
      .from("roles")
      .select(
        `
        *,
        role_permissions(
          permission:permissions(*)
        )
      `,
      )
      .order("name");

    if (error) throw error;

    return NextResponse.json({ roles });
  } catch (error) {
    console.error("Error fetching roles:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to fetch roles" },
      { status: 500 },
    );
  }
}

// POST /api/rbac/roles - Create a new role
export async function POST(request: Request) {
  try {
    const canCreate = await hasPermission("roles.create");
    if (!canCreate) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const supabase = await createClient();
    const body = await request.json();

    const { name, description, permissionIds } = body;

    if (!name) {
      return NextResponse.json({ error: "Role name is required" }, { status: 400 });
    }

    // Create role
    const { data: role, error: roleError } = await supabase
      .from("roles")
      .insert({
        name,
        description,
        is_system: false,
      })
      .select()
      .single();

    if (roleError) throw roleError;

    // Assign permissions if provided
    if (permissionIds && permissionIds.length > 0) {
      const rolePermissions = permissionIds.map((permissionId: string) => ({
        role_id: role.id,
        permission_id: permissionId,
      }));

      const { error: permError } = await supabase.from("role_permissions").insert(rolePermissions);

      if (permError) throw permError;
    }

    return NextResponse.json({ role }, { status: 201 });
  } catch (error) {
    console.error("Error creating role:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to create role" },
      { status: 500 },
    );
  }
}
