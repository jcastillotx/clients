import { createClient } from "@/lib/supabase/server";
import { SupportTicketForm } from "@/components/support/ticket-form";
import { redirect } from "next/navigation";

/**
 * New Support Ticket page (Server Component)
 *
 * Allows users to create new support tickets.
 */
export default async function NewSupportTicketPage() {
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Fetch staff users for assignment (optional)
  const { data: staffUsers } = await supabase.from("users").select("id, name, email").eq("role", "staff").order("name");

  return (
    <div className="container mx-auto py-8 max-w-4xl">
      <div className="mb-6">
        <h1 className="text-3xl font-bold tracking-tight">Create Support Ticket</h1>
        <p className="text-muted-foreground mt-1">Submit a new support ticket and track its progress</p>
      </div>

      <SupportTicketForm staffUsers={staffUsers || []} />
    </div>
  );
}
