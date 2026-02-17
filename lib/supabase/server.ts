import { createServerClient } from "@supabase/ssr";
import { cookies } from "next/headers";
import { getSupabaseCookieOptions } from "@/lib/supabase/cookie-options";

/**
 * Create a Supabase client for Server Components and Route Handlers.
 * Always create a new client within each function — do not store in a global.
 */
export async function createClient() {
  const cookieStore = await cookies();

  return createServerClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!,
    {
      cookieOptions: getSupabaseCookieOptions(),
      cookies: {
        getAll() {
          return cookieStore.getAll();
        },
        setAll(cookiesToSet) {
          try {
            cookiesToSet.forEach(({ name, value, options }) =>
              cookieStore.set(name, value, options)
            );
          } catch {
            // The "setAll" method was called from a Server Component.
            // This can be ignored if you have middleware refreshing
            // user sessions.
          }
        },
      },
    }
  );
}

function getServiceRoleKey() {
  return process.env.SUPABASE_SERVICE_ROLE_KEY ?? process.env.SUPABASE_SERVICE_KEY ?? null;
}

function createServiceRoleClient(serviceRoleKey: string) {
  return createServerClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    serviceRoleKey,
    {
      cookieOptions: getSupabaseCookieOptions(),
      cookies: {
        getAll() {
          return [];
        },
        setAll() {},
      },
    }
  );
}

/**
 * Create a Supabase admin client with service role key.
 * WARNING: This bypasses Row-Level Security (RLS)!
 */
export function createAdminClient() {
  const serviceRoleKey = getServiceRoleKey();

  if (!serviceRoleKey) {
    throw new Error("Missing SUPABASE_SERVICE_ROLE_KEY (or SUPABASE_SERVICE_KEY) for admin operations");
  }

  return createServiceRoleClient(serviceRoleKey);
}

/**
 * Create an admin client only when a service role key is configured.
 * Returns null in environments where service-role credentials are unavailable.
 */
export function createAdminClientIfAvailable() {
  const serviceRoleKey = getServiceRoleKey();
  if (!serviceRoleKey) return null;
  return createServiceRoleClient(serviceRoleKey);
}
