import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { logger } from "@/lib/logger";

/**
 * Health check endpoint
 * Returns system status for the top bar indicator
 */
export async function GET() {
  try {
    const supabase = await createClient();
    
    // Check database connection
    const { data, error } = await supabase.from("clients").select("id").limit(1);
    
    // Check if query failed (Supabase returns errors in error field, doesn't throw)
    if (error) {
      // PGRST116 = "no rows returned" which is acceptable (table exists but empty)
      if (error.code !== "PGRST116") {
        logger.error("Health check database error", error, { code: error.code });
        return NextResponse.json(
          { 
            status: "degraded",
            timestamp: new Date().toISOString(),
            services: {
              database: "error",
              auth: "unknown"
            },
            error: error.message,
            code: error.code
          },
          { status: 503 }
        );
      }
    }

    return NextResponse.json({ 
      status: "operational",
      timestamp: new Date().toISOString(),
      services: {
        database: "ok",
        auth: "ok"
      }
    });
  } catch (error) {
    logger.error("Health check exception", error);
    return NextResponse.json(
      { 
        status: "down",
        timestamp: new Date().toISOString(),
        services: {
          database: "down",
          auth: "unknown"
        },
        error: "Health check failed"
      },
      { status: 503 }
    );
  }
}

/**
 * HEAD request for lightweight status check
 */
export async function HEAD() {
  try {
    const supabase = await createClient();
    
    // Actually check if the query succeeded
    const { error } = await supabase.from("clients").select("id").limit(1);
    
    // Return 503 if query failed (even if no exception thrown)
    if (error) {
      logger.warn("Health check HEAD failed", { code: error.code });
      return new Response(null, { status: 503 });
    }

    return new Response(null, { status: 200 });
  } catch (error) {
    logger.error("Health check HEAD exception", error);
    return new Response(null, { status: 503 });
  }
}
