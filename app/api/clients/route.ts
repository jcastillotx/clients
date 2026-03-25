import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";
import { inngest } from "@/lib/inngest/client";
import { db } from "@/lib/db";
import { clients } from "@/lib/db/schema";

export async function GET(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Authentication required" }, { status: 401 });
    }

    const { searchParams } = new URL(request.url);
    const includeInactive = searchParams.get("includeInactive") === "true";

    let query = supabase.from("clients").select("id, company_name, status").is("deleted_at", null).order("company_name");
    if (!includeInactive) {
      query = query.eq("status", "active");
    }

    const { data, error } = await query;
    if (error) {
      throw error;
    }

    return NextResponse.json({
      success: true,
      data: data ?? [],
    });
  } catch (error) {
    console.error("Error listing clients:", error);
    return NextResponse.json(
      {
        success: false,
        error: error instanceof Error ? error.message : "Failed to fetch clients",
      },
      { status: 500 },
    );
  }
}

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

    // Sanitize and map
    const insertData: any = {
      companyName: body.company_name,
      email: body.email,
      domain: body.domain || null,
      industry: body.industry || null,
      logoUrl: body.logo_url || null,
      status: body.status || "active",
      primaryContactId: body.primary_contact_id || null,
      phone: body.phone || null,
      address: body.address || null,
      city: body.city || null,
      state: body.state || null,
      zipCode: body.zip_code || null,
      country: body.country || "US",
      website: body.website || null,
    };

    if (!insertData.companyName) {
      return NextResponse.json({ error: "Company name is required" }, { status: 400 });
    }

    if (!insertData.email) {
      return NextResponse.json({ error: "Email is required" }, { status: 400 });
    }

    const [newClient] = await db.insert(clients).values(insertData).returning();

    if (!newClient) {
      throw new Error("Failed to create client record");
    }

    await inngest.send({
      name: "client.created",
      data: {
        clientId: newClient.id,
        companyName: newClient.companyName,
      },
    });

    return NextResponse.json({ client: newClient }, { status: 201 });
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
