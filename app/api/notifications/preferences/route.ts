import { NextRequest, NextResponse } from "next/server";
import { eq } from "drizzle-orm";
import { z } from "zod";
import { db } from "@/lib/db";
import { parseNotificationPreferences } from "@/lib/notifications/preferences";
import { users } from "@/lib/db/schema/users";
import { createClient } from "@/lib/supabase/server";

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

export async function GET() {
  try {
    const user = await getCurrentUser();
    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const [dbUser] = await db
      .select({ securitySettings: users.securitySettings })
      .from(users)
      .where(eq(users.id, user.id))
      .limit(1);

    const preferences = parseNotificationPreferences(dbUser?.securitySettings);
    return NextResponse.json({ preferences });
  } catch (error) {
    console.error("Error fetching notification preferences:", error);
    return NextResponse.json(
      { error: "Failed to fetch preferences" },
      { status: 500 },
    );
  }
}

export async function PATCH(request: NextRequest) {
  try {
    const user = await getCurrentUser();
    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const body = await request.json();
    const parsed = updateSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json(
        { error: "Invalid request payload" },
        { status: 400 },
      );
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
    return NextResponse.json({ success: true, preferences });
  } catch (error) {
    console.error("Error updating notification preferences:", error);
    return NextResponse.json(
      { error: "Failed to update preferences" },
      { status: 500 },
    );
  }
}
