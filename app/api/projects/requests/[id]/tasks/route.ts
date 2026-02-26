import { NextRequest, NextResponse } from "next/server";
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
export async function GET(_request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const access = await resolveAccess(supabase, user);

    const { data: requestRow, error: requestError } = await supabase
      .from("requests")
      .select("id, client_id")
      .eq("id", id)
      .contains("custom_fields", { type: "project" })
      .single();

    if (requestError || !requestRow) {
      return NextResponse.json({ error: "Project request not found" }, { status: 404 });
    }

    if (!access.isAdmin && access.clientId !== requestRow.client_id) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const { data: tasks, error: tasksError } = await supabase
      .from("staff_tasks")
      .select("id, title, description, priority, due_date, progress, completed_at, column_id, created_at, updated_at")
      .eq("request_id", id)
      .order("created_at", { ascending: false });

    if (tasksError) {
      return NextResponse.json({ error: tasksError.message }, { status: 500 });
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
      return NextResponse.json({ error: columnsResult.error.message }, { status: 500 });
    }
    if (assigneesResult.error) {
      return NextResponse.json({ error: assigneesResult.error.message }, { status: 500 });
    }

    const columnNameById = new Map((columnsResult.data || []).map((column) => [column.id, column.name]));
    const assigneesByTaskId = new Map<string, Array<{ id: string; name: string; avatar?: string | null }>>();

    for (const row of assigneesResult.data || []) {
      const taskId = row.task_id as string;
      const userRow = row.user as { id: string; name: string; avatar?: string | null } | null;
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

    return NextResponse.json({ data: payload });
  } catch (error) {
    console.error("Error fetching project request tasks:", error);
    return NextResponse.json({ error: "Failed to fetch project request tasks" }, { status: 500 });
  }
}
