import { createClient } from "@/lib/supabase/server";
import { ProposalList } from "@/components/proposals/proposal-list";
import { redirect } from "next/navigation";

export const metadata = {
  title: "Proposals | KRE8IV",
  description: "Manage your proposals",
};

interface SearchParams {
  search?: string;
  status?: string;
  page?: string;
}

/**
 * Proposals list page (Server Component)
 *
 * Fetches proposals on the server with RLS automatically filtering by user's access.
 */
export default async function ProposalsPage({ searchParams }: { searchParams: SearchParams }) {
  const supabase = createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Build query with filters
  let query = supabase
    .from("proposals")
    .select(
      `
      *,
      client:clients(id, company_name),
      creator:users!proposals_created_by_fkey(id, name)
    `,
      { count: "exact" },
    )
    .order("created_at", { ascending: false });

  // Apply search filter (title or client name)
  if (searchParams.search) {
    query = query.or(`title.ilike.%${searchParams.search}%`);
  }

  // Apply status filter
  if (searchParams.status && searchParams.status !== "all") {
    query = query.eq("status", searchParams.status);
  }

  // Pagination
  const page = parseInt(searchParams.page || "1");
  const pageSize = 20;
  const from = (page - 1) * pageSize;
  const to = from + pageSize - 1;

  query = query.range(from, to);

  const { data: proposals, error, count } = await query;

  if (error) {
    console.error("Error fetching proposals:", error);
    return <div>Error loading proposals</div>;
  }

  // Calculate proposal stats
  const { data: allProposals } = await supabase.from("proposals").select("total_amount, status");

  const stats = {
    totalProposals: allProposals?.length || 0,
    acceptedValue:
      allProposals?.filter((p) => p.status === "accepted").reduce((sum, p) => sum + Number(p.total_amount), 0) || 0,
    pendingValue:
      allProposals
        ?.filter((p) => p.status === "sent" || p.status === "viewed")
        .reduce((sum, p) => sum + Number(p.total_amount), 0) || 0,
    acceptanceRate: allProposals?.length
      ? ((allProposals.filter((p) => p.status === "accepted").length / allProposals.length) * 100).toFixed(1)
      : 0,
  };

  return (
    <div className="flex flex-col gap-8 p-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Proposals</h1>
          <p className="text-muted-foreground">Create and manage business proposals</p>
        </div>
      </div>

      <ProposalList
        initialData={proposals || []}
        totalCount={count || 0}
        currentPage={page}
        pageSize={pageSize}
        stats={stats}
      />
    </div>
  );
}
