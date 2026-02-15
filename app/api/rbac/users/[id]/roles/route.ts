import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";

// GET /api/rbac/users/[id]/roles - Get user's roles
export async function GET(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canRead = await hasPermission("users.read");
    if (!canRead) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const supabase = await createClient();

    const { data: userRoles, error } = await supabase
      .from("user_roles")
      .select(
        `
        *,
        role:roles(*)
      `,
      )
      .eq("user_id", id);

    if (error) throw error;

    return NextResponse.json({ userRoles });
  } catch (error) {
    console.error("Error fetching user roles:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to fetch user roles" },
      { status: 500 },
    );
  }
}

// POST /api/rbac/users/[id]/roles - Assign role to user
export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canAssign = await hasPermission("users.assign_roles");
    if (!canAssign) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    const { roleId } = await request.json();

    if (!roleId) {
      return NextResponse.json({ error: "Role ID is required" }, { status: 400 });
    }

    // Check if user already has this role
    const { data: existing } = await supabase
      .from("user_roles")
      .select("*")
      .eq("user_id", id)
      .eq("role_id", roleId)
      .maybeSingle();

    if (existing) {
      return NextResponse.json({ error: "User already has this role" }, { status: 400 });
    }

    // Assign role
    const { data: userRole, error } = await supabase
      .from("user_roles")
      .insert({
        user_id: id,
        role_id: roleId,
        assigned_by: user?.id,
      })
      .select(
        `
        *,
        role:roles(*)
      `,
      )
      .single();

    if (error) throw error;

    return NextResponse.json({ userRole }, { status: 201 });
  } catch (error) {
    console.error("Error assigning role:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to assign role" },
      { status: 500 },
    );
  }
}

// DELETE /api/rbac/users/[id]/roles/[roleId] - Remove role from user
export async function DELETE(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canAssign = await hasPermission("users.assign_roles");
    if (!canAssign) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const supabase = await createClient();
    const url = new URL(request.url);
    const roleId = url.searchParams.get("roleId");

    if (!roleId) {
      return NextResponse.json({ error: "Role ID is required" }, { status: 400 });
    }

    const { error } = await supabase.from("user_roles").delete().eq("user_id", id).eq("role_id", roleId);

    if (error) throw error;

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error removing role:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to remove role" },
      { status: 500 },
    );
  }
}
