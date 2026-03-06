import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";

/**
 * GET /api/marketing/features
 *
 * Returns the marketing feature flags for a given client.
 * - Admin/account managers can pass ?client_id=<uuid> to view any client's features.
 * - Regular users see features for their own client.
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
    // Determine the user's role and client
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

    // Determine which client to look up
    const requestedClientId = req.nextUrl.searchParams.get("client_id");
    let clientId: string | null = null;

    if (requestedClientId && isStaff) {
      clientId = requestedClientId;
    } else {
      clientId = userData?.client_id || null;
    }

    if (!clientId) {
      return NextResponse.json({ error: "No client context" }, { status: 400 });
    }

    // Fetch marketing-category features with their client-level overrides
    const { data: allFeatures, error: featuresError } = await supabase
      .from("features")
      .select("id, name, display_name, description, category, is_enabled_by_default")
      .eq("category", "marketing")
      .order("display_name");

    if (featuresError) throw featuresError;

    // Fetch client-level overrides for these features
    const featureIds = (allFeatures || []).map((f) => f.id);
    const { data: clientOverrides } = await supabase
      .from("client_features")
      .select("feature_id, is_enabled, config")
      .eq("client_id", clientId)
      .in("feature_id", featureIds);

    const overrideMap = new Map(
      (clientOverrides || []).map((co) => [co.feature_id, co]),
    );

    // Also fetch summary counts for each enabled feature
    const [
      { count: campaignCount },
      { count: leadCount },
      { count: contentCount },
    ] = await Promise.all([
      supabase.from("campaigns").select("id", { count: "exact", head: true }).eq("client_id", clientId),
      supabase.from("leads").select("id", { count: "exact", head: true }).eq("client_id", clientId),
      supabase.from("content_calendar_items").select("id", { count: "exact", head: true }).eq("client_id", clientId),
    ]);

    const itemCounts: Record<string, number> = {
      marketing_tools: campaignCount || 0,
      lead_management: leadCount || 0,
      content_calendar: contentCount || 0,
    };

    const features = (allFeatures || []).map((f) => {
      const override = overrideMap.get(f.id);
      const isEnabled = override ? override.is_enabled : f.is_enabled_by_default;
      return {
        name: f.name,
        displayName: f.display_name,
        description: f.description,
        isEnabled,
        itemCount: itemCounts[f.name] ?? 0,
        config: override?.config || null,
      };
    });

    return NextResponse.json({
      clientId,
      features,
      isStaff,
    });
  } catch (error) {
    console.error("Error fetching marketing features:", error);
    return NextResponse.json({ error: "Failed to fetch marketing features" }, { status: 500 });
  }
}
