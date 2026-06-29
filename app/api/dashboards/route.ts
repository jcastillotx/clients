import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { apiError, apiInternalError, apiSuccess, apiUnauthorized } from "@/lib/api/response";

export async function GET(req: NextRequest) {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return apiUnauthorized(req);

  const { data: userData } = await supabase.from("users").select("client_id").eq("id", user.id).single();

  const { data: dashboards, error } = await supabase
    .from("custom_dashboards")
    .select("*")
    .or(`user_id.eq.${user.id},client_id.eq.${userData?.client_id ?? "00000000-0000-0000-0000-000000000000"}`)
    .order("created_at", { ascending: false });

  if (error) return apiInternalError(req, error.message);

  return apiSuccess(req, dashboards ?? [], { extra: { dashboards } });
}

export async function POST(req: NextRequest) {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return apiUnauthorized(req);

  const body = await req.json();
  const { name, layout, widgets } = body as {
    name?: string;
    layout?: Record<string, unknown>;
    widgets?: unknown[];
  };

  if (!name) {
    return apiError(req, { status: 400, code: "BAD_REQUEST", message: "Dashboard name is required" });
  }

  const { data: userData } = await supabase.from("users").select("client_id").eq("id", user.id).single();

  const { data: dashboard, error } = await supabase
    .from("custom_dashboards")
    .insert({
      user_id: user.id,
      client_id: userData?.client_id ?? null,
      dashboard_name: name,
      layout: layout ?? { type: "grid", columns: 12, gap: 4 },
      widgets: widgets ?? [],
      is_default: false,
    })
    .select()
    .single();

  if (error) return apiInternalError(req, error.message);

  return apiSuccess(req, dashboard, { extra: { dashboard } });
}
