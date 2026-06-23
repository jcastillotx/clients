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
      .from("campaigns")
      .select(`
        *,
        client:clients(id, company_name),
        created_by_user:users!campaigns_created_by_fkey(id, name)
      `)
      .order("created_at", { ascending: false });

    if (clientIdFilter) {
      query = query.eq("client_id", clientIdFilter);
    } else if (!isAdmin) {
      query = query.eq("client_id", userData!.client_id!);
    }

    const { data, error } = await query;

    if (error) throw error;

    const rows = data ?? [];
    return apiSuccess(req, rows, { extra: { campaigns: rows } });
  } catch (error) {
    console.error("Error fetching campaigns:", error);
    return apiInternalError(req, "Failed to fetch campaigns");
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

    const { createCampaignSchema } = await import("@/lib/validations/marketing");
    const validatedData = createCampaignSchema.parse(body);

    const { data: userData } = await supabase.from("users").select("client_id").eq("id", user.id).single();

    if (!userData?.client_id) {
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "User not associated with a client",
      });
    }

    const { data, error } = await supabase
      .from("campaigns")
      .insert({
        client_id: userData.client_id,
        name: validatedData.name,
        description: validatedData.description || null,
        campaign_type: validatedData.type,
        status: validatedData.status,
        start_date: validatedData.start_date || null,
        end_date: validatedData.end_date || null,
        budget: validatedData.budget || null,
        currency: validatedData.currency,
        created_by: user.id,
        metadata: validatedData.metadata || null,
      })
      .select()
      .single();

    if (error) throw error;

    return apiSuccess(req, data, { status: 201, extra: { campaign: data } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }
    console.error("Error creating campaign:", error);
    return apiInternalError(req, "Failed to create campaign");
  }
}
