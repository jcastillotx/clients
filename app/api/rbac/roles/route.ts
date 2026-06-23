import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { hasAnyPermission, hasPermission } from "@/lib/rbac/permissions";

/** GET /api/rbac/roles — list all roles */
export async function GET(request: Request) {
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

    const { data: roles, error } = await supabase
      .from("roles")
      .select(
        `
        *,
        role_permissions(
          permission:permissions(*)
        )
      `,
      )
      .order("name");

    if (error) {
      throw error;
    }

    return apiSuccess(request, roles ?? [], { extra: { roles: roles ?? [] } });
  } catch (error) {
    console.error("Error fetching roles:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch roles",
    );
  }
}

/** POST /api/rbac/roles — create a new role */
export async function POST(request: Request) {
  try {
    const canCreate = await hasPermission("roles.create");
    if (!canCreate) {
      return apiForbidden(request);
    }

    const supabase = await createClient();
    const body = await request.json();

    const { name, description, permissionIds } = body;

    if (!name) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Role name is required",
      });
    }

    const { data: role, error: roleError } = await supabase
      .from("roles")
      .insert({
        name,
        description,
        is_system: false,
      })
      .select()
      .single();

    if (roleError) {
      throw roleError;
    }

    if (permissionIds && permissionIds.length > 0) {
      const rolePermissions = permissionIds.map((permissionId: string) => ({
        role_id: role.id,
        permission_id: permissionId,
      }));

      const { error: permError } = await supabase.from("role_permissions").insert(rolePermissions);

      if (permError) {
        throw permError;
      }
    }

    return apiSuccess(request, role, { status: 201, extra: { role } });
  } catch (error) {
    console.error("Error creating role:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to create role",
    );
  }
}
