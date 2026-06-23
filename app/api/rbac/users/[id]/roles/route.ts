import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
} from "@/lib/api/response";
import { hasPermission } from "@/lib/rbac/permissions";

/** GET /api/rbac/users/[id]/roles */
export async function GET(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canRead = await hasPermission("users.read");
    if (!canRead) {
      return apiForbidden(request);
    }

    const supabase = await createClient();

    const { data: userRoles, error } = await supabase
      .from("user_roles")
      .select(
        `
        *,
        role:roles(*)
      `,
      )
      .eq("user_id", id);

    if (error) throw error;

    return apiSuccess(request, userRoles ?? [], { extra: { userRoles: userRoles ?? [] } });
  } catch (error) {
    console.error("Error fetching user roles:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch user roles",
    );
  }
}

/** POST /api/rbac/users/[id]/roles */
export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canAssign = await hasPermission("users.assign_roles");
    if (!canAssign) {
      return apiForbidden(request);
    }

    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    const { roleId } = await request.json();

    if (!roleId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Role ID is required",
      });
    }

    const { data: existing } = await supabase
      .from("user_roles")
      .select("*")
      .eq("user_id", id)
      .eq("role_id", roleId)
      .maybeSingle();

    if (existing) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "User already has this role",
      });
    }

    const { data: userRole, error } = await supabase
      .from("user_roles")
      .insert({
        user_id: id,
        role_id: roleId,
        assigned_by: user?.id,
      })
      .select(
        `
        *,
        role:roles(*)
      `,
      )
      .single();

    if (error) throw error;

    return apiSuccess(request, userRole, { status: 201, extra: { userRole } });
  } catch (error) {
    console.error("Error assigning role:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to assign role",
    );
  }
}

/** DELETE /api/rbac/users/[id]/roles */
export async function DELETE(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canAssign = await hasPermission("users.assign_roles");
    if (!canAssign) {
      return apiForbidden(request);
    }

    const supabase = await createClient();
    const url = new URL(request.url);
    const roleId = url.searchParams.get("roleId");

    if (!roleId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Role ID is required",
      });
    }

    const { error } = await supabase.from("user_roles").delete().eq("user_id", id).eq("role_id", roleId);

    if (error) throw error;

    return apiSuccess(request, { deleted: true });
  } catch (error) {
    console.error("Error removing role:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to remove role",
    );
  }
}
