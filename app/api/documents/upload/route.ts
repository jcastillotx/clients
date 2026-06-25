import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { hasPermission } from "@/lib/rbac/permissions";
import { uploadFile, generateFilePath, StorageBuckets } from "@/lib/storage/upload";

export async function POST(request: Request) {
  try {
    const supabase = await createClient();

    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const canCreate = await hasPermission("documents.create", {
      supabase,
      userId: user.id,
    });
    if (!canCreate) {
      return apiForbidden(request, "Permission denied");
    }

    const access = await resolveRouteAccess(supabase, user);

    const formData = await request.formData();
    const file = formData.get("file") as File;
    const clientId = formData.get("clientId") as string;
    const requestId = formData.get("requestId") as string | null;
    const name = formData.get("name") as string;
    const description = formData.get("description") as string | null;
    const tags = formData.get("tags") as string | null;

    if (!file || !clientId || !name) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Missing required fields",
      });
    }

    if (!canAccessClient(access, clientId)) {
      return apiForbidden(request, "Access denied");
    }

    const filePath = generateFilePath(clientId, "documents", file.name);

    const uploadResult = await uploadFile({
      bucket: StorageBuckets.DOCUMENTS,
      path: filePath,
      file,
    });

    if (uploadResult.error) {
      return apiInternalError(request, uploadResult.error);
    }

    const { data: document, error: dbError } = await supabase
      .from("documents")
      .insert({
        name,
        description,
        file_name: file.name,
        file_size: file.size,
        mime_type: file.type,
        storage_path: uploadResult.path,
        storage_url: uploadResult.publicUrl,
        client_id: clientId,
        request_id: requestId,
        uploaded_by: user.id,
        tags: tags ? JSON.parse(tags) : null,
      })
      .select()
      .single();

    if (dbError) {
      await supabase.storage.from(StorageBuckets.DOCUMENTS).remove([uploadResult.path]);
      return apiInternalError(request, dbError.message);
    }

    return apiSuccess(request, document, { status: 201, extra: { document } });
  } catch (error) {
    console.error("Error uploading document:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to upload document",
    );
  }
}
