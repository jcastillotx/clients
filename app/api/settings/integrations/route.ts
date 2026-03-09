import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import { encryptedSettings, type EncryptedSetting } from "@/lib/db/schema/encrypted-settings";
import { eq, and } from "drizzle-orm";
import { encrypt, decrypt, maskSecret } from "@/lib/encryption";
import { isUserAdmin } from "@/lib/rbac/check";
import { z } from "zod";

const querySchema = z.object({
  clientId: z.string().uuid(),
  provider: z.string().optional(),
  category: z.string().optional(),
});

/**
 * GET /api/settings/integrations
 * List encrypted settings for a client (masked values only)
 */
export async function GET(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    if (!(await isUserAdmin(user.id))) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const { searchParams } = new URL(request.url);
    const parsed = querySchema.safeParse({
      clientId: searchParams.get("clientId"),
      provider: searchParams.get("provider") || undefined,
      category: searchParams.get("category") || undefined,
    });

    if (!parsed.success) {
      return NextResponse.json({ error: "Invalid parameters", details: parsed.error.flatten() }, { status: 400 });
    }

    const { clientId, provider, category } = parsed.data;

    const conditions = [eq(encryptedSettings.clientId, clientId)];
    if (provider) {
      conditions.push(eq(encryptedSettings.provider, provider as any));
    }
    if (category) {
      conditions.push(eq(encryptedSettings.category, category as any));
    }

    const settings = await db
      .select()
      .from(encryptedSettings)
      .where(and(...conditions));

    // Return masked values - never expose raw secrets
    const masked = settings.map((s: EncryptedSetting) => {
      let maskedValue = "";
      try {
        const decrypted = decrypt(s.encryptedValue);
        maskedValue = maskSecret(decrypted);
      } catch {
        maskedValue = "••••••••";
      }
      return {
        id: s.id,
        provider: s.provider,
        category: s.category,
        settingKey: s.settingKey,
        maskedValue,
        isActive: s.isActive,
        label: s.label,
        lastRotatedAt: s.lastRotatedAt,
        lastVerifiedAt: s.lastVerifiedAt,
        createdAt: s.createdAt,
        updatedAt: s.updatedAt,
      };
    });

    return NextResponse.json(masked);
  } catch (error) {
    console.error("Error fetching integration settings:", error);
    return NextResponse.json({ error: "Failed to fetch settings" }, { status: 500 });
  }
}

const upsertSchema = z.object({
  clientId: z.string().uuid(),
  provider: z.string(),
  category: z.string(),
  settings: z.array(
    z.object({
      key: z.string().min(1),
      value: z.string().min(1),
      label: z.string().optional(),
    }),
  ),
});

/**
 * POST /api/settings/integrations
 * Create or update encrypted settings for a provider
 */
export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    if (!(await isUserAdmin(user.id))) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const body = await request.json();
    const parsed = upsertSchema.safeParse(body);

    if (!parsed.success) {
      return NextResponse.json({ error: "Invalid request body", details: parsed.error.flatten() }, { status: 400 });
    }

    const { clientId, provider, category, settings: settingsToSave } = parsed.data;

    const results = [];

    for (const setting of settingsToSave) {
      const encryptedValue = encrypt(setting.value);

      // Check if setting already exists
      const existing = await db
        .select({ id: encryptedSettings.id })
        .from(encryptedSettings)
        .where(
          and(
            eq(encryptedSettings.clientId, clientId),
            eq(encryptedSettings.provider, provider as any),
            eq(encryptedSettings.settingKey, setting.key),
          ),
        )
        .limit(1);

      if (existing.length > 0) {
        // Update
        const [updated] = await db
          .update(encryptedSettings)
          .set({
            encryptedValue,
            category: category as any,
            label: setting.label,
            isActive: true,
            lastRotatedAt: new Date(),
            lastVerifiedAt: null,
            updatedBy: user.id,
            updatedAt: new Date(),
          })
          .where(eq(encryptedSettings.id, existing[0].id))
          .returning();
        results.push({ key: setting.key, action: "updated", id: updated.id });
      } else {
        // Insert
        const [inserted] = await db
          .insert(encryptedSettings)
          .values({
            clientId,
            provider: provider as any,
            category: category as any,
            settingKey: setting.key,
            encryptedValue,
            label: setting.label,
            lastVerifiedAt: null,
            updatedBy: user.id,
          })
          .returning();
        results.push({ key: setting.key, action: "created", id: inserted.id });
      }
    }

    return NextResponse.json({ success: true, results });
  } catch (error) {
    console.error("Error saving integration settings:", error);
    return NextResponse.json({ error: "Failed to save settings" }, { status: 500 });
  }
}

/**
 * DELETE /api/settings/integrations
 * Remove a specific setting or all settings for a provider
 */
export async function DELETE(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    if (!(await isUserAdmin(user.id))) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const { searchParams } = new URL(request.url);
    const settingId = searchParams.get("id");
    const clientId = searchParams.get("clientId");
    const provider = searchParams.get("provider");

    if (settingId) {
      // Delete a single setting
      await db.delete(encryptedSettings).where(eq(encryptedSettings.id, settingId));
    } else if (clientId && provider) {
      // Delete all settings for a provider
      await db
        .delete(encryptedSettings)
        .where(
          and(
            eq(encryptedSettings.clientId, clientId),
            eq(encryptedSettings.provider, provider as any),
          ),
        );
    } else {
      return NextResponse.json({ error: "Provide either 'id' or 'clientId' + 'provider'" }, { status: 400 });
    }

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting integration settings:", error);
    return NextResponse.json({ error: "Failed to delete settings" }, { status: 500 });
  }
}
