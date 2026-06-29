import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { collectRoleNames } from "@/lib/rbac/role-row-utils";
import { z } from "zod";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

const updateRuleSchema = z.object({
  name: z.string().min(1).optional(),
  description: z.string().optional().nullable(),
  trigger: z.string().min(1).optional(),
  conditions: z
    .object({
      operator: z.enum(["and", "or"]).optional(),
      rules: z
        .array(
          z.object({
            field: z.string(),
            operator: z.enum(["equals", "not_equals", "contains", "greater_than", "less_than"]),
            value: z.any(),
          }),
        )
        .optional(),
    })
    .optional()
    .nullable(),
  actions: z
    .array(
      z.object({
        type: z.enum(["send_email", "create_task", "update_status", "send_notification", "webhook"]),
        config: z.record(z.any()),
      }),
    )
    .optional(),
  isActive: z.boolean().optional(),
});

async function assertStaff(supabase: Awaited<ReturnType<typeof createClient>>, userId: string) {
  const { data: roleRows } = await supabase
    .from("user_roles")
    .select("role:roles(name)")
    .eq("user_id", userId);

  const roleNames = collectRoleNames(roleRows);
  const isStaff =
    roleNames.has("admin") ||
    roleNames.has("super_admin") ||
    roleNames.has("account_manager") ||
    roleNames.has("staff");

  if (isStaff) return true;

  const { data: userData } = await supabase
    .from("users")
    .select("is_super_admin")
    .eq("id", userId)
    .single();

  return Boolean(userData?.is_super_admin);
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const supabase = await createClient();
  const { id } = await params;

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return apiUnauthorized(req);

  try {
    const authorized = await assertStaff(supabase, user.id);
    if (!authorized) return apiForbidden(req);

    const { data, error } = await supabase.from("automation_rules").select("*").eq("id", id).single();

    if (error || !data) return apiNotFound(req, "Automation rule not found");

    return apiSuccess(req, data, { extra: { rule: data } });
  } catch (error) {
    console.error("Error fetching automation rule:", error);
    return apiInternalError(req, "Failed to fetch automation rule");
  }
}

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const supabase = await createClient();
  const { id } = await params;

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return apiUnauthorized(req);

  try {
    const authorized = await assertStaff(supabase, user.id);
    if (!authorized) return apiForbidden(req);

    const body = await req.json();
    const validatedData = updateRuleSchema.parse(body);

    const updatePayload: Record<string, unknown> = {
      updated_by: user.id,
      updated_at: new Date().toISOString(),
    };

    if (validatedData.name !== undefined) updatePayload.name = validatedData.name;
    if (validatedData.description !== undefined) updatePayload.description = validatedData.description;
    if (validatedData.trigger !== undefined) updatePayload.trigger = validatedData.trigger;
    if (validatedData.conditions !== undefined) updatePayload.conditions = validatedData.conditions;
    if (validatedData.actions !== undefined) updatePayload.actions = validatedData.actions;
    if (validatedData.isActive !== undefined) updatePayload.is_active = validatedData.isActive;

    const { data, error } = await supabase
      .from("automation_rules")
      .update(updatePayload)
      .eq("id", id)
      .select()
      .single();

    if (error || !data) return apiNotFound(req, "Automation rule not found");

    return apiSuccess(req, data, { extra: { rule: data } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }
    console.error("Error updating automation rule:", error);
    return apiInternalError(req, "Failed to update automation rule");
  }
}

export async function DELETE(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const supabase = await createClient();
  const { id } = await params;

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return apiUnauthorized(req);

  try {
    const authorized = await assertStaff(supabase, user.id);
    if (!authorized) return apiForbidden(req);

    const { error } = await supabase.from("automation_rules").delete().eq("id", id);

    if (error) throw error;

    return apiSuccess(req, null, { extra: { deleted: true } });
  } catch (error) {
    console.error("Error deleting automation rule:", error);
    return apiInternalError(req, "Failed to delete automation rule");
  }
}
