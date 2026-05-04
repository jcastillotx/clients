import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { isAdminUser } from "@/lib/rbac/check";
import { bulkRequestsSchema } from "@/lib/validations/request";
import { z } from "zod";

function getClientIp(req: NextRequest): string | null {
  return req.headers.get("x-forwarded-for")?.split(",")[0]?.trim() ?? null;
}

/**
 * POST /api/requests/bulk
 *
 * Bulk close (mark completed) or soft-delete requests.
 * Delete requires admin; close follows same tenant rules as PATCH /api/requests/:id.
 */
export async function POST(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  let body: unknown;
  try {
    body = await req.json();
  } catch {
    return NextResponse.json({ error: "Invalid JSON" }, { status: 400 });
  }

  try {
    const validated = bulkRequestsSchema.parse(body);

    const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
      supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle(),
      supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
    ]);

    if (!dbUser) {
      return NextResponse.json({ error: "User profile not found" }, { status: 404 });
    }

    const isAdmin = isAdminUser(user, dbUser, roleRows);
    const metadataRole = String(user.user_metadata?.role || user.user_metadata?.app_role || "").toLowerCase();
    const isAdminByMetadata =
      user.user_metadata?.is_super_admin === true ||
      metadataRole === "admin" ||
      metadataRole === "super_admin";
    const canDelete = isAdmin || isAdminByMetadata;

    const { data: rows, error: fetchError } = await supabase
      .from("requests")
      .select("id, client_id")
      .in("id", validated.ids)
      .is("deleted_at", null);

    if (fetchError) {
      console.error("[POST /api/requests/bulk] fetch:", fetchError);
      return NextResponse.json({ error: "Failed to verify requests" }, { status: 500 });
    }

    if (!rows || rows.length !== validated.ids.length) {
      return NextResponse.json(
        { error: "One or more requests were not found or are unavailable" },
        { status: 404 },
      );
    }

    for (const row of rows) {
      const sameClient = dbUser.client_id && row.client_id === dbUser.client_id;
      if (!isAdmin && !sameClient) {
        return NextResponse.json({ error: "Forbidden" }, { status: 403 });
      }
    }

    if (validated.action === "delete" && !canDelete) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const now = new Date().toISOString();

    if (validated.action === "delete") {
      const { error } = await supabase
        .from("requests")
        .update({ deleted_at: now })
        .in("id", validated.ids);

      if (error) {
        console.error("[POST /api/requests/bulk] delete:", error);
        return NextResponse.json({ error: "Failed to delete requests" }, { status: 500 });
      }

      await supabase.from("activity_logs").insert({
        user_id: user.id,
        action: "request.bulk_deleted",
        resource_type: "request",
        resource_id: validated.ids[0],
        metadata: {
          ids: validated.ids,
          count: validated.ids.length,
          ip: getClientIp(req),
        },
      });

      return NextResponse.json({ success: true, affected: validated.ids.length });
    }

    const { error } = await supabase
      .from("requests")
      .update({ status: "completed", updated_at: now })
      .in("id", validated.ids);

    if (error) {
      console.error("[POST /api/requests/bulk] close:", error);
      return NextResponse.json({ error: "Failed to update requests" }, { status: 500 });
    }

    await supabase.from("activity_logs").insert({
      user_id: user.id,
      action: "request.bulk_closed",
      resource_type: "request",
      resource_id: validated.ids[0],
      metadata: {
        ids: validated.ids,
        count: validated.ids.length,
        new_status: "completed",
        ip: getClientIp(req),
      },
    });

    return NextResponse.json({ success: true, affected: validated.ids.length });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }
    console.error("[POST /api/requests/bulk]", error);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
