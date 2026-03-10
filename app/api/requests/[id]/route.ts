import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { updateRequestSchema } from "@/lib/validations/request";
import { isAdminUser } from "@/lib/rbac/check";
import { z } from "zod";

/** Best-effort client IP for audit logs (Vercel sets x-forwarded-for). */
function getClientIp(req: NextRequest): string | null {
  return req.headers.get("x-forwarded-for")?.split(",")[0]?.trim() ?? null;
}

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const body = await req.json();

  try {
    const validated = updateRequestSchema.parse(body);

    const [{ data: request }, { data: dbUser }, { data: roleRows }] = await Promise.all([
      supabase.from("requests").select("id, client_id, custom_fields, status, assigned_to").eq("id", id).single(),
      supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle(),
      supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
    ]);

    if (!request) {
      return NextResponse.json({ error: "Not found" }, { status: 404 });
    }
    if (!dbUser) {
      return NextResponse.json({ error: "User profile not found" }, { status: 404 });
    }

    const isAdmin = isAdminUser(user, dbUser, roleRows);
    const isSameClient = dbUser.client_id && request.client_id === dbUser.client_id;

    if (!isAdmin && !isSameClient) {
      // Log unauthorized attempt before returning
      console.warn("[PATCH /api/requests/:id] Forbidden attempt", {
        userId: user.id,
        requestId: id,
        ip: getClientIp(req),
      });
      await supabase.from("activity_logs").insert({
        user_id: user.id,
        action: "request.update_denied",
        resource_type: "request",
        resource_id: id,
        metadata: { reason: "client_mismatch", ip: getClientIp(req) },
      });
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const updatePayload: Record<string, unknown> = {};
    if (validated.title !== undefined) updatePayload.title = validated.title;
    if (validated.description !== undefined) updatePayload.description = validated.description;
    if (validated.priority !== undefined) updatePayload.priority = validated.priority;
    if (validated.status !== undefined) updatePayload.status = validated.status;
    if (validated.dueDate !== undefined) updatePayload.due_date = validated.dueDate || null;

    const currentCustomFields = (request as Record<string, unknown>).custom_fields as Record<string, unknown> || {};
    if (validated.type !== undefined || validated.customFields !== undefined) {
      updatePayload.custom_fields = {
        ...currentCustomFields,
        ...(validated.customFields || {}),
        ...(validated.type ? { type: validated.type } : {}),
      };
    }

    if (validated.assignedTo !== undefined) {
      if (!isAdmin) {
        console.warn("[PATCH /api/requests/:id] Non-admin assignee change attempt", {
          userId: user.id,
          requestId: id,
          ip: getClientIp(req),
        });
        await supabase.from("activity_logs").insert({
          user_id: user.id,
          action: "request.update_denied",
          resource_type: "request",
          resource_id: id,
          metadata: { reason: "assignee_change_not_admin", ip: getClientIp(req) },
        });
        return NextResponse.json({ error: "Forbidden" }, { status: 403 });
      }
      updatePayload.assigned_to = validated.assignedTo || null;
    }

    if (Object.keys(updatePayload).length === 0) {
      return NextResponse.json({ error: "No fields to update" }, { status: 400 });
    }

    const { data, error } = await supabase
      .from("requests")
      .update(updatePayload)
      .eq("id", id)
      .select("*, client:clients(company_name), assigned_user:users!requests_assigned_to_fkey(name, avatar)")
      .single();

    if (error) {
      console.error("[PATCH /api/requests/:id] DB error:", error);
      return NextResponse.json({ error: "Failed to update request" }, { status: 500 });
    }

    // Audit: record successful update with before/after for key fields
    await supabase.from("activity_logs").insert({
      user_id: user.id,
      action: "request.updated",
      resource_type: "request",
      resource_id: id,
      metadata: {
        changed_fields: Object.keys(updatePayload),
        ip: getClientIp(req),
        ...(validated.status !== undefined
          ? { old_status: request.status, new_status: validated.status }
          : {}),
        ...(validated.assignedTo !== undefined
          ? { old_assigned_to: request.assigned_to, new_assigned_to: validated.assignedTo }
          : {}),
      },
    });

    return NextResponse.json(data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }
    console.error("[PATCH /api/requests/:id] Unexpected error:", error);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}

export async function DELETE(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
    supabase.from("users").select("id, is_super_admin").eq("id", user.id).maybeSingle(),
    supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
  ]);

  if (!isAdminUser(user, dbUser, roleRows)) {
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });
  }

  const { error } = await supabase
    .from("requests")
    .update({ deleted_at: new Date().toISOString() })
    .eq("id", id);

  if (error) {
    console.error("[DELETE /api/requests/:id]", error);
    return NextResponse.json({ error: "Failed to delete request" }, { status: 500 });
  }

  void supabase.from("activity_logs").insert({
    user_id: user.id,
    action: "request.deleted",
    resource_type: "request",
    resource_id: id,
    metadata: { ip: req.headers.get("x-forwarded-for")?.split(",")[0]?.trim() ?? null },
  });

  return NextResponse.json({ success: true });
}
