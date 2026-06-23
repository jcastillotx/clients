import { createClient } from "@/lib/supabase/server";

/**
 * Feature Flag Checker
 *
 * Priority: User > Role > Client > Global
 */

export async function isFeatureEnabled(
  featureName: string,
  options: {
    userId?: string;
    clientId?: string;
    roleIds?: string[];
  } = {},
): Promise<boolean> {
  const supabase = await createClient();

  // Get feature definition
  const { data: feature } = await supabase
    .from("features")
    .select("id, is_enabled_by_default")
    .eq("name", featureName)
    .single();

  if (!feature) {
    console.warn(`Feature "${featureName}" not found`);
    return false;
  }

  // 1. Check user-level override (highest priority)
  if (options.userId) {
    const { data: userFeature } = await supabase
      .from("user_features")
      .select("is_enabled")
      .eq("user_id", options.userId)
      .eq("feature_id", feature.id)
      .single();

    if (userFeature) {
      return userFeature.is_enabled;
    }
  }

  // 2. Check role-level toggles
  if (options.roleIds && options.roleIds.length > 0) {
    const { data: roleFeatures } = await supabase
      .from("role_features")
      .select("is_enabled")
      .in("role_id", options.roleIds)
      .eq("feature_id", feature.id);

    if (roleFeatures && roleFeatures.length > 0) {
      // If ANY role has it enabled, feature is enabled
      return roleFeatures.some((rf) => rf.is_enabled);
    }
  }

  // 3. Check client-level toggle
  if (options.clientId) {
    const { data: clientFeature } = await supabase
      .from("client_features")
      .select("is_enabled")
      .eq("client_id", options.clientId)
      .eq("feature_id", feature.id)
      .single();

    if (clientFeature) {
      return clientFeature.is_enabled;
    }
  }

  // 4. Fall back to global default
  return feature.is_enabled_by_default;
}

/**
 * Check multiple features at once
 */
export async function getFeaturesForUser(userId: string, clientId?: string): Promise<Record<string, boolean>> {
  const supabase = await createClient();

  // Get user's roles
  const { data: userRoles } = await supabase.from("user_roles").select("role_id").eq("user_id", userId);

  const roleIds = userRoles?.map((ur) => ur.role_id) || [];

  // Get all features
  const { data: allFeatures } = await supabase.from("features").select("name");

  if (!allFeatures) return {};

  // Check each feature
  const featureFlags: Record<string, boolean> = {};

  for (const feature of allFeatures) {
    featureFlags[feature.name] = await isFeatureEnabled(feature.name, {
      userId,
      clientId,
      roleIds,
    });
  }

  return featureFlags;
}

/**
 * Get feature configuration for a client
 */
export async function getFeatureConfig(
  featureName: string,
  clientId: string,
): Promise<Record<string, unknown> | null> {
  const supabase = await createClient();

  const { data } = await supabase
    .from("client_features")
    .select("config, features!inner(name)")
    .eq("client_id", clientId)
    .eq("features.name", featureName)
    .single();

  return data?.config || null;
}

/**
 * Enable a feature for a client
 */
export async function enableFeatureForClient(
  featureName: string,
  clientId: string,
  enabledBy: string,
  config?: Record<string, unknown>,
): Promise<void> {
  const supabase = await createClient();

  const { data: feature } = await supabase.from("features").select("id").eq("name", featureName).single();

  if (!feature) {
    throw new Error(`Feature "${featureName}" not found`);
  }

  await supabase.from("client_features").upsert(
    {
      client_id: clientId,
      feature_id: feature.id,
      is_enabled: true,
      enabled_at: new Date().toISOString(),
      enabled_by: enabledBy,
      config,
    },
    {
      onConflict: "client_id,feature_id",
    },
  );
}

/**
 * Disable a feature for a client
 */
export async function disableFeatureForClient(
  featureName: string,
  clientId: string,
  disabledBy: string,
  notes?: string,
): Promise<void> {
  const supabase = await createClient();

  const { data: feature } = await supabase.from("features").select("id").eq("name", featureName).single();

  if (!feature) {
    throw new Error(`Feature "${featureName}" not found`);
  }

  await supabase.from("client_features").upsert(
    {
      client_id: clientId,
      feature_id: feature.id,
      is_enabled: false,
      disabled_at: new Date().toISOString(),
      disabled_by: disabledBy,
      notes,
    },
    {
      onConflict: "client_id,feature_id",
    },
  );
}
