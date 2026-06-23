import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import {
  hasAnyRole,
  hasPermission,
  Permissions,
  Roles,
} from "@/lib/rbac/permissions";
import {
  archiveClientWithRecords,
  restoreClientWithRecords,
} from "@/lib/clients/archive-client";
import { createClient } from "@/lib/supabase/server";

async function requireArchiveAccess(request: Request) {
  const supabase = await createClient();
  const {
    data: { user },
    error: authError,
  } = await supabase.auth.getUser();

  if (authError || !user) {
    return { error: apiUnauthorized(request, "Authentication required") };
  }

  const metadataRole = String(
    user.user_metadata?.role ?? user.user_metadata?.app_role ?? "",
  ).toLowerCase();
  const hasManagementMetadataRole =
    user.user_metadata?.is_super_admin === true ||
    metadataRole === Roles.SUPER_ADMIN ||
    metadataRole === Roles.ADMIN ||
    metadataRole === Roles.ACCOUNT_MANAGER;

  const accessOptions = { supabase, userId: user.id };
  const [canArchiveClients, hasManagementRoleDb] = await Promise.all([
    hasPermission(Permissions.CLIENTS_DELETE, accessOptions),
    hasAnyRole(
      [Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER],
      accessOptions,
    ),
  ]);

  if (
    !(canArchiveClients || hasManagementRoleDb || hasManagementMetadataRole)
  ) {
    return { error: apiForbidden(request) };
  }

  return { user };
}

export async function POST(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const guard = await requireArchiveAccess(request);
    if ("error" in guard) {
      return guard.error;
    }

    const { id } = await params;
    const result = await archiveClientWithRecords(id);

    if (!result.client) {
      return apiNotFound(request, "Client not found");
    }

    const payload = {
      client: result.client,
      alreadyArchived: result.alreadyArchived,
      archivedRecords: result.summaries,
    };

    return apiSuccess(request, payload, { extra: payload });
  } catch (error) {
    console.error("Error archiving client:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to archive client",
    );
  }
}

export async function DELETE(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const guard = await requireArchiveAccess(request);
    if ("error" in guard) {
      return guard.error;
    }

    const { id } = await params;
    const result = await restoreClientWithRecords(id);

    if (!result.client) {
      return apiNotFound(request, "Client not found");
    }

    const payload = {
      client: result.client,
      alreadyRestored: result.alreadyRestored,
      restoredRecords: result.summaries,
    };

    return apiSuccess(request, payload, { extra: payload });
  } catch (error) {
    console.error("Error restoring client:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to restore client",
    );
  }
}
