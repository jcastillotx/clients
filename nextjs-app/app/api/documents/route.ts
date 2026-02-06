import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";

export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const clientId = searchParams.get("clientId");
    const requestId = searchParams.get("requestId");
    const limit = parseInt(searchParams.get("limit") || "50");
    const offset = parseInt(searchParams.get("offset") || "0");

    const supabase = createClient();

    // Get authenticated user
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    // Build query
    let query = supabase
      .from("documents")
      .select(
        `
        *,
        client:clients(id, company_name),
        uploader:users!uploaded_by(id, name, email)
      `,
      )
      .is("deleted_at", null)
      .eq("is_latest_version", true)
      .order("created_at", { ascending: false })
      .range(offset, offset + limit - 1);

    // Filter by client if provided
    if (clientId) {
      query = query.eq("client_id", clientId);
    }

    // Filter by request if provided
    if (requestId) {
      query = query.eq("request_id", requestId);
    }

    const { data: documents, error } = await query;

    if (error) throw error;

    return NextResponse.json({ documents: documents || [] });
  } catch (error) {
    console.error("Error fetching documents:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to fetch documents",
      },
      { status: 500 },
    );
  }
}
