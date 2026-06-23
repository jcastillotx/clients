import { createClient } from "@/lib/supabase/server";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { getSignedUrl, StorageBuckets } from "@/lib/storage/upload";

export async function GET(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();

    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    const { data: document, error } = await supabase
      .from("documents")
      .select("storage_path, file_name, client_id")
      .eq("id", id)
      .is("deleted_at", null)
      .single();

    if (error || !document) {
      return apiNotFound(request, "Document not found");
    }

    const { data: access } = await supabase.from("users").select("client_id").eq("id", user.id).single();

    const hasAccess =
      access?.client_id === document.client_id ||
      (
        await supabase
          .from("staff_assignments")
          .select("id")
          .eq("staff_user_id", user.id)
          .eq("client_id", document.client_id)
          .single()
      ).data ||
      (
        await supabase
          .from("document_shares")
          .select("id")
          .eq("document_id", id)
          .eq("shared_with_user_id", user.id)
          .single()
      ).data;

    if (!hasAccess) {
      return apiForbidden(request, "Access denied");
    }

    const { url, error: urlError } = await getSignedUrl(StorageBuckets.DOCUMENTS, document.storage_path, 3600);

    if (urlError || !url) {
      return apiInternalError(request, "Failed to generate download URL");
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
