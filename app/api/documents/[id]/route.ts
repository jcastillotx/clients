import { createClient } from "@/lib/supabase/server";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";

async function ensureDocumentAccess(request: Request, id: string) {
  const supabase = await createClient();
  const {
    data: { user },
    error: authError,
  } = await supabase.auth.getUser();

  if (authError || !user) {
    return { error: apiUnauthorized(request) };
  }

  const access = await resolveRouteAccess(supabase, user);
  const { data: document, error } = await supabase
    .from("documents")
    .select("id, client_id, storage_path")
    .eq("id", id)
    .is("deleted_at", null)
    .single();

  if (error || !document) {
    return { error: apiNotFound(request, "Document not found") };
  }

  if (!canAccessClient(access, document.client_id)) {
    return { error: apiForbidden(request) };
  }

  return { supabase, document };
}

export async function GET(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await ensureDocumentAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const { supabase } = guard;

    const { data: document, error } = await supabase
      .from("documents")
      .select(
        `
        *,
        client:clients(id, company_name),
        uploader:users!uploaded_by(id, name, email),
        request:requests(id, title)
      `,
      )
      .eq("id", id)
      .is("deleted_at", null)
      .single();

    if (error) throw error;

    if (!document) {
      return apiNotFound(request, "Document not found");
    }

    return apiSuccess(request, document, { extra: { document } });
  } catch (error) {
    console.error("Error fetching document:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch document",
    );
  }
}

export async function PATCH(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await ensureDocumentAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();
    const { name, description, tags, isPublic } = body;

    const { supabase } = guard;

    const updateData: {
      updated_at: string;
      name?: unknown;
      description?: unknown;
      tags?: unknown;
      is_public?: unknown;
    } = {
      updated_at: new Date().toISOString(),
    };

    if (name !== undefined) updateData.name = name;
    if (description !== undefined) updateData.description = description;
    if (tags !== undefined) updateData.tags = tags;
    if (isPublic !== undefined) updateData.is_public = isPublic;

    const { data: document, error } = await supabase
      .from("documents")
      .update(updateData)
      .eq("id", id)
      .is("deleted_at", null)
      .select()
      .single();

    if (error) throw error;

    if (!document) {
      return apiNotFound(request, "Document not found");
    }

    return apiSuccess(request, document, { extra: { document } });
  } catch (error) {
    console.error("Error updating document:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to update document",
    );
  }
}

export async function DELETE(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await ensureDocumentAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const { supabase } = guard;

    const { error: dbError } = await supabase
      .from("documents")
      .update({ deleted_at: new Date().toISOString() })
      .eq("id", id);

    if (dbError) throw dbError;

    return apiSuccess(request, { deleted: true });
  } catch (error) {
    console.error("Error deleting document:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to delete document",
    );
  }
}
