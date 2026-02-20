"use client";

import { useEffect, useMemo } from "react";
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
          // Refresh server-rendered request detail when row changes
          router.refresh();
        },
      )
      .subscribe();

    // Cleanup subscription on unmount
    return () => {
      supabase.removeChannel(channel);
    };
  }, [requestId, router, supabase]);

  return null; // This component doesn't render anything
}
