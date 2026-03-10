import { NextRequest, NextResponse } from "next/server";
import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import type { BrandingSettings } from "@/lib/branding/get-branding";

/**
 * POST /api/branding
 * Body: { clientId, logoUrl, domain, primaryColor, secondaryColor, settings }
 * Upserts white_label_configs using admin client to bypass RLS for admin users.
 * Extended settings are serialized as JSON into the custom_css column.
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

    const body = (await req.json()) as {
      clientId?: string;
      logoUrl?: string | null;
      domain?: string | null;
      primaryColor?: string | null;
      secondaryColor?: string | null;
      settings?: Partial<BrandingSettings>;
    };

    const { clientId, logoUrl, domain, primaryColor, secondaryColor, settings } = body;

    if (!clientId) {
      return NextResponse.json({ error: "clientId is required" }, { status: 400 });
    }

    // Serialize extended settings as JSON into the custom_css column
    const customCss = settings ? JSON.stringify(settings) : null;

    const dbClient = createAdminClientIfAvailable() ?? supabase;

    const { error: upsertError } = await dbClient.from("white_label_configs").upsert(
      {
        client_id: clientId,
        logo_url: logoUrl ?? null,
        domain: domain ?? null,
        primary_color: primaryColor ?? null,
        secondary_color: secondaryColor ?? null,
        custom_css: customCss,
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
