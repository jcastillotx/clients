import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { uploadFile, generateFilePath, StorageBuckets } from "@/lib/storage/upload";

export async function POST(request: Request) {
  try {
    const supabase = await createClient();

    // Get authenticated user
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    // Get form data
    const formData = await request.formData();
    const file = formData.get("file") as File;
    const clientId = formData.get("clientId") as string;
    const requestId = formData.get("requestId") as string | null;
    const name = formData.get("name") as string;
    const description = formData.get("description") as string | null;
    const tags = formData.get("tags") as string | null;

    if (!file || !clientId || !name) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    // Verify user has access to this client
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
      return NextResponse.json({ error: "Access denied" }, { status: 403 });
    }

    // Generate unique file path
    const filePath = generateFilePath(clientId, "documents", file.name);

    // Upload to Supabase Storage
    const uploadResult = await uploadFile({
      bucket: StorageBuckets.DOCUMENTS,
      path: filePath,
      file,
    });

    if (uploadResult.error) {
      return NextResponse.json({ error: uploadResult.error }, { status: 500 });
    }

    // Create document record
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
      // Clean up uploaded file if database insert fails
      await supabase.storage.from(StorageBuckets.DOCUMENTS).remove([uploadResult.path]);
      return NextResponse.json({ error: dbError.message }, { status: 500 });
    }

    return NextResponse.json({ document }, { status: 201 });
  } catch (error) {
    console.error("Error uploading document:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to upload document",
      },
      { status: 500 },
    );
  }
}
