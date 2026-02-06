import { createBrowserClient } from "@supabase/ssr";

/**
 * Create a Supabase client for Client Components
 *
 * This client automatically handles:
 * - Cookie-based session management in the browser
 * - Real-time subscriptions
 * - Client-side authentication
 */
export function createClient() {
  return createBrowserClient(process.env.NEXT_PUBLIC_SUPABASE_URL!, process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!);
}
