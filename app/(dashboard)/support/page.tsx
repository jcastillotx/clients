import { createClient } from "@/lib/supabase/server";
import { SupportTicketList } from "@/components/support/ticket-list";
import { Button } from "@/components/ui/button";
import { PlusIcon } from "@radix-ui/react-icons";
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
export default async function SupportTicketsPage({ searchParams }: { searchParams: SearchParams }) {
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    throw new Error("Unauthorized");
  }

  // Server-side data fetching
  let query = supabase
    .from("support_tickets")
    .select(
      `
      *,
      client:clients(company_name),
      creator:users!support_tickets_created_by_fkey(name, avatar),
      assigned_user:users!support_tickets_assigned_to_fkey(name, avatar)
    `,
    )
    .order(searchParams.sortBy || "created_at", {
      ascending: searchParams.sortOrder === "asc",
    });

  // Apply search filter
  if (searchParams.search) {
    query = query.or(`subject.ilike.%${searchParams.search}%,ticket_number.ilike.%${searchParams.search}%`);
  }

  // Apply status filter
  if (searchParams.status) {
    query = query.eq("status", searchParams.status);
  }

  // Apply priority filter
  if (searchParams.priority) {
    query = query.eq("priority", searchParams.priority);
  }

  // Apply category filter
  if (searchParams.category) {
    query = query.eq("category", searchParams.category);
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
            <PlusIcon className="mr-2 h-4 w-4" />
            New Ticket
          </Link>
        </Button>
      </div>

      {/* Client Component for interactivity */}
      <SupportTicketList initialData={tickets || []} />
    </div>
  );
}
