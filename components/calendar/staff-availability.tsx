"use client";

import { useState, useEffect, useCallback } from "react";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { CheckCircle2, XCircle, HelpCircle, Loader2 } from "lucide-react";
import type { UserAvailability } from "@/lib/db/schema/calendar-integrations";

interface StaffAvailabilityProps {
  /** User IDs to check availability for. */
  userIds: string[];
  /** ISO 8601 start datetime string. */
  start: string;
  /** ISO 8601 end datetime string. */
  end: string;
}

function StatusBadge({ status }: { status: UserAvailability["status"] }) {
  if (status === "free") {
    return (
      <Badge
        variant="secondary"
        className="gap-1 bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20 text-xs"
      >
        <CheckCircle2 className="h-3 w-3" />
        Free
      </Badge>
    );
  }
  if (status === "busy") {
    return (
      <Badge variant="destructive" className="gap-1 text-xs">
        <XCircle className="h-3 w-3" />
        Busy
      </Badge>
    );
  }
  return (
    <Badge variant="outline" className="gap-1 text-xs text-muted-foreground">
      <HelpCircle className="h-3 w-3" />
      No calendar
    </Badge>
  );
}

export function StaffAvailability({ userIds, start, end }: StaffAvailabilityProps) {
  const [availability, setAvailability] = useState<UserAvailability[]>([]);
  const [loading, setLoading] = useState(false);

  const checkAvailability = useCallback(async () => {
    if (userIds.length === 0 || !start || !end) {
      setAvailability([]);
      return;
    }

    setLoading(true);
    try {
      const params = new URLSearchParams({
        userIds: userIds.join(","),
        start,
        end,
      });
      const res = await fetch(`/api/calendar/availability?${params}`, {
        credentials: "same-origin",
      });
      if (res.ok) {
        const data = await res.json();
        setAvailability(data.availability ?? []);
      }
    } catch {
      // Silently fail — availability is a nice-to-have
    } finally {
      setLoading(false);
    }
  }, [userIds.join(","), start, end]); // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => {
    checkAvailability();
  }, [checkAvailability]);

  if (userIds.length === 0) return null;

  return (
    <div className="space-y-2">
      <div className="flex items-center gap-2 text-xs font-medium text-muted-foreground uppercase tracking-wide">
        Availability
        {loading && <Loader2 className="h-3 w-3 animate-spin" />}
      </div>

      {loading && availability.length === 0 && (
        <div className="flex items-center gap-2 text-xs text-muted-foreground py-1">
          <Loader2 className="h-3 w-3 animate-spin" />
          Checking calendars…
        </div>
      )}

      <div className="space-y-2">
        {availability.map((a) => (
          <div key={a.userId} className="flex items-center gap-2.5">
            <Avatar className="h-6 w-6">
              <AvatarFallback className="text-[10px]">
                {a.name
                  ? a.name
                      .split(" ")
                      .map((n) => n[0])
                      .join("")
                      .toUpperCase()
                      .slice(0, 2)
                  : "?"}
              </AvatarFallback>
            </Avatar>
            <span className="text-sm flex-1 truncate">{a.name}</span>
            <StatusBadge status={a.status} />
          </div>
        ))}
      </div>

      {!loading && availability.length > 0 && (
        <p className="text-[11px] text-muted-foreground">
          {availability.filter((a) => a.status === "free").length} of {availability.length} staff
          available
        </p>
      )}
    </div>
  );
}
