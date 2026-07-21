import {
  buildPaginationMeta,
  parsePaginationSearchParams,
} from "@/lib/api/pagination";
import { apiError, apiForbidden, apiSuccess } from "@/lib/api/response";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import {
  createAdminClientIfAvailable,
  createClient,
} from "@/lib/supabase/server";

export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const clientId = searchParams.get("clientId");
    const requestId = searchParams.get("requestId");
    const pagination = parsePaginationSearchParams(searchParams);

    const supabase = await createClient();

    // Get authenticated user
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiError(request, {
        status: 401,
        code: "UNAUTHORIZED",
        message: "Unauthorized",
      });
    }

    const access = await resolveRouteAccess(supabase, user);
    const adminClient = access.isStaff ? createAdminClientIfAvailable() : null;
    const dbClient = adminClient ?? supabase;

    if (clientId && !canAccessClient(access, clientId)) {
      return apiForbidden(request, "Access denied");
    }

    // Build query
    let query = dbClient
      .from("documents")
      .select(
        `
        *,
        client:clients(id, company_name),
        uploader:users!uploaded_by(id, name, email)
      `,
        { count: "exact" },
      )
      .is("deleted_at", null)
      .eq("is_latest_version", true)
      .order("created_at", { ascending: false })
      .range(pagination.offset, pagination.offset + pagination.limit - 1);

    // Filter by client if provided
    if (clientId) {
      query = query.eq("client_id", clientId);
    } else if (!access.isStaff && access.clientId) {
      query = query.eq("client_id", access.clientId);
    }

    // Filter by request if provided
    if (requestId) {
      query = query.eq("request_id", requestId);
    }

    const { data: documents, error, count } = await query;

    if (error) throw error;

    const rows = documents || [];

    return apiSuccess(request, rows, {
      extra: { documents: rows },
      pagination: buildPaginationMeta(pagination, count, rows.length),
    });
  } catch (error) {
    console.error("Error fetching documents:", error);
    return apiError(request, {
      status: 500,
      code: "INTERNAL_ERROR",
      message:
        error instanceof Error ? error.message : "Failed to fetch documents",
    });
  }
}
