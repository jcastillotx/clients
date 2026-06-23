import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { uploadFile, generateFilePath, StorageBuckets } from "@/lib/storage/upload";

export async function POST(request: Request) {
  try {
    const supabase = await createClient();

    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

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

    const { data: userClient } = await supabase.from("users").select("client_id").eq("id", user.id).single();

    const hasAccess =
      userClient?.client_id === clientId ||
      (
        await supabase
          .from("staff_assignments")
          .select("id")
          .eq("staff_user_id", user.id)
          .eq("client_id", clientId)
          .single()
      ).data;

    if (!hasAccess) {
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
