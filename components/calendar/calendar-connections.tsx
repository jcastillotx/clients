"use client";

import { useState, useEffect, useTransition } from "react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { useToast } from "@/hooks/use-toast";
import {
  CheckCircle2,
  XCircle,
  Loader2,
  Calendar,
  AlertCircle,
  RefreshCw,
} from "lucide-react";

interface CalendarConnection {
  id: string;
  provider: "google" | "microsoft";
  calendarName: string | null;
  isActive: boolean;
  tokenExpiry: string | null;
  createdAt: string;
}

interface CalendarConnectionsProps {
  /** Pre-fetched connections from the server (used as initial state). */
  initialConnections?: CalendarConnection[];
  /** Search param from OAuth callback — "google" | "microsoft" */
  justConnected?: string | null;
  /** Search param from OAuth callback — error code */
  oauthError?: string | null;
}

const PROVIDER_LABELS: Record<string, string> = {
  google: "Google Calendar",
  microsoft: "Microsoft Outlook Calendar",
};

const PROVIDER_COLORS: Record<string, string> = {
  google: "text-red-500",
  microsoft: "text-blue-500",
};

function ProviderIcon({ provider }: { provider: string }) {
  if (provider === "google") {
    return (
      <svg className="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
        <path
          d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
          fill="#4285F4"
        />
        <path
          d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
          fill="#34A853"
        />
        <path
          d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
          fill="#FBBC05"
        />
        <path
          d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
          fill="#EA4335"
        />
      </svg>
    );
  }

  if (provider === "microsoft") {
    return (
      <svg className="h-5 w-5" viewBox="0 0 23 23" aria-hidden="true">
        <path fill="#f35325" d="M1 1h10v10H1z" />
        <path fill="#81bc06" d="M12 1h10v10H12z" />
        <path fill="#05a6f0" d="M1 12h10v10H1z" />
        <path fill="#ffba08" d="M12 12h10v10H12z" />
      </svg>
    );
  }

  return <Calendar className="h-5 w-5" />;
}

function ConnectionStatusBadge({
  connection,
}: {
  connection: CalendarConnection;
}) {
  const isExpired =
    connection.tokenExpiry && new Date(connection.tokenExpiry) < new Date();

  if (isExpired) {
    return (
      <Badge variant="destructive" className="gap-1 text-xs">
        <AlertCircle className="h-3 w-3" />
        Token expired
      </Badge>
    );
  }

  if (connection.isActive) {
    return (
      <Badge
        variant="secondary"
        className="gap-1 bg-green-500/10 text-green-700 dark:text-green-400 text-xs border-green-500/20"
      >
        <CheckCircle2 className="h-3 w-3" />
        Connected
      </Badge>
    );
  }

  return (
    <Badge variant="outline" className="gap-1 text-xs text-muted-foreground">
      <XCircle className="h-3 w-3" />
      Disconnected
    </Badge>
  );
}

