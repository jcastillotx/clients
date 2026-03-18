import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { isUserAdmin } from "@/lib/rbac/check";
import { createClient } from "@/lib/supabase/server";

type SupabaseClient = Awaited<ReturnType<typeof createClient>>;
type AuthUser = NonNullable<
  Awaited<ReturnType<SupabaseClient["auth"]["getUser"]>>["data"]["user"]
>;

type GuardSuccess = {
  supabase: SupabaseClient;
  user: AuthUser;
};

type GuardFailure = {
  error: NextResponse;
};

export async function requireAuthenticatedUser(): Promise<
  GuardSuccess | GuardFailure
> {
  const supabase = await createClient();
  const {
    data: { user },
    error,
  } = await supabase.auth.getUser();

  if (error || !user) {
    return {
      error: NextResponse.json({ error: "Unauthorized" }, { status: 401 }),
    };
  }

  return { supabase, user };
}

export async function requireAdminUser(): Promise<GuardSuccess | GuardFailure> {
  const auth = await requireAuthenticatedUser();
  if ("error" in auth) {
    return auth;
  }

  const admin = await isUserAdmin(auth.user.id);
  if (!admin) {
    return {
      error: NextResponse.json({ error: "Permission denied" }, { status: 403 }),
    };
  }

  return auth;
}

export async function requirePermission(
  permission: string,
): Promise<GuardSuccess | GuardFailure> {
  const auth = await requireAuthenticatedUser();
  if ("error" in auth) {
    return auth;
  }

  const allowed = await hasPermission(permission, {
    supabase: auth.supabase,
    userId: auth.user.id,
  });

  if (!allowed) {
    return {
      error: NextResponse.json({ error: "Permission denied" }, { status: 403 }),
    };
  }

  return auth;
}
