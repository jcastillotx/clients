import { and, eq } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import { encryptedSettings, type EncryptedSetting } from "@/lib/db/schema/encrypted-settings";
import { encrypt, decrypt, maskSecret } from "@/lib/encryption";
import { isUserAdmin } from "@/lib/rbac/check";
import { getPublicIntegrationError } from "@/lib/settings/integration-errors";
import {
  isIntegrationCategory,
  isIntegrationProvider,
  validateIntegrationProviderCategory,
} from "@/lib/settings/integration-validation";
import { z } from "zod";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

const querySchema = z.object({
  clientId: z.string().uuid(),
  provider: z.string().optional(),
  category: z.string().optional(),
});

export async function GET(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    if (!(await isUserAdmin(user.id))) {
      return apiForbidden(request);
    }

    const { searchParams } = new URL(request.url);
    const parsed = querySchema.safeParse({
      clientId: searchParams.get("clientId"),
      provider: searchParams.get("provider") || undefined,
      category: searchParams.get("category") || undefined,
    });

    if (!parsed.success) {
      return apiValidationError(request, parsed.error);
    }

    const { clientId, provider, category } = parsed.data;

    const conditions = [eq(encryptedSettings.clientId, clientId)];
    if (provider) {
      if (!isIntegrationProvider(provider)) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: "Unsupported integration provider.",
        });
      }
      conditions.push(eq(encryptedSettings.provider, provider));
    }
    if (category) {
      if (!isIntegrationCategory(category)) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: "Unsupported integration category.",
        });
      }
      conditions.push(eq(encryptedSettings.category, category));
    }

    const settings = await db
      .select()
      .from(encryptedSettings)
      .where(and(...conditions));

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

    return apiSuccess(request, masked);
  } catch (error) {
    console.error("Error fetching integration settings:", error);
    return apiInternalError(request, "Failed to fetch settings");
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

export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    if (!(await isUserAdmin(user.id))) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const parsed = upsertSchema.safeParse(body);

    if (!parsed.success) {
      return apiValidationError(request, parsed.error);
    }

    const {
      clientId,
      provider: requestedProvider,
      category: requestedCategory,
      settings: settingsToSave,
    } = parsed.data;
    const providerValidation = validateIntegrationProviderCategory(requestedProvider, requestedCategory);

    if (!providerValidation.success) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: providerValidation.error,
      });
    }

    const { provider, category } = providerValidation;

    const results = [];

    for (const setting of settingsToSave) {
      const encryptedValue = encrypt(setting.value);

      const existing = await db
        .select({ id: encryptedSettings.id })
        .from(encryptedSettings)
        .where(
          and(
            eq(encryptedSettings.clientId, clientId),
            eq(encryptedSettings.provider, provider),
            eq(encryptedSettings.settingKey, setting.key),
          ),
        )
        .limit(1);

      if (existing.length > 0) {
        const [updated] = await db
          .update(encryptedSettings)
          .set({
            encryptedValue,
            category,
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
        const [inserted] = await db
          .insert(encryptedSettings)
          .values({
            clientId,
            provider,
            category,
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

    return apiSuccess(request, { results }, { extra: { success: true, results } });
  } catch (error) {
    console.error("Error saving integration settings:", error);
    const publicError = getPublicIntegrationError(error);
    return apiError(request, {
      status: publicError.status,
      code: publicError.status >= 500 ? "INTERNAL_ERROR" : "BAD_REQUEST",
      message: publicError.error,
    });
  }
}

export async function DELETE(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    if (!(await isUserAdmin(user.id))) {
      return apiForbidden(request);
    }

    const { searchParams } = new URL(request.url);
    const settingId = searchParams.get("id");
    const clientId = searchParams.get("clientId");
    const provider = searchParams.get("provider");

    if (settingId) {
      await db.delete(encryptedSettings).where(eq(encryptedSettings.id, settingId));
    } else if (clientId && provider) {
      if (!isIntegrationProvider(provider)) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: "Unsupported integration provider.",
        });
      }

      await db
        .delete(encryptedSettings)
        .where(
          and(
            eq(encryptedSettings.clientId, clientId),
            eq(encryptedSettings.provider, provider),
          ),
        );
    } else {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Provide either 'id' or 'clientId' + 'provider'",
      });
    }

    return apiSuccess(request, { deleted: true }, { extra: { success: true } });
  } catch (error) {
    console.error("Error deleting integration settings:", error);
    return apiInternalError(request, "Failed to delete settings");
  }
}
