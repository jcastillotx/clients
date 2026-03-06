import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { z } from "zod";

/**
 * GET /api/marketing/campaigns
 * 
 * Fetch all marketing campaigns for the authenticated user's client
 */
export async function GET(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    // Get user's client and role info
    const { data: userData } = await supabase
      .from("users")
      .select("client_id, is_super_admin")
      .eq("id", user.id)
      .single();

    const { data: roleRows } = await supabase
      .from("user_roles")
      .select("role:roles(name)")
      .eq("user_id", user.id);

    const roleNames = new Set<string>();
    for (const row of roleRows || []) {
      const roleName = String((row as any)?.role?.name || (row as any)?.role?.[0]?.name || "").toLowerCase();
      if (roleName) roleNames.add(roleName);
    }

    const isAdmin = Boolean(userData?.is_super_admin) || roleNames.has("admin") || roleNames.has("super_admin");
    const isAccountManager = roleNames.has("account_manager");
    const isStaff = isAdmin || isAccountManager || roleNames.has("staff");

    if (!isStaff) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    // Staff can optionally filter by client_id
    const clientIdFilter = req.nextUrl.searchParams.get("client_id");

    let query = supabase
      .from("campaigns")
      .select(`
        *,
        client:clients(id, company_name),
        created_by_user:users!campaigns_created_by_fkey(id, name)
      `)
      .order("created_at", { ascending: false });

    if (clientIdFilter) {
      query = query.eq("client_id", clientIdFilter);
    } else if (!isAdmin) {
      // Non-admin staff see only their client's campaigns
      query = query.eq("client_id", userData!.client_id!);
    }

    const { data, error } = await query;

    if (error) throw error;

    return NextResponse.json(data);
  } catch (error) {
    console.error("Error fetching campaigns:", error);
    return NextResponse.json({ error: "Failed to fetch campaigns" }, { status: 500 });
  }
}

/**
 * POST /api/marketing/campaigns
 * 
 * Create a new marketing campaign
 */
export async function POST(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const body = await req.json();
    
    // Validate input
    const { createCampaignSchema } = await import("@/lib/validations/marketing");
    const validatedData = createCampaignSchema.parse(body);

    // Get user's client_id
    const { data: userData } = await supabase.from("users").select("client_id").eq("id", user.id).single();

    if (!userData?.client_id) {
      return NextResponse.json({ error: "User not associated with a client" }, { status: 400 });
    }

    const { data, error } = await supabase
      .from("campaigns")
      .insert({
        client_id: userData.client_id,
        name: validatedData.name,
        description: validatedData.description || null,
        campaign_type: validatedData.type,
        status: validatedData.status,
        start_date: validatedData.start_date || null,
        end_date: validatedData.end_date || null,
        budget: validatedData.budget || null,
        currency: validatedData.currency,
        created_by: user.id,
        metadata: validatedData.metadata || null,
      })
      .select()
      .single();

    if (error) throw error;

    return NextResponse.json(data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }
    console.error("Error creating campaign:", error);
    return NextResponse.json({ error: "Failed to create campaign" }, { status: 500 });
  }
}
