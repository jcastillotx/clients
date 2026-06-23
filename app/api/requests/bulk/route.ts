import { NextRequest } from "next/server";
import { revalidatePath } from "next/cache";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { createClient } from "@/lib/supabase/server";
import { isAdminUser } from "@/lib/rbac/check";
import { bulkRequestsSchema } from "@/lib/validations/request";
import { z } from "zod";

function getClientIp(request: NextRequest): string | null {
  return request.headers.get("x-forwarded-for")?.split(",")[0]?.trim() ?? null;
}

export async function POST(request: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(request);
  }

  let body: unknown;
  try {
    body = await request.json();
  } catch {
    return apiError(request, { status: 400, code: "BAD_REQUEST", message: "Invalid JSON" });
  }

  try {
    const validated = bulkRequestsSchema.parse(body);

    const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
      supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle(),
      supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
    ]);

    if (!dbUser) {
      return apiNotFound(request, "User profile not found");
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
      return apiInternalError(request, "Failed to verify requests");
    }

    if (!rows || rows.length !== validated.ids.length) {
      return apiNotFound(request, "One or more requests were not found or are unavailable");
    }

    for (const row of rows) {
      const sameClient = dbUser.client_id && row.client_id === dbUser.client_id;
      if (!isAdmin && !sameClient) {
        return apiForbidden(request);
      }
    }

    if (validated.action === "delete" && !canDelete) {
      return apiForbidden(request);
    }

    const now = new Date().toISOString();

    if (validated.action === "delete") {
      const { error } = await supabase
        .from("requests")
        .update({ deleted_at: now })
        .in("id", validated.ids);

      if (error) {
        console.error("[POST /api/requests/bulk] delete:", error);
        return apiInternalError(request, "Failed to delete requests");
      }

      revalidatePath("/dashboard");
      revalidatePath("/requests");

      await supabase.from("activity_logs").insert({
        user_id: user.id,
        action: "request.bulk_deleted",
        resource_type: "request",
        resource_id: validated.ids[0],
        metadata: {
          ids: validated.ids,
          count: validated.ids.length,
          ip: getClientIp(request),
        },
      });

      return apiSuccess(request, { affected: validated.ids.length }, { extra: { success: true, affected: validated.ids.length } });
    }

    const { error } = await supabase
      .from("requests")
      .update({ status: "completed", updated_at: now })
      .in("id", validated.ids);

    if (error) {
      console.error("[POST /api/requests/bulk] close:", error);
      return apiInternalError(request, "Failed to update requests");
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
        ip: getClientIp(request),
      },
    });

    return apiSuccess(request, { affected: validated.ids.length }, { extra: { success: true, affected: validated.ids.length } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("[POST /api/requests/bulk]", error);
    return apiInternalError(request, "Internal server error");
  }
}
