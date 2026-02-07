import { createBrowserClient } from "@supabase/ssr";

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

/**
 * Create a Supabase client for Client Components
 *
 * This client automatically handles:
 * - Cookie-based session management in the browser
 * - Real-time subscriptions
 * - Client-side authentication
 */
export function createClient() {
  if (!supabaseUrl || !supabaseAnonKey) {
    throw new Error(
      "NEXT_PUBLIC_SUPABASE_URL and NEXT_PUBLIC_SUPABASE_ANON_KEY must be set in environment variables"
    );
  }

  return createBrowserClient(supabaseUrl, supabaseAnonKey);
}
