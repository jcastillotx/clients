import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";

export async function POST(request: Request) {
  try {
    const canManage = await hasPermission("users.manage");
    if (!canManage) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const body = await request.json();
    const { name, email, phone, password, client_id, is_active, roles } = body;

    const supabase = await createClient();

    // Create user in Supabase Auth
    const { data: authData, error: authError } = await supabase.auth.admin.createUser({
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

    // Create user record in database
    const { data: user, error: dbError } = await supabase
      .from("users")
      .insert({
        id: authData.user.id,
        name,
        email,
        phone,
        client_id,
        is_active,
        status: is_active ? "active" : "inactive",
      })
      .select()
      .single();

    if (dbError) throw dbError;

    // Assign roles
    if (roles && roles.length > 0) {
      await supabase
        .from("user_roles")
        .insert(roles.map((role_id: string) => ({ user_id: user.id, role_id })));
    }

    // Fetch complete user with roles
    const { data: completeUser } = await supabase
      .from("users")
      .select(`
        *,
        client:clients(id, company_name),
        user_roles(role:roles(id, name, description))
      `)
      .eq("id", user.id)
      .single();

    return NextResponse.json({ user: completeUser }, { status: 201 });
  } catch (error) {
    console.error("Error creating user:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to create user",
      },
      { status: 500 },
    );
  }
}
