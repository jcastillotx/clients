import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { collectRoleNames } from "@/lib/rbac/role-row-utils";
import {
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

/**
 * GET /api/marketing/features
 */
export async function GET(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  try {
    const { data: userData } = await supabase
      .from("users")
      .select("client_id, is_super_admin")
      .eq("id", user.id)
      .single();

    const { data: roleRows } = await supabase
      .from("user_roles")
      .select("role:roles(name)")
      .eq("user_id", user.id);

    const roleNames = collectRoleNames(roleRows);

    const isAdmin = Boolean(userData?.is_super_admin) || roleNames.has("admin") || roleNames.has("super_admin");
    const isAccountManager = roleNames.has("account_manager");
    const isStaff = isAdmin || isAccountManager || roleNames.has("staff");

    const requestedClientId = req.nextUrl.searchParams.get("client_id");
    let clientId: string | null = null;

    if (requestedClientId && isStaff) {
      clientId = requestedClientId;
    } else {
      clientId = userData?.client_id || null;
    }

    const { data: allFeatures, error: featuresError } = await supabase
      .from("features")
      .select("id, name, display_name, description, category, is_enabled_by_default")
      .eq("category", "marketing")
      .order("display_name");

    if (featuresError) throw featuresError;

    // For admins with no client context, return platform defaults with no counts
    if (!clientId) {
      const features = (allFeatures || []).map((f) => ({
        name: f.name,
        displayName: f.display_name,
        description: f.description,
        isEnabled: f.is_enabled_by_default,
        itemCount: 0,
        config: null,
      }));
      const payload = { clientId: null, features, isStaff, noClientContext: true };
      return apiSuccess(req, payload, { extra: payload });
    }

    const featureIds = (allFeatures || []).map((f) => f.id);
    const { data: clientOverrides } = await supabase
      .from("client_features")
      .select("feature_id, is_enabled, config")
      .eq("client_id", clientId)
      .in("feature_id", featureIds);

    const overrideMap = new Map(
      (clientOverrides || []).map((co) => [co.feature_id, co]),
    );

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

    const payload = { clientId, features, isStaff, noClientContext: false };
    return apiSuccess(req, payload, { extra: payload });
  } catch (error) {
    console.error("Error fetching marketing features:", error);
    return apiInternalError(req, "Failed to fetch marketing features");
  }
}
