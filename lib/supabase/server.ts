import { createServerClient, type CookieOptions } from "@supabase/ssr";
import { cookies } from "next/headers";

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

/**
 * Create a Supabase client for Server Components and Route Handlers
 *
 * This client automatically handles:
 * - Cookie-based session management
 * - Authentication state across requests
 * - Row-Level Security (RLS) enforcement
 */
export async function createClient() {
  if (!supabaseUrl || !supabaseAnonKey) {
    throw new Error(
      "NEXT_PUBLIC_SUPABASE_URL and NEXT_PUBLIC_SUPABASE_ANON_KEY must be set in environment variables"
    );
  }

  const cookieStore = await cookies();

  return createServerClient(supabaseUrl, supabaseAnonKey, {
    cookies: {
      get(name: string) {
        return cookieStore.get(name)?.value;
      },
      set(name: string, value: string, options: CookieOptions) {
        try {
          cookieStore.set({ name, value, ...options });
        } catch (error) {
          // Cookie setting can fail in Server Components
          // This is expected and safe to ignore
        }
      },
      remove(name: string, options: CookieOptions) {
        try {
          cookieStore.set({ name, value: "", ...options });
        } catch (error) {
          // Cookie removal can fail in Server Components
          // This is expected and safe to ignore
        }
      },
    },
  });
}

/**
 * Create a Supabase admin client with service role key
 *
 * WARNING: This bypasses Row-Level Security (RLS)!
 * Only use in secure server-side contexts like:
 * - Admin operations
 * - Background jobs
 * - Data migrations
 */
export function createAdminClient() {
  const serviceKey = process.env.SUPABASE_SERVICE_KEY;
  if (!supabaseUrl || !serviceKey) {
    throw new Error(
      "NEXT_PUBLIC_SUPABASE_URL and SUPABASE_SERVICE_KEY must be set in environment variables"
    );
  }

  return createServerClient(supabaseUrl, serviceKey, {
    cookies: {
      get() {
        return "";
      },
      set() {},
      remove() {},
    },
  });
}
