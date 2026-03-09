"use client";

import { useCallback, useEffect, useMemo, useRef } from "react";
import { useRouter } from "next/navigation";
import { createClient } from "@/lib/supabase/client";

interface RequestRealtimeProps {
  requestId: string;
}

/**
 * Real-time subscription component for request updates
 *
 * This component doesn't render any UI - it just subscribes to
 * Supabase Realtime changes and refreshes server-rendered request data.
 */
export function RequestRealtime({ requestId }: RequestRealtimeProps) {
  const router = useRouter();
  const supabase = useMemo(() => createClient(), []);
  const refreshTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Debounce router.refresh() to avoid redundant SSR revalidations when
  // multiple rapid database changes arrive in quick succession.
  const debouncedRefresh = useCallback(() => {
    if (refreshTimeoutRef.current) {
      clearTimeout(refreshTimeoutRef.current);
    }
    refreshTimeoutRef.current = setTimeout(() => {
      router.refresh();
    }, 300);
  }, [router]);

  useEffect(() => {
    // Subscribe to changes on this specific request
    const channel = supabase
      .channel(`request:${requestId}`)
      .on(
        "postgres_changes",
        {
          event: "*",
          schema: "public",
          table: "requests",
          filter: `id=eq.${requestId}`,
        },
        () => {
          debouncedRefresh();
        },
      )
      .subscribe();

    // Cleanup subscription and any pending debounce on unmount
    return () => {
      supabase.removeChannel(channel);
      if (refreshTimeoutRef.current) {
        clearTimeout(refreshTimeoutRef.current);
      }
    };
  }, [requestId, debouncedRefresh, supabase]);

  return null; // This component doesn't render anything
}
