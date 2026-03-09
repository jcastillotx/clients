import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import { calendarConnections } from "@/lib/db/schema/calendar-integrations";
import { eq, and } from "drizzle-orm";
import { CalendarConnections } from "@/components/calendar/calendar-connections";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { CalendarDays } from "lucide-react";

export const metadata = {
  title: "Calendar Settings | KRE8IV",
  description: "Connect your calendar for availability and meeting scheduling",
};

/**
 * Calendar settings page for staff and admins.
 * Accessible at /settings/calendar.
 * Handles OAuth callback redirects (connected=google, error=...).
 */
export default async function CalendarSettingsPage({
  searchParams,
}: {
  searchParams: Promise<{ connected?: string; error?: string }>;
}) {
  const resolvedParams = await searchParams;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return null; // Layout already guards auth

  // Fetch current connections for this user
  const connections = await db
    .select({
      id: calendarConnections.id,
      provider: calendarConnections.provider,
      calendarName: calendarConnections.calendarName,
      isActive: calendarConnections.isActive,
      tokenExpiry: calendarConnections.tokenExpiry,
      createdAt: calendarConnections.createdAt,
    })
    .from(calendarConnections)
    .where(and(eq(calendarConnections.userId, user.id), eq(calendarConnections.isActive, true)));

  return (
    <div className="flex flex-col gap-8 p-8 max-w-3xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight flex items-center gap-3">
          <CalendarDays className="h-7 w-7 text-primary" />
          Calendar
        </h1>
        <p className="mt-2 text-muted-foreground">
          Connect your Google or Microsoft calendar so your team can see your availability when
          scheduling meetings.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Connected Calendars</CardTitle>
          <CardDescription>
            Your calendar data is read-only — we only check free/busy status, never read event
            details.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <CalendarConnections
            initialConnections={connections.map((c) => ({
              ...c,
              tokenExpiry: c.tokenExpiry?.toISOString() ?? null,
              createdAt: c.createdAt.toISOString(),
            }))}
            justConnected={resolvedParams.connected ?? null}
            oauthError={resolvedParams.error ?? null}
          />
        </CardContent>
      </Card>

      <Card className="border-border/60 bg-muted/30">
        <CardContent className="pt-6">
          <div className="space-y-2 text-sm text-muted-foreground">
            <p className="font-medium text-foreground">How it works</p>
            <ul className="space-y-1 list-disc list-inside">
              <li>We request read-only access to your calendar free/busy information</li>
              <li>We never read the titles or details of your events</li>
              <li>Your access token is encrypted at rest using AES-256-GCM</li>
              <li>You can disconnect at any time and your tokens are immediately deleted</li>
            </ul>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
