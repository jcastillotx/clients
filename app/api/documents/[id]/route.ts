import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";

async function ensureDocumentAccess(id: string) {
  const supabase = await createClient();
  const {
    data: { user },
    error: authError,
  } = await supabase.auth.getUser();

  if (authError || !user) {
    return {
      error: NextResponse.json({ error: "Unauthorized" }, { status: 401 }),
    };
  }

  const access = await resolveRouteAccess(supabase, user);
  const { data: document, error } = await supabase
    .from("documents")
    .select("id, client_id, storage_path")
    .eq("id", id)
    .is("deleted_at", null)
    .single();

  if (error || !document) {
    return {
      error: NextResponse.json(
        { error: "Document not found" },
        { status: 404 },
      ),
    };
  }

  if (!canAccessClient(access, document.client_id)) {
    return {
      error: NextResponse.json({ error: "Forbidden" }, { status: 403 }),
    };
  }

  return { supabase, document };
}

export async function GET(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await ensureDocumentAccess(id);
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
      return NextResponse.json(
        { error: "Document not found" },
        { status: 404 },
      );
    }

    return NextResponse.json({ document });
  } catch (error) {
    console.error("Error fetching document:", error);
    return NextResponse.json(
      {
        error:
          error instanceof Error ? error.message : "Failed to fetch document",
      },
      { status: 500 },
    );
  }
}

export async function PATCH(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await ensureDocumentAccess(id);
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
      return NextResponse.json(
        { error: "Document not found" },
        { status: 404 },
      );
    }

    return NextResponse.json({ document });
  } catch (error) {
    console.error("Error updating document:", error);
    return NextResponse.json(
      {
        error:
          error instanceof Error ? error.message : "Failed to update document",
      },
      { status: 500 },
    );
  }
}

export async function DELETE(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await ensureDocumentAccess(id);
    if ("error" in guard) {
      return guard.error;
    }

    const { supabase } = guard;

    // Soft delete document record
    const { error: dbError } = await supabase
      .from("documents")
      .update({ deleted_at: new Date().toISOString() })
      .eq("id", id);

    if (dbError) throw dbError;

    // Delete from storage (optional - can keep for recovery)
    // await deleteFile(StorageBuckets.DOCUMENTS, document.storage_path);

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting document:", error);
    return NextResponse.json(
      {
        error:
          error instanceof Error ? error.message : "Failed to delete document",
      },
      { status: 500 },
    );
  }
}
