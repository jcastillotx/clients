import { createClient, createAdminClientIfAvailable } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";
import { db } from "@/lib/db";
import { staffAssignments } from "@/lib/db/schema/activity-logs";
import { eq, and } from "drizzle-orm";

/**
 * GET /api/clients/[id]/staff
 *
 * Returns all staff assignments for a client.
 */
export async function GET(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;

  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request, "Authentication required");
    }

    const accessOptions = { supabase, userId: user.id };
    const metadataRole = String(
      user.user_metadata?.role ?? user.user_metadata?.app_role ?? "",
    ).toLowerCase();
    const hasManagementMetadataRole =
      user.user_metadata?.is_super_admin === true ||
      metadataRole === Roles.SUPER_ADMIN ||
      metadataRole === Roles.ADMIN ||
      metadataRole === Roles.ACCOUNT_MANAGER;

    const [canReadClients, hasManagementRoleDb] = await Promise.all([
      hasPermission(Permissions.CLIENTS_READ, accessOptions),
      hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
    ]);

    if (!(canReadClients || hasManagementRoleDb || hasManagementMetadataRole)) {
      return apiForbidden(request);
    }

    // Use admin client to bypass RLS on users join
    const adminClient = createAdminClientIfAvailable();
    const lookupClient = adminClient ?? supabase;

    const { data: assignments, error } = await lookupClient
      .from("staff_assignments")
      .select(`
        id,
        role,
        created_at,
        user:users(id, name, email, avatar)
      `)
      .eq("client_id", id);

    if (error) {
      console.error("Error fetching staff assignments:", error);
      return apiInternalError(request, "Failed to fetch staff assignments");
    }

    // Normalize the Supabase join response
    const normalized = (assignments || []).flatMap((assignment) => {
      const user = Array.isArray(assignment.user)
        ? (assignment.user[0] ?? null)
        : assignment.user;

      if (!user) return [];

      return [{
        id: assignment.id,
        role: assignment.role,
        created_at: assignment.created_at,
        user,
      }];
    });

    return apiSuccess(request, normalized);
  } catch (error) {
    console.error("Error in GET /api/clients/[id]/staff:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch staff assignments",
    );
  }
}

/**
 * POST /api/clients/[id]/staff
 *
 * Assign a staff member to a client.
 * Body: { userId: string, role?: string }
 */
export async function POST(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;

  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request, "Authentication required");
    }

    const accessOptions = { supabase, userId: user.id };
    const metadataRole = String(
      user.user_metadata?.role ?? user.user_metadata?.app_role ?? "",
    ).toLowerCase();
    const hasManagementMetadataRole =
      user.user_metadata?.is_super_admin === true ||
      metadataRole === Roles.SUPER_ADMIN ||
      metadataRole === Roles.ADMIN ||
      metadataRole === Roles.ACCOUNT_MANAGER;

    const [canUpdateClients, hasManagementRoleDb] = await Promise.all([
      hasPermission(Permissions.CLIENTS_UPDATE, accessOptions),
      hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
    ]);

    if (!(canUpdateClients || hasManagementRoleDb || hasManagementMetadataRole)) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const { userId, role } = body;

    if (!userId || typeof userId !== "string") {
      return apiError(request, { status: 400, code: "BAD_REQUEST", message: "userId is required" });
    }

    const assignmentRole = typeof role === "string" && role.trim() ? role.trim() : "member";

    const [assignment] = await db
      .insert(staffAssignments)
      .values({
        clientId: id,
        userId,
        role: assignmentRole,
      })
      .onConflictDoUpdate({
        target: [staffAssignments.clientId, staffAssignments.userId],
        set: { role: assignmentRole, updatedAt: new Date() },
      })
      .returning();

    return apiSuccess(request, assignment, { status: 201 });
  } catch (error) {
    console.error("Error in POST /api/clients/[id]/staff:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to assign staff",
    );
  }
}

/**
 * DELETE /api/clients/[id]/staff
 *
 * Remove a staff assignment.
 * Body: { userId: string }
 */
export async function DELETE(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;

  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request, "Authentication required");
    }

    const accessOptions = { supabase, userId: user.id };
    const metadataRole = String(
      user.user_metadata?.role ?? user.user_metadata?.app_role ?? "",
    ).toLowerCase();
    const hasManagementMetadataRole =
      user.user_metadata?.is_super_admin === true ||
      metadataRole === Roles.SUPER_ADMIN ||
      metadataRole === Roles.ADMIN ||
      metadataRole === Roles.ACCOUNT_MANAGER;

    const [canUpdateClients, hasManagementRoleDb] = await Promise.all([
      hasPermission(Permissions.CLIENTS_UPDATE, accessOptions),
      hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
    ]);

    if (!(canUpdateClients || hasManagementRoleDb || hasManagementMetadataRole)) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const { userId } = body;

    if (!userId || typeof userId !== "string") {
      return apiError(request, { status: 400, code: "BAD_REQUEST", message: "userId is required" });
    }

    const [deleted] = await db
      .delete(staffAssignments)
      .where(
        and(
          eq(staffAssignments.clientId, id),
          eq(staffAssignments.userId, userId),
        ),
      )
      .returning();

    if (!deleted) {
      return apiError(request, { status: 400, code: "BAD_REQUEST", message: "Staff assignment not found" });
    }

    return apiSuccess(request, { removed: true });
  } catch (error) {
    console.error("Error in DELETE /api/clients/[id]/staff:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to remove staff assignment",
    );
  }
}
