import { NextRequest } from "next/server";
import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import type { BrandingSettings } from "@/lib/branding/get-branding";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

export async function POST(req: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();
    if (authError || !user) {
      return apiUnauthorized(req);
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
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "clientId is required",
      });
    }

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
      return apiInternalError(req, upsertError.message);
    }

    return apiSuccess(req, { saved: true }, { extra: { success: true } });
  } catch (error) {
    return apiInternalError(
      req,
      error instanceof Error ? error.message : "Failed to save branding",
    );
  }
}
