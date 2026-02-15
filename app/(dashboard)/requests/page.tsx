import { createClient } from "@/lib/supabase/server";
import { RequestList } from "@/components/requests/request-list";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";
import Link from "next/link";

interface SearchParams {
  search?: string;
  status?: string;
  sortBy?: string;
  sortOrder?: "asc" | "desc";
}

/**
 * Requests page (Server Component)
 *
 * Fetches requests on the server for better performance and SEO.
 * RLS automatically filters to only show requests for user's client.
 */
export default async function RequestsPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();

  // Authentication is handled by the dashboard layout - no redundant check needed.
  // Server-side data fetching (no loading state needed!)
  let query = supabase
    .from("requests")
    .select(
      `
      *,
      client:clients(company_name),
      assigned_user:users!requests_assigned_to_fkey(name, avatar)
    `,
    )
    .order(resolvedSearchParams.sortBy || "created_at", {
      ascending: resolvedSearchParams.sortOrder === "asc",
    });

  // Apply search filter
  if (resolvedSearchParams.search) {
    query = query.or(
      `title.ilike.%${resolvedSearchParams.search}%,description.ilike.%${resolvedSearchParams.search}%`,
    );
  }

  // Apply status filter
  if (resolvedSearchParams.status) {
    query = query.eq("status", resolvedSearchParams.status);
  }

  const { data: requests, error } = await query;

  if (error) {
    console.error("Error fetching requests:", error);
    return (
      <div className="container mx-auto py-8">
        <div className="flex flex-col items-center justify-center min-h-[400px] gap-4">
          <div className="text-destructive font-semibold text-lg">Error Loading Requests</div>
          <div className="text-sm text-muted-foreground max-w-md text-center">
            {error.message || "An unexpected error occurred"}
          </div>
          <pre className="text-xs bg-muted p-4 rounded max-w-2xl overflow-auto">
            {JSON.stringify(error, null, 2)}
          </pre>
          <p className="text-xs text-muted-foreground mt-4">
            This might be an RLS policy issue. Check the server logs for details.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto py-8">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Service Requests</h1>
          <p className="text-muted-foreground mt-1">Manage and track client service requests</p>
        </div>
        <Button asChild>
          <Link href="/requests/new">
            <Plus className="mr-2 h-4 w-4" />
            New Request
          </Link>
        </Button>
      </div>

      {/* Client Component for interactivity */}
      <RequestList initialData={requests || []} />
    </div>
  );
}