export function CalendarConnections({
  initialConnections = [],
  justConnected,
  oauthError,
}: CalendarConnectionsProps) {
  const { toast } = useToast();
  const [connections, setConnections] = useState<CalendarConnection[]>(initialConnections);
  const [loading, setLoading] = useState(false);
  const [disconnecting, setDisconnecting] = useState<string | null>(null);
  const [isPending, startTransition] = useTransition();

  // Show toast for OAuth callback result
  useEffect(() => {
    if (justConnected) {
      const label = PROVIDER_LABELS[justConnected] ?? justConnected;
      toast({
        title: "Calendar connected",
        description: `${label} was successfully connected to your account.`,
      });
    }
    if (oauthError) {
      const appOrigin =
        typeof window !== "undefined" ? window.location.origin : "";
      const messages: Record<string, string> = {
        google_oauth_denied: "Google OAuth was denied or cancelled.",
        microsoft_oauth_denied: "Microsoft OAuth was denied or cancelled.",
        token_exchange_failed: "Token exchange failed. Please try again.",
        not_configured:
          "Calendar OAuth is not fully configured on the server (client IDs/secrets or state secret).",
        google_not_configured: `Set GOOGLE_CALENDAR_CLIENT_ID and GOOGLE_CALENDAR_CLIENT_SECRET. In Google Cloud, add redirect URI ${appOrigin || "(your app)"}/api/calendar/callback/google. Set CALENDAR_OAUTH_STATE_SECRET or ENCRYPTION_KEY.`,
        microsoft_not_configured: `Set MICROSOFT_CALENDAR_CLIENT_ID and MICROSOFT_CALENDAR_CLIENT_SECRET. In Azure, add redirect URI ${appOrigin || "(your app)"}/api/calendar/callback/microsoft. Set CALENDAR_OAUTH_STATE_SECRET or ENCRYPTION_KEY.`,
        oauth_state_not_configured:
          "OAuth state signing is not configured. Set CALENDAR_OAUTH_STATE_SECRET (32+ random characters) or reuse ENCRYPTION_KEY.",
        invalid_state:
          "This connection attempt expired or was invalid. Click Connect again.",
        unexpected: "An unexpected error occurred. Please try again.",
      };
      toast({
        title: "Calendar connection failed",
        description: messages[oauthError] ?? "An error occurred. Please try again.",
        variant: "destructive",
      });
    }
  }, [justConnected, oauthError, toast]);

  async function refreshConnections() {
    setLoading(true);
    try {
      const res = await fetch("/api/calendar/connections", { credentials: "same-origin" });
      if (res.ok) {
        const data = await res.json();
        setConnections(data);
      }
    } finally {
      setLoading(false);
    }
  }

  async function handleDisconnect(provider: string) {
    setDisconnecting(provider);
    try {
      const res = await fetch(`/api/calendar/connections?provider=${provider}`, {
        method: "DELETE",
        credentials: "same-origin",
      });

      if (res.ok) {
        setConnections((prev) => prev.filter((c) => c.provider !== provider));
        toast({
          title: "Calendar disconnected",
          description: `${PROVIDER_LABELS[provider] ?? provider} has been disconnected.`,
        });
      } else {
        toast({
          title: "Failed to disconnect",
          description: "Please try again.",
          variant: "destructive",
        });
      }
    } finally {
      setDisconnecting(null);
    }
  }

  const connectedProviders = new Set(connections.map((c) => c.provider));
  const providers: Array<"google" | "microsoft"> = ["google", "microsoft"];

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">
          Connect your calendar to enable availability checks and meeting scheduling.
        </p>
        <Button
          variant="ghost"
          size="sm"
          onClick={() => startTransition(refreshConnections)}
          disabled={loading || isPending}
          className="gap-2 text-xs"
        >
          <RefreshCw className={`h-3.5 w-3.5 ${loading || isPending ? "animate-spin" : ""}`} />
          Refresh
        </Button>
      </div>

      <div className="space-y-3">
        {providers.map((provider) => {
          const conn = connections.find((c) => c.provider === provider);
          const isConnected = connectedProviders.has(provider);
          const isDisconnecting = disconnecting === provider;

          return (
            <Card key={provider} className="border-border/60">
              <CardContent className="flex items-center gap-4 p-4">
                <div className={`flex-shrink-0 ${PROVIDER_COLORS[provider]}`}>
                  <ProviderIcon provider={provider} />
                </div>

                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2">
                    <p className="text-sm font-medium">{PROVIDER_LABELS[provider]}</p>
                    {conn && <ConnectionStatusBadge connection={conn} />}
                    {!conn && (
                      <Badge variant="outline" className="text-xs text-muted-foreground">
                        Not connected
                      </Badge>
                    )}
                  </div>
                  {conn?.calendarName && (
                    <p className="text-xs text-muted-foreground mt-0.5 truncate">
                      {conn.calendarName}
                    </p>
                  )}
                  {conn?.tokenExpiry && (
                    <p className="text-xs text-muted-foreground mt-0.5">
                      Token expires:{" "}
                      {new Date(conn.tokenExpiry) < new Date()
                        ? "Expired — reconnect to refresh"
                        : new Date(conn.tokenExpiry).toLocaleDateString()}
                    </p>
                  )}
                </div>

                <div className="flex-shrink-0">
                  {isConnected ? (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handleDisconnect(provider)}
                      disabled={isDisconnecting}
                      className="gap-2 text-destructive hover:text-destructive"
                    >
                      {isDisconnecting ? (
                        <Loader2 className="h-3.5 w-3.5 animate-spin" />
                      ) : (
                        <XCircle className="h-3.5 w-3.5" />
                      )}
                      Disconnect
                    </Button>
                  ) : (
                    <Button
                      variant="outline"
                      size="sm"
                      asChild
                      className="gap-2"
                    >
                      <a href={`/api/calendar/connect/${provider}`}>
                        <CheckCircle2 className="h-3.5 w-3.5" />
                        Connect
                      </a>
                    </Button>
                  )}
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>
    </div>
  );
}
