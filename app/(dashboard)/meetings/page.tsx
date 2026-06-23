import { Suspense } from "react";
import { createClient } from "@/lib/supabase/server";
import { Button } from "@/components/ui/button";
import { MeetingsTabs } from "@/components/meetings/meetings-tabs";
import Link from "next/link";
import { Plus } from "lucide-react";

export const metadata = {
  title: "Meetings",
  description: "Manage your client meetings and schedules",
};

async function getMeetings() {
  const supabase = await createClient();

  const { data: meetings, error } = await supabase
    .from("meetings")
    .select(
      `
      *,
      client:clients(id, company_name),
      creator:users!meetings_created_by_fkey(id, name, email, avatar)
    `,
    )
    .is("deleted_at", null)
    .order("scheduled_at", { ascending: true });

  if (error) {
    console.error("Error fetching meetings:", error);
    return [];
  }

  return meetings;
}

export default async function MeetingsPage({
  searchParams,
}: {
  searchParams: Promise<{ tab?: string }>;
}) {
  const { tab } = await searchParams;
  const defaultTab = tab === "list" ? "list" : "calendar";
  const meetings = await getMeetings();

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Meetings</h1>
          <p className="text-muted-foreground">Schedule and manage client meetings</p>
        </div>
        <Button asChild>
          <Link href="/meetings/new">
            <Plus className="mr-2 h-4 w-4" />
            Schedule Meeting
          </Link>
        </Button>
      </div>

      <MeetingsTabs meetings={meetings} defaultTab={defaultTab} />
    </div>
  );
}
