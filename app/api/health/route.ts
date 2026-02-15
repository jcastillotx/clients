import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";

/**
 * Health check endpoint
 * Returns system status for the top bar indicator
 */
export async function GET() {
  try {
    const supabase = await createClient();
    
    // Check database connection
    const { error } = await supabase.from("clients").select("id").limit(1).single();
    
    if (error && error.code !== "PGRST116") {
      // PGRST116 is "no rows returned" which is fine
      throw error;
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
    console.error("Health check failed:", error);
    return NextResponse.json(
      { 
        status: "degraded",
        timestamp: new Date().toISOString(),
        error: error instanceof Error ? error.message : "Unknown error"
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
    await supabase.from("clients").select("id").limit(1);
    return new Response(null, { status: 200 });
  } catch (error) {
    return new Response(null, { status: 503 });
  }
}
