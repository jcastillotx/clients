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
export default async function SocialMediaPage({ searchParams }: { searchParams: SearchParams }) {
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return null; // Middleware will redirect
  }

  // Fetch user's client ID
  const { data: userData } = await supabase.from("users").select("client_id").eq("id", user.id).single();

  const clientId = searchParams.clientId || userData?.client_id;

  if (!clientId) {
    return (
      <div className="flex flex-col gap-8 p-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Social Media</h1>
            <p className="text-muted-foreground">No client selected</p>
          </div>
        </div>
      </div>
    );
  }

  // Fetch social accounts
  const { data: accounts, error: accountsError } = await supabase
    .from("social_accounts")
    .select("*")
    .eq("client_id", clientId)
    .is("deleted_at", null)
    .order("created_at", { ascending: false });

  // Fetch recent posts
  const { data: posts, error: postsError } = await supabase
    .from("social_posts")
    .select(
      `
      *,
      account:social_accounts(id, platform, account_name),
      creator:users(id, name, email)
    `,
    )
    .eq("social_accounts.client_id", clientId)
    .is("deleted_at", null)
    .order("scheduled_for", { ascending: false })
    .limit(20);

  if (accountsError || postsError) {
    console.error("Error fetching social media data:", accountsError || postsError);
  }

  return (
    <div className="flex flex-col gap-8 p-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Social Media</h1>
          <p className="text-muted-foreground">Manage your social media accounts and schedule posts</p>
        </div>
      </div>

      <SocialMediaDashboard
        clientId={clientId}
        userId={user.id}
        initialAccounts={accounts || []}
        initialPosts={posts || []}
      />
    </div>
  );
}
