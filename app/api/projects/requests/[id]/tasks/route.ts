import { NextRequest } from "next/server";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

import { createClient } from "@/lib/supabase/server";

async function resolveAccess(supabase: Awaited<ReturnType<typeof createClient>>, user: { id: string; user_metadata?: Record<string, unknown> }) {
  const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
    supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle(),
    supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
  ]);

  const metadataRole = String(user.user_metadata?.role || user.user_metadata?.app_role || "").toLowerCase();
  const roleNames = (roleRows || []).map((row: unknown) => {
    const roleRow = row as { role?: { name?: string } | Array<{ name?: string }> };
    if (Array.isArray(roleRow.role)) {
      return String(roleRow.role[0]?.name || "").toLowerCase();
    }
    return String(roleRow.role?.name || "").toLowerCase();
  });

  const isAdmin = Boolean(
    dbUser?.is_super_admin ||
      user.user_metadata?.is_super_admin === true ||
      metadataRole === "admin" ||
      metadataRole === "super_admin" ||
      roleNames.includes("admin") ||
      roleNames.includes("super_admin"),
  );

  return {
    clientId: dbUser?.client_id || null,
    isAdmin,
  };
}

/**
 * GET /api/projects/requests/[id]/tasks
 *
 * AJAX feed for project-request-related tasks.
 */
export async function GET(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const access = await resolveAccess(supabase, user);

    const { data: requestRow, error: requestError } = await supabase
      .from("requests")
      .select("id, client_id, custom_fields")
      .eq("id", id)
      .contains("custom_fields", { type: "project" })
      .single();

    if (requestError || !requestRow) {
      return apiNotFound(request, "Project request not found");
    }

    if (!access.isAdmin && access.clientId !== requestRow.client_id) {
      return apiForbidden(request);
    }

    // Tasks are linked to a request via the board created at approval time.
    // Prefer querying by board_id (taskBoardId in custom_fields) so that tasks
    // added directly to the board show up here. Fall back to request_id for
    // any tasks created through older flows that set it directly.
    const taskBoardId = (requestRow.custom_fields as Record<string, unknown> | null)?.taskBoardId as string | null | undefined;

    const baseQuery = supabase
      .from("staff_tasks")
      .select("id, title, description, priority, due_date, progress, completed_at, column_id, created_at, updated_at")
      .order("created_at", { ascending: false });

    const { data: tasks, error: tasksError } = taskBoardId
      ? await baseQuery.eq("board_id", taskBoardId)
      : await baseQuery.eq("request_id", id);

    if (tasksError) {
      return apiInternalError(request, tasksError.message);
    }

    const columnIds = Array.from(new Set((tasks || []).map((task) => task.column_id).filter(Boolean)));
    const taskIds = (tasks || []).map((task) => task.id);

    const [columnsResult, assigneesResult] = await Promise.all([
      columnIds.length > 0
        ? supabase.from("staff_task_columns").select("id, name").in("id", columnIds)
        : Promise.resolve({ data: [], error: null }),
      taskIds.length > 0
        ? supabase
            .from("staff_task_assignees")
            .select("task_id, user:users(id, name, avatar)")
            .in("task_id", taskIds)
        : Promise.resolve({ data: [], error: null }),
    ]);

    if (columnsResult.error) {
      return apiInternalError(request, columnsResult.error.message);
    }
    if (assigneesResult.error) {
      return apiInternalError(request, assigneesResult.error.message);
    }

    const columnNameById = new Map((columnsResult.data || []).map((column) => [column.id, column.name]));
    const assigneesByTaskId = new Map<string, Array<{ id: string; name: string; avatar?: string | null }>>();

    for (const row of assigneesResult.data || []) {
      const taskId = row.task_id as string;
      const userRelation = row.user as
        | { id: string; name: string; avatar?: string | null }
        | Array<{ id: string; name: string; avatar?: string | null }>
        | null;
      const userRow = Array.isArray(userRelation) ? userRelation[0] : userRelation;
      if (!userRow) {
        continue;
      }
      const current = assigneesByTaskId.get(taskId) || [];
      current.push(userRow);
      assigneesByTaskId.set(taskId, current);
    }

    const payload = (tasks || []).map((task) => ({
      ...task,
      columnName: task.column_id ? columnNameById.get(task.column_id) || "Unassigned" : "Unassigned",
      assignees: assigneesByTaskId.get(task.id) || [],
    }));

    return apiSuccess(request, payload);
  } catch (error) {
    console.error("Error fetching project request tasks:", error);
    return apiInternalError(request, "Failed to fetch project request tasks");
  }
}
