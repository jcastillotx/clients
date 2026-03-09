import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import { calendarConnections } from "@/lib/db/schema/calendar-integrations";
import { eq, and } from "drizzle-orm";
import { CalendarConnections } from "@/components/calendar/calendar-connections";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { Badge } from "@/components/ui/badge";
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

      {/* Setup Instructions */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Setup Instructions</CardTitle>
          <CardDescription>
            Follow the steps for your calendar provider. One-time setup takes about 2 minutes.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Accordion type="single" collapsible className="w-full">

            {/* Google Calendar */}
            <AccordionItem value="google">
              <AccordionTrigger className="text-sm font-medium gap-3">
                <span className="flex items-center gap-2.5">
                  <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                  </svg>
                  Connecting Google Calendar
                </span>
              </AccordionTrigger>
              <AccordionContent className="pt-3 pb-1">
                <ol className="space-y-4 text-sm text-muted-foreground list-none">
                  <li className="flex gap-3">
                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-semibold text-primary">1</span>
                    <div>
                      <p className="font-medium text-foreground">Click &ldquo;Connect&rdquo; next to Google Calendar above.</p>
                      <p className="mt-0.5">You&apos;ll be redirected to Google&apos;s sign-in page.</p>
                    </div>
                  </li>
                  <li className="flex gap-3">
                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-semibold text-primary">2</span>
                    <div>
                      <p className="font-medium text-foreground">Sign in with your Google account.</p>
                      <p className="mt-0.5">Use the Google account whose calendar you want to share availability from.</p>
                    </div>
                  </li>
                  <li className="flex gap-3">
                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-semibold text-primary">3</span>
                    <div>
                      <p className="font-medium text-foreground">Review and approve the permissions.</p>
                      <p className="mt-0.5">
                        We request two scopes only:
                      </p>
                      <ul className="mt-1.5 space-y-1 pl-1">
                        <li className="flex items-start gap-2">
                          <Badge variant="secondary" className="mt-0.5 text-[10px] shrink-0">Read-only</Badge>
                          <span><code className="text-xs">calendar.readonly</code> — lets us read your primary calendar to get its name.</span>
                        </li>
                        <li className="flex items-start gap-2">
                          <Badge variant="secondary" className="mt-0.5 text-[10px] shrink-0">Read-only</Badge>
                          <span><code className="text-xs">calendar.freebusy</code> — lets us check your free/busy blocks without seeing event titles or details.</span>
                        </li>
                      </ul>
                    </div>
                  </li>
                  <li className="flex gap-3">
                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-semibold text-primary">4</span>
                    <div>
                      <p className="font-medium text-foreground">Click &ldquo;Allow&rdquo; to complete the connection.</p>
                      <p className="mt-0.5">You&apos;ll be redirected back here with a &ldquo;Connected&rdquo; confirmation.</p>
                    </div>
                  </li>
                </ol>
                <div className="mt-4 rounded-lg border border-amber-500/20 bg-amber-500/5 p-3 text-xs text-amber-700 dark:text-amber-400">
                  <strong>Google verification notice:</strong> If you see a warning that the app is &ldquo;not verified by Google&rdquo;, click <em>Advanced</em> → <em>Go to [app name] (unsafe)</em>. This appears during development before OAuth app verification is complete and is safe to proceed through.
                </div>
              </AccordionContent>
            </AccordionItem>

            {/* Microsoft Outlook */}
            <AccordionItem value="microsoft">
              <AccordionTrigger className="text-sm font-medium gap-3">
                <span className="flex items-center gap-2.5">
                  <svg className="h-4 w-4 shrink-0" viewBox="0 0 23 23" aria-hidden="true">
                    <path fill="#f35325" d="M1 1h10v10H1z"/>
                    <path fill="#81bc06" d="M12 1h10v10H12z"/>
                    <path fill="#05a6f0" d="M1 12h10v10H1z"/>
                    <path fill="#ffba08" d="M12 12h10v10H12z"/>
                  </svg>
                  Connecting Microsoft Outlook Calendar
                </span>
              </AccordionTrigger>
              <AccordionContent className="pt-3 pb-1">
                <ol className="space-y-4 text-sm text-muted-foreground list-none">
                  <li className="flex gap-3">
                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-semibold text-primary">1</span>
                    <div>
                      <p className="font-medium text-foreground">Click &ldquo;Connect&rdquo; next to Microsoft Outlook Calendar above.</p>
                      <p className="mt-0.5">You&apos;ll be redirected to Microsoft&apos;s sign-in page.</p>
                    </div>
                  </li>
                  <li className="flex gap-3">
                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-semibold text-primary">2</span>
                    <div>
                      <p className="font-medium text-foreground">Sign in with your Microsoft or Office 365 account.</p>
                      <p className="mt-0.5">Works with personal Microsoft accounts, Outlook.com, and Microsoft 365 / work accounts.</p>
                    </div>
                  </li>
                  <li className="flex gap-3">
                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-semibold text-primary">3</span>
                    <div>
                      <p className="font-medium text-foreground">Review and approve the permissions.</p>
                      <p className="mt-0.5">
                        We request three scopes only:
                      </p>
                      <ul className="mt-1.5 space-y-1 pl-1">
                        <li className="flex items-start gap-2">
                          <Badge variant="secondary" className="mt-0.5 text-[10px] shrink-0">Read-only</Badge>
                          <span><code className="text-xs">Calendars.Read</code> — lets us check your calendar events to determine free/busy status.</span>
                        </li>
                        <li className="flex items-start gap-2">
                          <Badge variant="secondary" className="mt-0.5 text-[10px] shrink-0">Read-only</Badge>
                          <span><code className="text-xs">User.Read</code> — lets us read your name and get your primary calendar label.</span>
                        </li>
                        <li className="flex items-start gap-2">
                          <Badge variant="outline" className="mt-0.5 text-[10px] shrink-0">Auth</Badge>
                          <span><code className="text-xs">offline_access</code> — lets us refresh your token automatically so you don&apos;t need to reconnect every hour.</span>
                        </li>
                      </ul>
                    </div>
                  </li>
                  <li className="flex gap-3">
                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-semibold text-primary">4</span>
                    <div>
                      <p className="font-medium text-foreground">Click &ldquo;Accept&rdquo; to complete the connection.</p>
                      <p className="mt-0.5">You&apos;ll be redirected back here with a &ldquo;Connected&rdquo; confirmation.</p>
                    </div>
                  </li>
                </ol>
                <div className="mt-4 rounded-lg border border-blue-500/20 bg-blue-500/5 p-3 text-xs text-blue-700 dark:text-blue-400">
                  <strong>Work accounts:</strong> If your organisation requires admin approval for third-party apps, ask your Microsoft 365 admin to grant consent for the app in the Azure Active Directory admin centre before you try to connect.
                </div>
              </AccordionContent>
            </AccordionItem>

            {/* Troubleshooting */}
            <AccordionItem value="troubleshooting" className="border-b-0">
              <AccordionTrigger className="text-sm font-medium">
                Troubleshooting
              </AccordionTrigger>
              <AccordionContent className="pt-3 pb-1">
                <div className="space-y-3 text-sm text-muted-foreground">
                  <div>
                    <p className="font-medium text-foreground">Connection shows &ldquo;Token expired&rdquo;</p>
                    <p className="mt-0.5">Click <strong>Disconnect</strong> then <strong>Connect</strong> again to re-authorise. This refreshes your access token.</p>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">OAuth was denied or cancelled</p>
                    <p className="mt-0.5">Make sure you click <em>Allow</em> (Google) or <em>Accept</em> (Microsoft) on the permissions screen. If you clicked Deny, try connecting again.</p>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Microsoft work account says &ldquo;Need admin approval&rdquo;</p>
                    <p className="mt-0.5">Your IT/Microsoft 365 admin must grant tenant-wide consent for the app in Azure Active Directory. Share this page URL with them and ask them to approve <code className="text-xs">Calendars.Read</code> and <code className="text-xs">User.Read</code> for your organisation.</p>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Availability shows &ldquo;No calendar&rdquo; for some staff</p>
                    <p className="mt-0.5">That staff member hasn&apos;t connected a calendar yet. Ask them to visit <strong>Settings → Calendar</strong> and connect their calendar.</p>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Calendar not configured error</p>
                    <p className="mt-0.5">The Google or Microsoft OAuth app credentials haven&apos;t been set up on this server. Contact your administrator to configure <code className="text-xs">GOOGLE_CALENDAR_CLIENT_ID</code> / <code className="text-xs">MICROSOFT_CALENDAR_CLIENT_ID</code> environment variables.</p>
                  </div>
                </div>
              </AccordionContent>
            </AccordionItem>

          </Accordion>
        </CardContent>
      </Card>

      {/* Privacy notice */}
      <Card className="border-border/60 bg-muted/30">
        <CardContent className="pt-6">
          <div className="space-y-2 text-sm text-muted-foreground">
            <p className="font-medium text-foreground">Privacy & security</p>
            <ul className="space-y-1 list-disc list-inside">
              <li>We request read-only access to your calendar free/busy information only</li>
              <li>We never read event titles, descriptions, attendees, or locations</li>
              <li>Your access token is encrypted at rest using AES-256-GCM</li>
              <li>Tokens are automatically refreshed — you only need to connect once</li>
              <li>You can disconnect at any time and your tokens are immediately deleted</li>
            </ul>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
