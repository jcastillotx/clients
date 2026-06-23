import { createClient } from "@/lib/supabase/server";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { updateSupportTicketSchema } from "@/lib/validations/support-ticket";
import { isAdminUser } from "@/lib/rbac/check";
import { NextRequest } from "next/server";
import { revalidatePath } from "next/cache";
import { z } from "zod";

/**
 * GET /api/support/[id]
 */
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  const { data, error } = await supabase
    .from("support_tickets")
    .select(
      `
      *,
      client:clients(id, company_name, email),
      creator:users!support_tickets_created_by_fkey(id, name, email, avatar),
      assigned_user:users!support_tickets_assigned_to_fkey(id, name, email, avatar)
    `,
    )
    .eq("id", id)
    .is("deleted_at", null)
    .single();

  if (error) {
    console.error("Error fetching ticket:", error);
    return apiInternalError(req, error.message);
  }

  if (!data) {
    return apiNotFound(req, "Ticket not found");
  }

  return apiSuccess(req, data);
}

/**
 * PUT /api/support/[id]
 */
export async function PUT(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
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
    const validatedData = updateSupportTicketSchema.parse(body);

    const updateData: Record<string, unknown> = {
      updated_at: new Date().toISOString(),
    };
    if (validatedData.status !== undefined) updateData.status = validatedData.status;
    if (validatedData.priority !== undefined) updateData.priority = validatedData.priority;
    if (validatedData.subject !== undefined) updateData.subject = validatedData.subject;
    if (validatedData.description !== undefined) updateData.description = validatedData.description;
    if (validatedData.category !== undefined) updateData.category = validatedData.category;
    if (validatedData.estimatedHours !== undefined) updateData.estimated_hours = validatedData.estimatedHours;
    if (validatedData.actualHours !== undefined) updateData.actual_hours = validatedData.actualHours;
    if (validatedData.metadata !== undefined) updateData.metadata = validatedData.metadata;
    if ("assignedTo" in validatedData) {
      updateData.assigned_to = validatedData.assignedTo ?? null;
    }

    if (validatedData.status) {
      if (validatedData.status === "in_progress") {
        const { data: ticket } = await supabase
          .from("support_tickets")
          .select("first_response_at")
          .eq("id", id)
          .single();

        if (ticket && !ticket.first_response_at) {
          updateData.first_response_at = new Date().toISOString();
        }
      }

      if (validatedData.status === "resolved") {
        updateData.resolved_at = new Date().toISOString();
      }

      if (validatedData.status === "closed") {
        updateData.closed_at = new Date().toISOString();
      }

      if (validatedData.status === "waiting_on_client") {
        updateData.sla_paused = true;
      }
    }

    const { data, error } = await supabase
      .from("support_tickets")
      .update(updateData)
      .eq("id", id)
      .select(
        `
        *,
        client:clients(id, company_name, email),
        creator:users!support_tickets_created_by_fkey(id, name, email, avatar),
        assigned_user:users!support_tickets_assigned_to_fkey(id, name, email, avatar)
      `,
      )
      .single();

    if (error) {
      console.error("Error updating ticket:", error);
      return apiInternalError(req, error.message);
    }

    return apiSuccess(req, data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }

    console.error("Unexpected error:", error);
    return apiInternalError(req, "Internal server error");
  }
}

/**
 * DELETE /api/support/[id]
 */
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
    supabase.from("users").select("is_super_admin").eq("id", user.id).maybeSingle(),
    supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
  ]);

  if (!isAdminUser(user, dbUser, roleRows)) {
    return apiForbidden(req);
  }

  const { data: ticketRow } = await supabase
    .from("support_tickets")
    .select("metadata")
    .eq("id", id)
    .maybeSingle();

  const deletedAt = new Date().toISOString();
  const metadata = (ticketRow?.metadata as Record<string, unknown> | null) ?? {};
  const customFields = (metadata.customFields as Record<string, unknown> | undefined) ?? {};
  const linkedRequestId =
    typeof metadata.request_id === "string"
      ? metadata.request_id
      : typeof customFields.requestId === "string"
        ? customFields.requestId
        : typeof customFields.request_id === "string"
          ? customFields.request_id
          : null;

  const { error } = await supabase
    .from("support_tickets")
    .update({ deleted_at: deletedAt })
    .eq("id", id);

  if (error) {
    console.error("Error deleting ticket:", error);
    return apiInternalError(req, error.message);
  }

  if (linkedRequestId) {
    await supabase
      .from("requests")
      .update({ deleted_at: deletedAt })
      .eq("id", linkedRequestId)
      .is("deleted_at", null);
  }

  revalidatePath("/dashboard");
  revalidatePath("/support");

  return apiSuccess(req, { deleted: true });
}
