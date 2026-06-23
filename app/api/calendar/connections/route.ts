import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import { calendarConnections } from "@/lib/db/schema/calendar-integrations";
import { eq, and } from "drizzle-orm";
import {
  apiError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

export async function GET(request: Request) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return apiUnauthorized(request);

  const connections = await db
    .select({
      id: calendarConnections.id,
      provider: calendarConnections.provider,
      calendarId: calendarConnections.calendarId,
      calendarName: calendarConnections.calendarName,
      isActive: calendarConnections.isActive,
      tokenExpiry: calendarConnections.tokenExpiry,
      createdAt: calendarConnections.createdAt,
    })
    .from(calendarConnections)
    .where(and(eq(calendarConnections.userId, user.id), eq(calendarConnections.isActive, true)));

  return apiSuccess(request, connections, { extra: { connections } });
}

export async function DELETE(req: NextRequest) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return apiUnauthorized(req);

  const provider = req.nextUrl.searchParams.get("provider") as "google" | "microsoft" | null;
  if (!provider || !["google", "microsoft"].includes(provider)) {
    return apiError(req, {
      status: 400,
      code: "BAD_REQUEST",
      message: "Invalid provider",
    });
  }

  const deleted = await db
    .delete(calendarConnections)
    .where(and(eq(calendarConnections.userId, user.id), eq(calendarConnections.provider, provider)))
    .returning({ id: calendarConnections.id });

  if (deleted.length === 0) {
    return apiNotFound(req, "Connection not found");
  }

  return apiSuccess(req, { deleted: true }, { extra: { success: true } });
}
