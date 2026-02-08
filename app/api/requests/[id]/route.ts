import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { updateRequestSchema } from "@/lib/validations/request";
import { z } from "zod";

function isAdminUser(user: any, dbUser: any, roleRows: any[] | null | undefined) {
  const metadataRole = String(user?.user_metadata?.role || user?.user_metadata?.app_role || "").toLowerCase();
  if (dbUser?.is_super_admin || user?.user_metadata?.is_super_admin === true) return true;
  if (metadataRole === "admin" || metadataRole === "super_admin") return true;
  return (roleRows || []).some((row: any) => {
    const roleName = String(row?.role?.name || row?.role?.[0]?.name || "").toLowerCase();
    return roleName === "admin" || roleName === "super_admin";
  });
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
      supabase.from("requests").select("id, client_id, custom_fields").eq("id", id).single(),
      supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle(),
      supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
    ]);

    if (!request) {
      return NextResponse.json({ error: "Request not found" }, { status: 404 });
    }
    if (!dbUser) {
      return NextResponse.json({ error: "User profile not found" }, { status: 404 });
    }

    const isAdmin = isAdminUser(user, dbUser, roleRows);
    const isSameClient = dbUser.client_id && request.client_id === dbUser.client_id;
    if (!isAdmin && !isSameClient) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const updatePayload: Record<string, any> = {};
    if (validated.title !== undefined) updatePayload.title = validated.title;
    if (validated.description !== undefined) updatePayload.description = validated.description;
    if (validated.priority !== undefined) updatePayload.priority = validated.priority;
    if (validated.status !== undefined) updatePayload.status = validated.status;
    if (validated.dueDate !== undefined) updatePayload.due_date = validated.dueDate || null;

    const currentCustomFields = (request as any).custom_fields || {};
    if (validated.type !== undefined || validated.customFields !== undefined) {
      updatePayload.custom_fields = {
        ...currentCustomFields,
        ...(validated.customFields || {}),
        ...(validated.type ? { type: validated.type } : {}),
      };
    }

    if (validated.assignedTo !== undefined) {
      if (!isAdmin) {
        return NextResponse.json({ error: "Only admins can change assignee" }, { status: 403 });
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
      .select("*, client:clients(company_name), assigned_user:users(name, avatar)")
      .single();

    if (error) {
      return NextResponse.json({ error: error.message }, { status: 500 });
    }

    return NextResponse.json(data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
