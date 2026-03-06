import Link from "next/link";
import { formatDistanceToNow } from "date-fns";
import { createClient } from "@/lib/supabase/server";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { MessageCircle, ArrowRight, Star } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

export const metadata = {
  title: "Project Feedback",
  description: "View feedback and reviews across all your project requests",
};

interface FeedbackEntry {
  id: string;
  content: string;
  created_at: string;
  request_id: string;
  user: { id: string; name: string; avatar?: string | null } | null;
}

interface ProjectFeedbackGroup {
  requestId: string;
  title: string;
  status: string;
  client: { id: string; company_name: string } | null;
  entries: Array<{
    id: string;
    rating: number | null;
    message: string;
    createdAt: string;
    user: { id: string; name: string; avatar?: string | null } | null;
  }>;
}

function parseFeedback(content: string) {
  const match = content.match(/^\[rating:(\d)\]\s*/i);
  if (!match) {
    return { rating: null as number | null, message: content };
  }
  return {
    rating: Number(match[1]),
    message: content.replace(/^\[rating:(\d)\]\s*/i, "").trim(),
  };
}

export default async function ProjectFeedbackPage() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  const [{ data: dbUser }, { data: roleRows }] = user
    ? await Promise.all([
        supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle(),
        supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
      ])
    : [{ data: null }, { data: [] }];

  const metadataRole = String(user?.user_metadata?.role || user?.user_metadata?.app_role || "").toLowerCase();
  const roleNames = (roleRows || []).map((row: unknown) => {
    const roleRow = row as { role?: { name?: string } | Array<{ name?: string }> };
    if (Array.isArray(roleRow.role)) {
      return String(roleRow.role[0]?.name || "").toLowerCase();
    }
    return String(roleRow.role?.name || "").toLowerCase();
  });

  const isAdmin = Boolean(
    dbUser?.is_super_admin ||
      user?.user_metadata?.is_super_admin === true ||
      metadataRole === "admin" ||
      metadataRole === "super_admin" ||
      roleNames.includes("admin") ||
      roleNames.includes("super_admin"),
  );

  let query = supabase
    .from("requests")
    .select("id, title, status, client:clients(id, company_name)")
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
    client: { id: string; company_name: string } | { id: string; company_name: string }[] | null;
  }>;

  const groups: ProjectFeedbackGroup[] = [];

  for (const row of requests) {
    const { data: comments } = await supabase
      .from("request_comments")
      .select("id, content, created_at, user:users(id, name, avatar)")
      .eq("request_id", row.id)
      .order("created_at", { ascending: false })
      .limit(5);

    if (!comments || comments.length === 0) continue;

    const entries = comments.map((comment) => {
      const parsed = parseFeedback(comment.content || "");
      const userRelation = comment.user as
        | { id: string; name: string; avatar?: string | null }
        | Array<{ id: string; name: string; avatar?: string | null }>;
      const normalizedUser = Array.isArray(userRelation) ? userRelation[0] : userRelation;
      return {
        id: comment.id,
        rating: parsed.rating,
        message: parsed.message,
        createdAt: comment.created_at,
        user: normalizedUser || null,
      };
    });

    groups.push({
      requestId: row.id,
      title: row.title,
      status: row.status,
      client: Array.isArray(row.client) ? row.client[0] || null : row.client,
      entries,
    });
  }

  return (
    <div className="container mx-auto space-y-6 py-8">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Project Feedback</h1>
        <p className="mt-1 text-muted-foreground">
          Review notes, ratings, and feedback across all your project requests.
        </p>
      </div>

      {groups.length === 0 ? (
        <Card>
          <CardContent className="py-10 text-center">
            <MessageCircle className="mx-auto mb-3 h-10 w-10 text-muted-foreground" />
            <h3 className="text-lg font-semibold">No feedback yet</h3>
            <p className="mt-1 text-sm text-muted-foreground">
              Feedback will appear here once reviews are posted on your project requests.
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-6">
          {groups.map((group) => (
            <Card key={group.requestId}>
              <CardHeader className="pb-3">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <CardTitle className="text-lg">{group.title}</CardTitle>
                    <CardDescription>{group.client?.company_name || "Unknown client"}</CardDescription>
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge variant="secondary">
                      <MessageCircle className="mr-1 h-3 w-3" />
                      {group.entries.length} {group.entries.length === 1 ? "entry" : "entries"}
                    </Badge>
                    <Badge variant="outline">{group.status.replace(/_/g, " ")}</Badge>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                {group.entries.slice(0, 3).map((entry) => (
                  <div key={entry.id} className="rounded-md border p-3">
                    <div className="mb-1 flex items-center gap-2">
                      <Avatar className="h-6 w-6">
                        <AvatarImage src={entry.user?.avatar || undefined} />
                        <AvatarFallback className="text-xs">
                          {entry.user?.name?.slice(0, 1).toUpperCase() || "U"}
                        </AvatarFallback>
                      </Avatar>
                      <span className="text-sm font-medium">{entry.user?.name || "Unknown"}</span>
                      <span className="text-xs text-muted-foreground">
                        {formatDistanceToNow(new Date(entry.createdAt), { addSuffix: true })}
                      </span>
                      {entry.rating ? (
                        <Badge variant="secondary" className="ml-auto text-xs">
                          <Star className="mr-0.5 h-3 w-3 fill-current" />
                          {entry.rating}/5
                        </Badge>
                      ) : null}
                    </div>
                    <p className="line-clamp-2 text-sm text-muted-foreground">{entry.message}</p>
                  </div>
                ))}

                <div className="flex justify-end">
                  <Button variant="outline" size="sm" asChild>
                    <Link href={`/projects/requests/${group.requestId}?tab=feedback`}>
                      View All Feedback
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
