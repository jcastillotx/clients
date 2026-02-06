import { createClient } from "@/lib/supabase/server";
import { MeetingForm } from "@/components/meetings/meeting-form";
import { redirect } from "next/navigation";

export const metadata = {
  title: "Schedule Meeting",
  description: "Schedule a new client meeting",
};

async function getClients() {
  const supabase = createClient();

  const { data: clients, error } = await supabase
    .from("clients")
    .select("id, company_name")
    .eq("status", "active")
    .order("company_name");

  if (error) {
    console.error("Error fetching clients:", error);
    return [];
  }

  return clients;
}

async function getUsers() {
  const supabase = createClient();

  const { data: users, error } = await supabase
    .from("users")
    .select("id, name, email")
    .eq("is_active", true)
    .order("name");

  if (error) {
    console.error("Error fetching users:", error);
    return [];
  }

  return users;
}

export default async function NewMeetingPage({
  searchParams,
}: {
  searchParams: { clientId?: string; requestId?: string };
}) {
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const [clients, users] = await Promise.all([getClients(), getUsers()]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Schedule Meeting</h1>
        <p className="text-muted-foreground">Create a new meeting with your client</p>
      </div>

      <MeetingForm
        clients={clients}
        users={users}
        preselectedClientId={searchParams.clientId}
        preselectedRequestId={searchParams.requestId}
      />
    </div>
  );
}
