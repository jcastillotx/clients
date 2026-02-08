import { completeSupabaseAuthFromUrl } from "@/lib/supabase/auth-redirect";

export async function GET(request: Request) {
  return completeSupabaseAuthFromUrl(request);
}
