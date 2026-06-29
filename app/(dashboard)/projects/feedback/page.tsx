import Link from "next/link";
import { formatDistanceToNow } from "date-fns";
import { createClient } from "@/lib/supabase/server";
import { isAdminUser } from "@/lib/rbac/check";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { ArrowRight, CheckCircle2, Clock, MessageSquare, Globe, ImageIcon } from "lucide-react";

export const metadata = {
  title: "Project Reviews",
  description: "Design feedback and review activity across all your projects",
};

const statusColors: Record<string, string> = {
  planning: "bg-blue-500",
  active: "bg-green-500",
  on_hold: "bg-yellow-500",
  completed: "bg-gray-500",
  cancelled: "bg-red-500",
};

const reviewStatusLabel: Record<string, string> = {
  open: "Open",
  in_review: "In Review",
  resolved: "Resolved",
  archived: "Archived",
};

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

  const isAdmin = user ? isAdminUser(user, dbUser, roleRows) : false;

  // Get all project_review_items with project info
  let itemQuery = supabase
    .from("project_review_items")
    .select("id, project_id, title, type, status, created_at, updated_at, project:projects!inner(id, name, status, client_id, deleted_at)")
    .is("project.deleted_at", null)
    .order("updated_at", { ascending: false });

  if (!isAdmin && dbUser?.client_id) {
    itemQuery = itemQuery.eq("project.client_id", dbUser.client_id);
  }

  const { data: reviewItems } = await itemQuery;

  // Get recent comments across all review items (batch, not N+1)
  const allProjectIds = [...new Set((reviewItems || []).map((i) => i.project_id))];

  const { data: recentComments } = allProjectIds.length > 0
    ? await supabase
        .from("project_review_comments")
        .select("id, review_item_id, project_id, body, status, created_at, author:users(id, name, avatar)")
        .in("project_id", allProjectIds)
        .order("created_at", { ascending: false })
        .limit(50)
    : { data: [] };

  // Group by project
  type ReviewItem = NonNullable<typeof reviewItems>[number];
  type Comment = NonNullable<typeof recentComments>[number];

  const projectMap = new Map<string, {
    project: { id: string; name: string; status: string };
    items: ReviewItem[];
    comments: Comment[];
    latestAt: string;
  }>();

  for (const item of reviewItems || []) {
    const proj = Array.isArray(item.project) ? item.project[0] : item.project;
    if (!proj) continue;
    if (!projectMap.has(item.project_id)) {
      projectMap.set(item.project_id, {
        project: { id: proj.id, name: proj.name, status: proj.status },
        items: [],
        comments: [],
        latestAt: item.updated_at,
      });
    }
    const entry = projectMap.get(item.project_id)!;
    entry.items.push(item);
    if (item.updated_at > entry.latestAt) entry.latestAt = item.updated_at;
  }

  for (const comment of recentComments || []) {
    const entry = projectMap.get(comment.project_id);
    if (entry) entry.comments.push(comment);
  }

  const groups = [...projectMap.values()].sort(
    (a, b) => new Date(b.latestAt).getTime() - new Date(a.latestAt).getTime(),
  );

  // Summary stats
  const totalOpen = (reviewItems || []).filter((i) => i.status === "open" || i.status === "in_review").length;
  const totalResolved = (reviewItems || []).filter((i) => i.status === "resolved").length;
  const projectsWithOpen = groups.filter((g) => g.items.some((i) => i.status === "open" || i.status === "in_review")).length;

  return (
    <div className="container mx-auto space-y-6 py-8">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Project Reviews</h1>
        <p className="mt-1 text-muted-foreground">
          Design feedback and review activity across all your projects.
        </p>
      </div>

      {/* Stats row */}
      {groups.length > 0 && (
        <div className="grid grid-cols-3 gap-4">
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <Clock className="h-8 w-8 text-amber-500" />
                <div>
                  <div className="text-2xl font-bold">{totalOpen}</div>
                  <div className="text-sm text-muted-foreground">Open items</div>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <MessageSquare className="h-8 w-8 text-blue-500" />
                <div>
                  <div className="text-2xl font-bold">{projectsWithOpen}</div>
                  <div className="text-sm text-muted-foreground">Projects awaiting review</div>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <CheckCircle2 className="h-8 w-8 text-green-500" />
                <div>
                  <div className="text-2xl font-bold">{totalResolved}</div>
                  <div className="text-sm text-muted-foreground">Resolved items</div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {groups.length === 0 ? (
        <Card>
          <CardContent className="py-16 text-center">
            <MessageSquare className="mx-auto mb-3 h-10 w-10 text-muted-foreground" />
            <h3 className="text-lg font-semibold">No reviews yet</h3>
            <p className="mt-1 text-sm text-muted-foreground">
              Design review activity will appear here once items are added on a project&apos;s Reviews tab.
            </p>
            <Button variant="outline" className="mt-4" asChild>
              <Link href="/projects">Go to Projects</Link>
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {groups.map((group) => {
            const openItems = group.items.filter((i) => i.status === "open" || i.status === "in_review");
            const resolvedItems = group.items.filter((i) => i.status === "resolved");
            const recentComments = group.comments.slice(0, 3);

            return (
              <Card key={group.project.id}>
                <CardHeader className="pb-3">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <div className="flex items-center gap-2">
                        <CardTitle className="text-lg">{group.project.name}</CardTitle>
                        <span
                          className={`inline-block h-2 w-2 rounded-full ${statusColors[group.project.status] ?? "bg-gray-400"}`}
                        />
                      </div>
                      <CardDescription className="mt-0.5">
                        {group.items.length} review {group.items.length === 1 ? "item" : "items"} &middot; last activity{" "}
                        {formatDistanceToNow(new Date(group.latestAt), { addSuffix: true })}
                      </CardDescription>
                    </div>
                    <div className="flex items-center gap-2">
                      {openItems.length > 0 && (
                        <Badge variant="secondary" className="text-amber-700 bg-amber-100 border-amber-200">
                          <Clock className="mr-1 h-3 w-3" />
                          {openItems.length} open
                        </Badge>
                      )}
                      {resolvedItems.length > 0 && (
                        <Badge variant="secondary" className="text-green-700 bg-green-100 border-green-200">
                          <CheckCircle2 className="mr-1 h-3 w-3" />
                          {resolvedItems.length} resolved
                        </Badge>
                      )}
                    </div>
                  </div>
                </CardHeader>

                <CardContent className="space-y-3">
                  {/* Review items summary */}
                  <div className="flex flex-wrap gap-2">
                    {group.items.slice(0, 5).map((item) => (
                      <div
                        key={item.id}
                        className="flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs text-muted-foreground"
                      >
                        {item.type === "website" ? (
                          <Globe className="h-3 w-3 shrink-0" />
                        ) : (
                          <ImageIcon className="h-3 w-3 shrink-0" />
                        )}
                        <span className="max-w-[160px] truncate">{item.title}</span>
                        <Badge
                          variant="outline"
                          className="ml-1 px-1 py-0 text-[10px] leading-4"
                        >
                          {reviewStatusLabel[item.status] ?? item.status}
                        </Badge>
                      </div>
                    ))}
                    {group.items.length > 5 && (
                      <div className="flex items-center rounded-md border px-2 py-1 text-xs text-muted-foreground">
                        +{group.items.length - 5} more
                      </div>
                    )}
                  </div>

                  {/* Recent comments */}
                  {recentComments.length > 0 && (
                    <div className="space-y-2 pt-1">
                      {recentComments.map((comment) => {
                        const author = Array.isArray(comment.author) ? comment.author[0] : comment.author;
                        return (
                          <div key={comment.id} className="flex items-start gap-2 rounded-md bg-muted/40 px-3 py-2">
                            <Avatar className="mt-0.5 h-5 w-5 shrink-0">
                              <AvatarImage src={(author as { avatar?: string | null } | null)?.avatar ?? undefined} />
                              <AvatarFallback className="text-[10px]">
                                {((author as { name?: string } | null)?.name ?? "U").slice(0, 1).toUpperCase()}
                              </AvatarFallback>
                            </Avatar>
                            <div className="min-w-0 flex-1">
                              <div className="flex items-center gap-1.5">
                                <span className="text-xs font-medium">
                                  {(author as { name?: string } | null)?.name ?? "Unknown"}
                                </span>
                                <span className="text-[11px] text-muted-foreground">
                                  {formatDistanceToNow(new Date(comment.created_at), { addSuffix: true })}
                                </span>
                              </div>
                              <p className="line-clamp-1 text-xs text-muted-foreground">{comment.body}</p>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  )}

                  <div className="flex justify-end pt-1">
                    <Button variant="outline" size="sm" asChild>
                      <Link href={`/projects/${group.project.id}`}>
                        Open Project
                        <ArrowRight className="ml-2 h-4 w-4" />
                      </Link>
                    </Button>
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}
    </div>
  );
}
