import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { hasAnyPermission, hasPermission } from "@/lib/rbac/permissions";

/** GET /api/rbac/roles/[id] */
export async function GET(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();

    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    const canReadRbac = await hasAnyPermission(["roles.read", "settings.read"], {
      supabase,
      userId: user.id,
    });
    if (!canReadRbac) {
      return apiForbidden(request);
    }

    const { data: role, error } = await supabase
      .from("roles")
      .select(
        `
        *,
        role_permissions(
          permission:permissions(*)
        )
      `,
      )
      .eq("id", id)
      .single();

    if (error) throw error;
    if (!role) {
      return apiNotFound(request, "Role not found");
    }

    return apiSuccess(request, role, { extra: { role } });
  } catch (error) {
    console.error("Error fetching role:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch role",
    );
  }
}

/** PATCH /api/rbac/roles/[id] */
export async function PATCH(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canUpdate = await hasPermission("roles.update");
    if (!canUpdate) {
      return apiForbidden(request);
    }

    const supabase = await createClient();
    const body = await request.json();

    const { name, description, permissionIds } = body;

    const { data: existingRole } = await supabase.from("roles").select("is_system").eq("id", id).maybeSingle();

    if (!existingRole) {
      return apiNotFound(request, "Role not found");
    }

    if (existingRole.is_system && name) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Cannot rename system roles",
      });
    }

    const { data: role, error: roleError } = await supabase
      .from("roles")
      .update({
        name,
        description,
        updated_at: new Date().toISOString(),
      })
      .eq("id", id)
      .select()
      .single();

    if (roleError) throw roleError;

    if (permissionIds !== undefined) {
      await supabase.from("role_permissions").delete().eq("role_id", id);

      if (permissionIds.length > 0) {
        const rolePermissions = permissionIds.map((permissionId: string) => ({
          role_id: id,
          permission_id: permissionId,
        }));

        const { error: permError } = await supabase.from("role_permissions").insert(rolePermissions);

        if (permError) throw permError;
      }
    }

    return apiSuccess(request, role, { extra: { role } });
  } catch (error) {
    console.error("Error updating role:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to update role",
    );
  }
}

/** DELETE /api/rbac/roles/[id] */
export async function DELETE(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canDelete = await hasPermission("roles.delete");
    if (!canDelete) {
      return apiForbidden(request);
    }

    const supabase = await createClient();

    const { data: role } = await supabase.from("roles").select("is_system, name").eq("id", id).maybeSingle();

    if (!role) {
      return apiNotFound(request, "Role not found");
    }

    if (role.is_system) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Cannot delete system roles",
      });
    }

    const { error } = await supabase.from("roles").delete().eq("id", id);

    if (error) throw error;

    return apiSuccess(request, { deleted: true });
  } catch (error) {
    console.error("Error deleting role:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to delete role",
    );
  }
}
