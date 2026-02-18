import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";

export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Authentication required" }, { status: 401 });
    }

    const metadataRole = String(user.user_metadata?.role ?? user.user_metadata?.app_role ?? "").toLowerCase();
    const hasManagementMetadataRole =
      user.user_metadata?.is_super_admin === true ||
      metadataRole === Roles.SUPER_ADMIN ||
      metadataRole === Roles.ADMIN ||
      metadataRole === Roles.ACCOUNT_MANAGER;

    const accessOptions = { supabase, userId: user.id };
    const [canCreateClients, hasManagementRoleDb] = await Promise.all([
      hasPermission(Permissions.CLIENTS_CREATE, accessOptions),
      hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
    ]);
    const hasManagementRole = hasManagementRoleDb || hasManagementMetadataRole;

    if (!(canCreateClients || hasManagementRole)) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const body = await request.json();

    if (!body?.company_name) {
      return NextResponse.json({ error: "Company name is required" }, { status: 400 });
    }

    if (!body?.email) {
      return NextResponse.json({ error: "Email is required" }, { status: 400 });
    }

    const adminClient = hasManagementRole ? createAdminClientIfAvailable() : null;
    const dbClient = adminClient ?? supabase;

    if (hasManagementRole && !adminClient) {
      console.warn("Service-role Supabase key missing; falling back to session client for POST /api/clients");
    }

    const { data: client, error } = await dbClient.from("clients").insert(body).select().single();

    if (error) throw error;

    return NextResponse.json({ client }, { status: 201 });
  } catch (error) {
    console.error("Error creating client:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to create client",
      },
      { status: 500 },
    );
  }
}
