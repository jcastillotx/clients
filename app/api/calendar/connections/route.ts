import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import { calendarConnections } from "@/lib/db/schema/calendar-integrations";
import { eq, and } from "drizzle-orm";

/**
 * GET /api/calendar/connections
 * Returns the authenticated user's calendar connections (no token values).
 */
export async function GET() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

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

  return NextResponse.json(connections);
}

/**
 * DELETE /api/calendar/connections?provider=google
 * Disconnect (deactivate) a calendar connection for the current user.
 */
export async function DELETE(req: NextRequest) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const provider = req.nextUrl.searchParams.get("provider") as "google" | "microsoft" | null;
  if (!provider || !["google", "microsoft"].includes(provider)) {
    return NextResponse.json({ error: "Invalid provider" }, { status: 400 });
  }

  const deleted = await db
    .delete(calendarConnections)
    .where(and(eq(calendarConnections.userId, user.id), eq(calendarConnections.provider, provider)))
    .returning({ id: calendarConnections.id });

  if (deleted.length === 0) {
    return NextResponse.json({ error: "Connection not found" }, { status: 404 });
  }

  return NextResponse.json({ success: true });
}
