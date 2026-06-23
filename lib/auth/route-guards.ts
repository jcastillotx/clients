import { NextResponse } from "next/server";
import { apiError } from "@/lib/api/response";
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

function guardRequest(request?: Request): Request {
  return request ?? new Request("http://localhost/api/internal");
}

export async function requireAuthenticatedUser(
  request?: Request,
): Promise<GuardSuccess | GuardFailure> {
  const supabase = await createClient();
  const {
    data: { user },
    error,
  } = await supabase.auth.getUser();

  if (error || !user) {
    return {
      error: apiError(guardRequest(request), {
        status: 401,
        code: "UNAUTHORIZED",
        message: "Unauthorized",
      }),
    };
  }

  return { supabase, user };
}

export async function requireAdminUser(
  request?: Request,
): Promise<GuardSuccess | GuardFailure> {
  const auth = await requireAuthenticatedUser(request);
  if ("error" in auth) {
    return auth;
  }

  const admin = await isUserAdmin(auth.user.id);
  if (!admin) {
    return {
      error: apiError(guardRequest(request), {
        status: 403,
        code: "FORBIDDEN",
        message: "Permission denied",
      }),
    };
  }

  return auth;
}

export async function requirePermission(
  permission: string,
  request?: Request,
): Promise<GuardSuccess | GuardFailure> {
  const auth = await requireAuthenticatedUser(request);
  if ("error" in auth) {
    return auth;
  }

  const allowed = await hasPermission(permission, {
    supabase: auth.supabase,
    userId: auth.user.id,
  });

  if (!allowed) {
    return {
      error: apiError(guardRequest(request), {
        status: 403,
        code: "FORBIDDEN",
        message: "Permission denied",
      }),
    };
  }

  return auth;
}
