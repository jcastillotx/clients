import {
  createAdminClientIfAvailable,
  createClient,
} from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { hasPermission } from "@/lib/rbac/permissions";
import { getS3Credentials } from "@/lib/storage/get-s3-credentials";
import { deleteS3Object, putS3Object } from "@/lib/storage/s3";
import { generateFilePath } from "@/lib/storage/upload";

const MAX_DOCUMENT_UPLOAD_BYTES = 100 * 1024 * 1024;

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

    const access = await resolveRouteAccess(supabase, user);
    const canCreate =
      access.isStaff ||
      (await hasPermission("documents.create", {
        supabase,
        userId: user.id,
      }));
    if (!canCreate) {
      return apiForbidden(request, "Permission denied");
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

    if (file.size > MAX_DOCUMENT_UPLOAD_BYTES) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Files must be 100MB or less",
      });
    }

    if (!canAccessClient(access, clientId)) {
      return apiForbidden(request, "Access denied");
    }

    const filePath = generateFilePath(clientId, "documents", file.name);
    const s3Credentials = await getS3Credentials(user.id);

    if (!s3Credentials) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message:
          "No S3 storage connection configured. Add a platform S3 connection in Storage settings.",
      });
    }

    const uploadResult = await putS3Object({
      credentials: s3Credentials,
      key: filePath,
      body: await file.arrayBuffer(),
      contentType: file.type || "application/octet-stream",
    });

    if (uploadResult.error) {
      return apiInternalError(request, uploadResult.error);
    }

    const dbClient = access.isStaff
      ? (createAdminClientIfAvailable() ?? supabase)
      : supabase;
    const { data: document, error: dbError } = await dbClient
      .from("documents")
      .insert({
        name,
        description,
        file_name: file.name,
        file_size: file.size,
        mime_type: file.type,
        storage_path: uploadResult.key,
        storage_url: null,
        client_id: clientId,
        request_id: requestId,
        uploaded_by: user.id,
        tags: tags ? JSON.parse(tags) : null,
      })
      .select()
      .single();

    if (dbError) {
      await deleteS3Object({
        credentials: s3Credentials,
        key: uploadResult.key,
      });
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
