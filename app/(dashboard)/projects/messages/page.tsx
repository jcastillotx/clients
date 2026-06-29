import Link from "next/link";
import { formatDistanceToNow } from "date-fns";
import { createClient } from "@/lib/supabase/server";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { MessageSquareText, ArrowRight } from "lucide-react";

export const metadata = {
  title: "Project Messages",
  description: "View and access messaging threads across all your project requests",
};

interface ProjectWithMessages {
  id: string;
  title: string;
  status: string;
  created_at: string;
  client: { id: string; company_name: string } | null;
  messageCount: number;
  latestMessageAt: string | null;
}

export default async function ProjectMessagesPage() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  const { data: dbUser } = user
    ? await supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle()
    : { data: null };

  // Use DB as authoritative source for admin status (not JWT metadata)
  const isAdmin = Boolean(dbUser?.is_super_admin);

  let query = supabase
    .from("requests")
    .select("id, title, status, created_at, client:clients(id, company_name)")
    .contains("custom_fields", { type: "project" })
    .order("created_at", { ascending: false });

  if (!isAdmin && dbUser?.client_id) {
    query = query.eq("client_id", dbUser.client_id);
  }

  const { data: rows } = await query;
  const requests = (rows || []) as unknown as Array<{
    id: string;
    title: string;
    status: string;
    created_at: string;
    client: { id: string; company_name: string } | { id: string; company_name: string }[] | null;
  }>;

  const projects: ProjectWithMessages[] = await Promise.all(
    requests.map(async (row) => {
      const [{ count }, { data: latest }] = await Promise.all([
        supabase
          .from("request_comments")
          .select("id", { count: "exact", head: true })
          .eq("request_id", row.id),
        supabase
          .from("request_comments")
          .select("created_at")
          .eq("request_id", row.id)
          .order("created_at", { ascending: false })
          .limit(1)
          .maybeSingle(),
      ]);

      return {
        id: row.id,
        title: row.title,
        status: row.status,
        created_at: row.created_at,
        client: Array.isArray(row.client) ? row.client[0] || null : row.client,
        messageCount: count || 0,
        latestMessageAt: latest?.created_at || null,
      };
    }),
  );

  const sorted = projects.sort((a, b) => {
    const aTime = a.latestMessageAt || a.created_at;
    const bTime = b.latestMessageAt || b.created_at;
    return new Date(bTime).getTime() - new Date(aTime).getTime();
  });

  return (
    <div className="container mx-auto space-y-6 py-8">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Project Messages</h1>
        <p className="mt-1 text-muted-foreground">
          View and continue messaging threads across all your project requests.
        </p>
      </div>

      {sorted.length === 0 ? (
        <Card>
          <CardContent className="py-10 text-center">
            <MessageSquareText className="mx-auto mb-3 h-10 w-10 text-muted-foreground" />
            <h3 className="text-lg font-semibold">No project conversations yet</h3>
            <p className="mt-1 text-sm text-muted-foreground">
              Messages will appear here once you start communicating on a project request.
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4">
          {sorted.map((project) => (
            <Card key={project.id} className="transition hover:shadow-sm">
              <CardHeader className="pb-3">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <CardTitle className="text-lg">{project.title}</CardTitle>
                    <CardDescription>
                      {project.client?.company_name || "Unknown client"}
                    </CardDescription>
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge variant="secondary">
                      <MessageSquareText className="mr-1 h-3 w-3" />
                      {project.messageCount} {project.messageCount === 1 ? "message" : "messages"}
                    </Badge>
                    <Badge variant="outline">{project.status.replace(/_/g, " ")}</Badge>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div className="flex items-center justify-between">
                  <p className="text-sm text-muted-foreground">
                    {project.latestMessageAt
                      ? `Last message ${formatDistanceToNow(new Date(project.latestMessageAt), { addSuffix: true })}`
                      : "No messages yet"}
                  </p>
                  <Button variant="outline" size="sm" asChild>
                    <Link href={`/projects/requests/${project.id}?tab=messaging`}>
                      Open Messages
                      <ArrowRight className="ml-2 h-4 w-4" />
                    </Link>
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
