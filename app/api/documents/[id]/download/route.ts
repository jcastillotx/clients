import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { getSignedUrl, StorageBuckets } from "@/lib/storage/upload";

export async function GET(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();

    // Get authenticated user
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    // Get document
    const { data: document, error } = await supabase
      .from("documents")
      .select("storage_path, file_name, client_id")
      .eq("id", id)
      .is("deleted_at", null)
      .single();

    if (error || !document) {
      return NextResponse.json({ error: "Document not found" }, { status: 404 });
    }

    // Verify access (RLS handles this, but double-check for signed URLs)
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
      return NextResponse.json({ error: "Access denied" }, { status: 403 });
    }

    // Get signed URL for download (valid for 1 hour)
    const { url, error: urlError } = await getSignedUrl(StorageBuckets.DOCUMENTS, document.storage_path, 3600);

    if (urlError || !url) {
      return NextResponse.json({ error: "Failed to generate download URL" }, { status: 500 });
    }

    // Track download
    await supabase
      .from("document_shares")
      .update({
        last_accessed_at: new Date().toISOString(),
        access_count: supabase.rpc("increment", { row_id: id }),
      })
      .eq("document_id", id)
      .eq("shared_with_user_id", user.id);

    return NextResponse.json({
      url,
      fileName: document.file_name,
    });
  } catch (error) {
    console.error("Error generating download URL:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to generate download URL",
      },
      { status: 500 },
    );
  }
}
