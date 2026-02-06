import { createClient } from "@/lib/supabase/server";
import { MeetingDetail } from "@/components/meetings/meeting-detail";
import { notFound, redirect } from "next/navigation";

export const metadata = {
  title: "Meeting Details",
  description: "View and manage meeting details",
};

async function getMeeting(id: string) {
  const supabase = createClient();

  const { data: meeting, error } = await supabase
    .from("meetings")
    .select(
      `
      *,
      client:clients(id, company_name, email, phone),
      creator:users!meetings_created_by_fkey(id, name, email, avatar),
      request:requests(id, title),
      notes:meeting_notes(*, creator:users(id, name, avatar)),
      attendeeRecords:meeting_attendees(*, user:users(id, name, email, avatar))
    `,
    )
    .eq("id", id)
    .is("deleted_at", null)
    .single();

  if (error) {
    console.error("Error fetching meeting:", error);
    return null;
  }

  return meeting;
}

async function getUsers() {
  const supabase = createClient();

  const { data: users, error } = await supabase
    .from("users")
    .select("id, name, email, avatar")
    .eq("is_active", true)
    .order("name");

  if (error) {
    console.error("Error fetching users:", error);
    return [];
  }

  return users;
}

export default async function MeetingDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const [meeting, users] = await Promise.all([getMeeting(id), getUsers()]);

  if (!meeting) {
    notFound();
  }

  return <MeetingDetail meeting={meeting} users={users} />;
}
