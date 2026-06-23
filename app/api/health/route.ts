import { createClient } from "@/lib/supabase/server";
import { apiSuccess } from "@/lib/api/response";
import { logger } from "@/lib/logger";

/**
 * Health check endpoint
 * Returns system status for the top bar indicator
 */
export async function GET(request: Request) {
  try {
    const supabase = await createClient();
    
    // Check database connection
    const { error } = await supabase.from("clients").select("id").limit(1);
    
    // Check if query failed (Supabase returns errors in error field, doesn't throw)
    if (error) {
      // PGRST116 = "no rows returned" which is acceptable (table exists but empty)
      if (error.code !== "PGRST116") {
        logger.error("Health check database error", error, { code: error.code });
        return apiSuccess(request, {
          status: "degraded",
          timestamp: new Date().toISOString(),
          services: {
            database: "error",
            auth: "unknown",
          },
          code: error.code,
          errorMessage: error.message,
        }, { status: 503 });
      }
    }

    return apiSuccess(request, {
      status: "operational",
      timestamp: new Date().toISOString(),
      services: {
        database: "ok",
        auth: "ok",
      },
    });
  } catch (error) {
    logger.error("Health check exception", error);
    return apiSuccess(request, {
      status: "down",
      timestamp: new Date().toISOString(),
      services: {
        database: "down",
        auth: "unknown",
      },
      errorMessage: "Health check failed",
    }, { status: 503 });
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
