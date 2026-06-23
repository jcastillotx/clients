import { NextRequest } from "next/server";
import { eq } from "drizzle-orm";
import { z } from "zod";
import { db } from "@/lib/db";
import { parseNotificationPreferences } from "@/lib/notifications/preferences";
import { users } from "@/lib/db/schema/users";
import { createClient } from "@/lib/supabase/server";
import {
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

const updateSchema = z.object({
  emailEnabled: z.boolean().optional(),
  inAppEnabled: z.boolean().optional(),
});

async function getCurrentUser() {
  const supabase = await createClient();
  const {
    data: { user },
    error,
  } = await supabase.auth.getUser();

  if (error || !user) {
    return null;
  }

  return user;
}

export async function GET(request: Request) {
  try {
    const user = await getCurrentUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const [dbUser] = await db
      .select({ securitySettings: users.securitySettings })
      .from(users)
      .where(eq(users.id, user.id))
      .limit(1);

    const preferences = parseNotificationPreferences(dbUser?.securitySettings);
    return apiSuccess(request, preferences, { extra: { preferences } });
  } catch (error) {
    console.error("Error fetching notification preferences:", error);
    return apiInternalError(request, "Failed to fetch preferences");
  }
}

export async function PATCH(request: NextRequest) {
  try {
    const user = await getCurrentUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const body = await request.json();
    const parsed = updateSchema.safeParse(body);
    if (!parsed.success) {
      return apiValidationError(request, parsed.error);
    }

    const [dbUser] = await db
      .select({ securitySettings: users.securitySettings })
      .from(users)
      .where(eq(users.id, user.id))
      .limit(1);

    const current = (dbUser?.securitySettings || {}) as Record<string, unknown>;
    const currentNotifications = (current.notifications || {}) as Record<
      string,
      unknown
    >;
    const nextSettings = {
      ...current,
      notifications: {
        ...currentNotifications,
        ...parsed.data,
      },
    };

    await db
      .update(users)
      .set({ securitySettings: nextSettings })
      .where(eq(users.id, user.id));

    const preferences = parseNotificationPreferences(nextSettings);
    return apiSuccess(request, preferences, {
      extra: { success: true, preferences },
    });
  } catch (error) {
    console.error("Error updating notification preferences:", error);
    return apiInternalError(request, "Failed to update preferences");
  }
}
