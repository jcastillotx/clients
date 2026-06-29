import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { collectRoleNames } from "@/lib/rbac/role-row-utils";
import { z } from "zod";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

const createRuleSchema = z.object({
  name: z.string().min(1, "Name is required"),
  description: z.string().optional().nullable(),
  trigger: z.string().min(1, "Trigger is required"),
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
    .min(1, "At least one action is required"),
  isActive: z.boolean().optional().default(true),
});

export async function GET(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  try {
    const { data: roleRows } = await supabase
      .from("user_roles")
      .select("role:roles(name)")
      .eq("user_id", user.id);

    const roleNames = collectRoleNames(roleRows);
    const isStaff =
      roleNames.has("admin") ||
      roleNames.has("super_admin") ||
      roleNames.has("account_manager") ||
      roleNames.has("staff");

    if (!isStaff) {
      const { data: userData } = await supabase
        .from("users")
        .select("is_super_admin")
        .eq("id", user.id)
        .single();
      if (!userData?.is_super_admin) {
        return apiForbidden(req);
      }
    }

    const { data, error } = await supabase
      .from("automation_rules")
      .select("*")
      .order("sort_order", { ascending: true })
      .order("created_at", { ascending: false });

    if (error) throw error;

    const rows = data ?? [];
    return apiSuccess(req, rows, { extra: { rules: rows } });
  } catch (error) {
    console.error("Error fetching automation rules:", error);
    return apiInternalError(req, "Failed to fetch automation rules");
  }
}

export async function POST(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  try {
    const body = await req.json();
    const validatedData = createRuleSchema.parse(body);

    const { data: roleRows } = await supabase
      .from("user_roles")
      .select("role:roles(name)")
      .eq("user_id", user.id);

    const roleNames = collectRoleNames(roleRows);
    const isStaff =
      roleNames.has("admin") ||
      roleNames.has("super_admin") ||
      roleNames.has("account_manager") ||
      roleNames.has("staff");

    if (!isStaff) {
      const { data: userData } = await supabase
        .from("users")
        .select("is_super_admin")
        .eq("id", user.id)
        .single();
      if (!userData?.is_super_admin) {
        return apiForbidden(req);
      }
    }

    const { data, error } = await supabase
      .from("automation_rules")
      .insert({
        name: validatedData.name,
        description: validatedData.description ?? null,
        trigger: validatedData.trigger,
        conditions: validatedData.conditions ?? null,
        actions: validatedData.actions,
        is_active: validatedData.isActive,
        created_by: user.id,
        updated_by: user.id,
      })
      .select()
      .single();

    if (error) throw error;

    return apiSuccess(req, data, { status: 201, extra: { rule: data } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }
    console.error("Error creating automation rule:", error);
    return apiInternalError(req, "Failed to create automation rule");
  }
}
