import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { getS3Credentials } from "@/lib/storage/get-s3-credentials";
import { createS3SignedGetUrl } from "@/lib/storage/s3";

export async function GET(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();

    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);

    const { data: document, error } = await supabase
      .from("documents")
      .select("storage_path, file_name, client_id")
      .eq("id", id)
      .is("deleted_at", null)
      .single();

    if (error || !document) {
      return apiNotFound(request, "Document not found");
    }

    const { data: share } = await supabase
      .from("document_shares")
      .select("id")
      .eq("document_id", id)
      .eq("shared_with_user_id", user.id)
      .maybeSingle();

    const hasAccess = canAccessClient(access, document.client_id) || Boolean(share);

    if (!hasAccess) {
      return apiForbidden(request, "Access denied");
    }

    const s3Credentials = await getS3Credentials(user.id);

    if (!s3Credentials) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message:
          "No S3 storage connection configured. Add a platform S3 connection in Storage settings.",
      });
    }

    const url = await createS3SignedGetUrl({
      credentials: s3Credentials,
      key: document.storage_path,
      expiresInSeconds: 3600,
    });

    if (new URL(request.url).searchParams.get("redirect") === "1") {
      return Response.redirect(url);
    }

    await supabase
      .from("document_shares")
      .update({
        last_accessed_at: new Date().toISOString(),
        access_count: supabase.rpc("increment", { row_id: id }),
      })
      .eq("document_id", id)
      .eq("shared_with_user_id", user.id);

    const payload = {
      url,
      fileName: document.file_name,
    };

    return apiSuccess(request, payload, { extra: payload });
  } catch (error) {
    console.error("Error generating download URL:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to generate download URL",
    );
  }
}
