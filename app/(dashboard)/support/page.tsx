import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { hasAnyRole } from "@/lib/rbac/permissions";
import { SupportTicketList } from "@/components/support/ticket-list";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";
import Link from "next/link";

interface SearchParams {
  search?: string;
  status?: string;
  priority?: string;
  category?: string;
  sortBy?: string;
  sortOrder?: "asc" | "desc";
}

/**
 * Support Tickets page (Server Component)
 *
 * Fetches support tickets on the server for better performance and SEO.
 * RLS automatically filters to only show tickets for user's client.
 */
export default async function SupportTicketsPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    throw new Error("Unauthorized");
  }

  const canViewAllTickets = await hasAnyRole(["super_admin", "admin", "account_manager", "staff"], {
    supabase,
    userId: user.id,
  });

  const adminClient = canViewAllTickets ? createAdminClientIfAvailable() : null;
  const dbClient = adminClient ?? supabase;

  // Server-side data fetching
  let query = dbClient
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
          <h1 className="text-3xl font-bold tracking-tight">Support Tickets</h1>
          <p className="text-muted-foreground mt-1">Manage and track support tickets with SLA monitoring</p>
        </div>
        <Button asChild>
          <Link href="/support/new">
            <Plus className="mr-2 h-4 w-4" />
            New Ticket
          </Link>
        </Button>
      </div>

      {/* Client Component for interactivity */}
      <SupportTicketList initialData={tickets || []} />
    </div>
  );
}
