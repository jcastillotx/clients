import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { deleteFile, StorageBuckets } from "@/lib/storage/upload";

export async function GET(request: Request, { params }: { params: { id: string } }) {
  try {
    const supabase = createClient();

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
      .eq("id", params.id)
      .is("deleted_at", null)
      .single();

    if (error) throw error;

    if (!document) {
      return NextResponse.json({ error: "Document not found" }, { status: 404 });
    }

    return NextResponse.json({ document });
  } catch (error) {
    console.error("Error fetching document:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to fetch document",
      },
      { status: 500 },
    );
  }
}

export async function PATCH(request: Request, { params }: { params: { id: string } }) {
  try {
    const body = await request.json();
    const { name, description, tags, isPublic } = body;

    const supabase = createClient();

    const updateData: any = {
      updated_at: new Date().toISOString(),
    };

    if (name !== undefined) updateData.name = name;
    if (description !== undefined) updateData.description = description;
    if (tags !== undefined) updateData.tags = tags;
    if (isPublic !== undefined) updateData.is_public = isPublic;

    const { data: document, error } = await supabase
      .from("documents")
      .update(updateData)
      .eq("id", params.id)
      .is("deleted_at", null)
      .select()
      .single();

    if (error) throw error;

    if (!document) {
      return NextResponse.json({ error: "Document not found" }, { status: 404 });
    }

    return NextResponse.json({ document });
  } catch (error) {
    console.error("Error updating document:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to update document",
      },
      { status: 500 },
    );
  }
}

export async function DELETE(request: Request, { params }: { params: { id: string } }) {
  try {
    const supabase = createClient();

    // Get document to find storage path
    const { data: document } = await supabase
      .from("documents")
      .select("storage_path")
      .eq("id", params.id)
      .is("deleted_at", null)
      .single();

    if (!document) {
      return NextResponse.json({ error: "Document not found" }, { status: 404 });
    }

    // Soft delete document record
    const { error: dbError } = await supabase
      .from("documents")
      .update({ deleted_at: new Date().toISOString() })
      .eq("id", params.id);

    if (dbError) throw dbError;

    // Delete from storage (optional - can keep for recovery)
    // await deleteFile(StorageBuckets.DOCUMENTS, document.storage_path);

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting document:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to delete document",
      },
      { status: 500 },
    );
  }
}
