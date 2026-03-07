import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { redirect } from "next/navigation";
import { SupportTicketList } from "@/components/support/ticket-list";

interface SearchParams {
  search?: string;
  status?: string;
  priority?: string;
  category?: string;
  sortBy?: string;
  sortOrder?: "asc" | "desc";
}

/**
 * Admin Support Tickets page
 *
 * Uses the admin client to bypass RLS and show tickets from ALL clients.
 */
export default async function AdminTicketsPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const resolvedSearchParams = await searchParams;
  // Check admin access
  const isAdmin = await hasPermission("admin.access");
  if (!isAdmin) {
    redirect("/dashboard");
  }

  // Use admin client to bypass RLS and see all tickets across clients
  const adminClient = createAdminClientIfAvailable();

  if (!adminClient) {
    throw new Error("Missing Supabase service role credentials for admin ticket queries");
  }

  // Server-side data fetching across all clients
  let query = adminClient
    .from("support_tickets")
    .select(
      `
      *,
      client:clients(company_name),
      creator:users!support_tickets_created_by_fkey(name, avatar),
      assigned_user:users!support_tickets_assigned_to_fkey(name, avatar)
    `,
    )
    .is("deleted_at", null)
    .order(resolvedSearchParams.sortBy || "created_at", {
      ascending: resolvedSearchParams.sortOrder === "asc",
    });

  // Apply search filter
  if (resolvedSearchParams.search) {
    query = query.or(`subject.ilike.%${resolvedSearchParams.search}%,ticket_number.ilike.%${resolvedSearchParams.search}%`);
  }

  // Apply status filter
  if (resolvedSearchParams.status) {
    query = query.eq("status", resolvedSearchParams.status);
  }

  // Apply priority filter
  if (resolvedSearchParams.priority) {
    query = query.eq("priority", resolvedSearchParams.priority);
  }

  // Apply category filter
  if (resolvedSearchParams.category) {
    query = query.eq("category", resolvedSearchParams.category);
  }

  const { data: tickets, error } = await query;

  if (error) {
    console.error("Error fetching tickets:", error);
    throw new Error("Failed to fetch support tickets");
  }

  return (
    <div className="container mx-auto py-8">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">All Support Tickets</h1>
          <p className="text-muted-foreground mt-1">View and manage support tickets across all clients</p>
        </div>
      </div>

      <SupportTicketList initialData={tickets || []} />
    </div>
  );
}
