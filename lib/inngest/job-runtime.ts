import { createAdminClient } from "@/lib/supabase/server";

/** Scheduled handlers cannot rely on a browser session. Only signed Inngest
 * requests reach them in production; the service role is required for system work.
 */
export function createJobClient() {
  return createAdminClient();
}

export function scheduledJobsEnabled() {
  return process.env.BACKGROUND_SCHEDULES_ENABLED === "true";
}

export function assertJobResult(
  error: { message: string } | null,
  operation: string,
): void {
  if (error) throw new Error(`${operation}: ${error.message}`);
}
