"use client";

import { useEffect } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { createClient } from "@/lib/supabase/client";

interface RequestRealtimeProps {
  requestId: string;
}

/**
 * Real-time subscription component for request updates
 *
 * This component doesn't render any UI - it just subscribes to
 * Supabase Realtime changes and invalidates React Query cache.
 */
export function RequestRealtime({ requestId }: RequestRealtimeProps) {
  const queryClient = useQueryClient();
  const supabase = createClient();

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
          // Invalidate request query to trigger refetch
          queryClient.invalidateQueries({ queryKey: ["request", requestId] });
        },
      )
      .subscribe();

    // Cleanup subscription on unmount
    return () => {
      supabase.removeChannel(channel);
    };
  }, [requestId, queryClient, supabase]);

  return null; // This component doesn't render anything
}
