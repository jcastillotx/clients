import { NextRequest } from "next/server";
import { revalidatePath } from "next/cache";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
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
    return apiUnauthorized(req);
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
      return apiNotFound(req, "Not found");
    }
    if (!dbUser) {
      return apiNotFound(req, "User profile not found");
    }

    const isAdmin = isAdminUser(user, dbUser, roleRows);
    const isSameClient = dbUser.client_id && request.client_id === dbUser.client_id;

    if (!isAdmin && !isSameClient) {
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
      return apiForbidden(req);
    }

    const updatePayload: Record<string, unknown> = {};
    if (validated.title !== undefined) updatePayload.title = validated.title;
    if (validated.description !== undefined) updatePayload.description = validated.description;
    if (validated.priority !== undefined) updatePayload.priority = validated.priority;
    if (validated.status !== undefined) updatePayload.status = validated.status;
    if (validated.dueDate !== undefined) updatePayload.due_date = validated.dueDate || null;

    const currentCustomFields =
      ((request as Record<string, unknown>).custom_fields as Record<string, unknown>) || {};
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
        return apiForbidden(req);
      }
      updatePayload.assigned_to = validated.assignedTo || null;
    }

    if (Object.keys(updatePayload).length === 0) {
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "No fields to update",
      });
    }

    const { data, error } = await supabase
      .from("requests")
      .update(updatePayload)
      .eq("id", id)
      .select("*, client:clients(company_name), assigned_user:users!requests_assigned_to_fkey(name, avatar)")
      .single();

    if (error) {
      console.error("[PATCH /api/requests/:id] DB error:", error);
      return apiInternalError(req, "Failed to update request");
    }

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

    return apiSuccess(req, data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }
    console.error("[PATCH /api/requests/:id] Unexpected error:", error);
    return apiInternalError(req, "Internal server error");
  }
}

export async function DELETE(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) {
    return apiUnauthorized(req);
  }

  const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
    supabase.from("users").select("id, is_super_admin").eq("id", user.id).maybeSingle(),
    supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
  ]);

  const metadataRole = String(user.user_metadata?.role || user.user_metadata?.app_role || "").toLowerCase();
  const isAdminByMetadata =
    user.user_metadata?.is_super_admin === true ||
    metadataRole === "admin" ||
    metadataRole === "super_admin";

  if (!isAdminUser(user, dbUser, roleRows) && !isAdminByMetadata) {
    return apiForbidden(req);
  }

  const { data: requestRow } = await supabase
    .from("requests")
    .select("custom_fields")
    .eq("id", id)
    .maybeSingle();

  const deletedAt = new Date().toISOString();
  const customFields = (requestRow?.custom_fields as Record<string, unknown> | null) ?? {};
  const linkedTicketId =
    typeof customFields.support_ticket_id === "string"
      ? customFields.support_ticket_id
      : typeof customFields.supportTicketId === "string"
        ? customFields.supportTicketId
        : null;

  const { error } = await supabase
    .from("requests")
    .update({ deleted_at: deletedAt })
    .eq("id", id);

  if (error) {
    console.error("[DELETE /api/requests/:id]", error);
    return apiInternalError(req, "Failed to delete request");
  }

  if (linkedTicketId) {
    await supabase
      .from("support_tickets")
      .update({ deleted_at: deletedAt })
      .eq("id", linkedTicketId)
      .is("deleted_at", null);
  }

  revalidatePath("/dashboard");
  revalidatePath("/requests");

  void supabase.from("activity_logs").insert({
    user_id: user.id,
    action: "request.deleted",
    resource_type: "request",
    resource_id: id,
    metadata: { ip: req.headers.get("x-forwarded-for")?.split(",")[0]?.trim() ?? null },
  });

  return apiSuccess(req, { deleted: true });
}
