import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { collectRoleNames } from "@/lib/rbac/role-row-utils";
import { z } from "zod";
import { createContentSchema } from "@/lib/validations/marketing";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

export async function GET(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  try {
    const { data: userData } = await supabase
      .from("users")
      .select("client_id, is_super_admin")
      .eq("id", user.id)
      .single();

    const { data: roleRows } = await supabase
      .from("user_roles")
      .select("role:roles(name)")
      .eq("user_id", user.id);

    const roleNames = collectRoleNames(roleRows);

    const isAdmin = Boolean(userData?.is_super_admin) || roleNames.has("admin") || roleNames.has("super_admin");
    const isAccountManager = roleNames.has("account_manager");
    const isStaff = isAdmin || isAccountManager || roleNames.has("staff");

    if (!isStaff) {
      return apiForbidden(req);
    }

    const clientIdFilter = req.nextUrl.searchParams.get("client_id");

    let query = supabase
      .from("content_calendar_items")
      .select(`
        *,
        client:clients(id, company_name),
        creator:users!content_calendar_items_created_by_fkey(id, name)
      `)
      .order("scheduled_for", { ascending: true });

    if (clientIdFilter) {
      query = query.eq("client_id", clientIdFilter);
    } else if (!isAdmin) {
      query = query.eq("client_id", userData!.client_id!);
    }

    const { data, error } = await query;

    if (error) throw error;

    const rows = data ?? [];
    return apiSuccess(req, rows, { extra: { items: rows } });
  } catch (error) {
    console.error("Error fetching content calendar:", error);
    return apiInternalError(req, "Failed to fetch content calendar");
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
    const validatedData = createContentSchema.parse(body);

    const { data: userData } = await supabase.from("users").select("client_id").eq("id", user.id).single();

    if (!userData?.client_id) {
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "User not associated with a client",
      });
    }

    const { data, error } = await supabase
      .from("content_calendar_items")
      .insert({
        client_id: userData.client_id,
        title: validatedData.title,
        content: validatedData.content,
        content_type: validatedData.content_type,
        platform: validatedData.platform,
        status: validatedData.status,
        scheduled_for: validatedData.scheduled_for || null,
        created_by: user.id,
      })
      .select()
      .single();

    if (error) throw error;

    return apiSuccess(req, data, { status: 201, extra: { item: data } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }
    console.error("Error creating content:", error);
    return apiInternalError(req, "Failed to create content");
  }
}
