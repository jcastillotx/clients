import { NextRequest, NextResponse } from "next/server";
import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";

/**
 * POST /api/branding
 * Body: { clientId, logoUrl, domain }
 * Upserts white_label_configs using admin client to bypass RLS for admin users.
 */
export async function POST(req: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();
    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const { clientId, logoUrl, domain } = await req.json();
    if (!clientId) {
      return NextResponse.json({ error: "clientId is required" }, { status: 400 });
    }

    const dbClient = createAdminClientIfAvailable() ?? supabase;

    const { error: upsertError } = await dbClient.from("white_label_configs").upsert(
      {
        client_id: clientId,
        logo_url: logoUrl || null,
        domain: domain || null,
        is_active: true,
        updated_at: new Date().toISOString(),
      },
      { onConflict: "client_id" },
    );

    if (upsertError) {
      return NextResponse.json({ error: upsertError.message }, { status: 500 });
    }

    return NextResponse.json({ success: true });
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to save branding" },
      { status: 500 },
    );
  }
}
