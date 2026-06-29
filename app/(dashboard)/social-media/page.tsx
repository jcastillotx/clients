import { createClient } from "@/lib/supabase/server";
import { SocialMediaDashboard } from "@/components/social/social-media-dashboard";

export const metadata = {
  title: "Social Media | KRE8IV",
  description: "Manage your social media accounts and schedule posts",
};

interface SearchParams {
  clientId?: string;
  status?: string;
}

/**
 * Social Media Management page (Server Component)
 *
 * Displays connected social accounts and post scheduler.
 */
export default async function SocialMediaPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return null; // Middleware will redirect
  }

  // Fetch user's client ID and admin status
  const { data: userData } = await supabase.from("users").select("client_id, is_super_admin").eq("id", user.id).single();

  const clientId = resolvedSearchParams.clientId || userData?.client_id || null;
  const isAdmin = Boolean(userData?.is_super_admin);

  // Admins without a client_id see all data platform-wide
  let accountsQuery = supabase
    .from("social_accounts")
    .select("*")
    .is("deleted_at", null)
    .order("created_at", { ascending: false });

  if (clientId) {
    accountsQuery = accountsQuery.eq("client_id", clientId);
  }

  let postsQuery = supabase
    .from("social_posts")
    .select(`*, account:social_accounts(id, platform, account_name), creator:users(id, name, email)`)
    .is("deleted_at", null)
    .order("scheduled_for", { ascending: false })
    .limit(20);

  if (clientId) {
    postsQuery = postsQuery.eq("social_accounts.client_id", clientId);
  }

  // Fetch social accounts
  const { data: accounts, error: accountsError } = await accountsQuery;

  // Fetch recent posts
  const { data: posts, error: postsError } = await postsQuery;

  if (accountsError || postsError) {
    console.error("Error fetching social media data:", accountsError || postsError);
  }

  return (
    <div className="flex flex-col gap-8 p-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Social Media</h1>
          <p className="text-muted-foreground">
            {isAdmin && !clientId ? "Showing all clients — select a client to filter" : "Manage your social media accounts and schedule posts"}
          </p>
        </div>
      </div>

      <SocialMediaDashboard
        clientId={clientId || ""}
        userId={user.id}
        initialAccounts={accounts || []}
        initialPosts={posts || []}
      />
    </div>
  );
}
