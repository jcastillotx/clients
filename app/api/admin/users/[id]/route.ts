import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";

export async function PATCH(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canManage = await hasPermission("users.manage");
    if (!canManage) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const body = await request.json();
    const { name, email, phone, password, client_id, is_active, roles } = body;

    const supabase = await createClient();

    // Update auth user if password changed
    if (password) {
      await supabase.auth.admin.updateUserById(id, {
        password,
        user_metadata: { name, phone, client_id },
      });
    }

    // Update user record
    const { data: user, error } = await supabase
      .from("users")
      .update({
        name,
        email,
        phone,
        client_id,
        is_active,
        status: is_active ? "active" : "inactive",
        updated_at: new Date().toISOString(),
      })
      .eq("id", id)
      .select()
      .single();

    if (error) throw error;

    // Update roles
    if (roles) {
      // Remove existing roles
      await supabase.from("user_roles").delete().eq("user_id", id);

      // Add new roles
      if (roles.length > 0) {
        await supabase
          .from("user_roles")
          .insert(roles.map((role_id: string) => ({ user_id: id, role_id })));
      }
    }

    // Fetch complete user with roles
    const { data: completeUser } = await supabase
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

export async function DELETE(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canManage = await hasPermission("users.manage");
    if (!canManage) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const supabase = await createClient();

    // Soft delete user
    const { error } = await supabase
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
