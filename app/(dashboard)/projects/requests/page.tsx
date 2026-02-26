import Link from "next/link";
import { formatDistanceToNow } from "date-fns";
import { createClient } from "@/lib/supabase/server";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ArrowRight, Plus } from "lucide-react";

export const metadata = {
  title: "Project Requests",
  description: "Client-submitted project requests with review and estimate workflow",
};

interface ProjectRequestRow {
  id: string;
  title: string;
  description: string | null;
  status: string;
  priority: string;
  created_at: string;
  due_date: string | null;
  custom_fields: {
    executiveSummary?: string;
    review?: {
      status?: string;
      estimateAmount?: number | string | null;
      estimateCurrency?: string | null;
      estimatedHours?: number | string | null;
    };
  } | null;
  client: {
    id: string;
    company_name: string;
  } | null;
}

const statusVariant = (status: string): "default" | "secondary" | "destructive" | "outline" => {
  if (status === "approved" || status === "completed") {
    return "outline";
  }
  if (status === "rejected" || status === "cancelled") {
    return "destructive";
  }
  if (status === "in_progress" || status === "awaiting_approval") {
    return "default";
  }
  return "secondary";
};

export default async function ProjectRequestsPage() {
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
    .select("id, title, description, status, priority, created_at, due_date, custom_fields, client:clients(id, company_name)")
    .contains("custom_fields", { type: "project" })
    .order("created_at", { ascending: false });

  if (!isAdmin && dbUser?.client_id) {
    query = query.eq("client_id", dbUser.client_id);
  }

  const { data: rows } = await query;
  const requests = (rows || []) as ProjectRequestRow[];

  return (
    <div className="container mx-auto space-y-6 py-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Project Requests</h1>
          <p className="mt-1 text-muted-foreground">
            Client submissions with attachments, review status, estimate response, and collaboration tools.
          </p>
        </div>
        <Button asChild>
          <Link href="/projects/requests/new">
            <Plus className="mr-2 h-4 w-4" />
            New Project Request
          </Link>
        </Button>
      </div>

      {requests.length === 0 ? (
        <Card>
          <CardContent className="py-10 text-center">
            <h3 className="text-lg font-semibold">No project requests yet</h3>
            <p className="mt-1 text-sm text-muted-foreground">
              Submit your first request and include scope, files, due dates, and outcomes.
            </p>
            <Button asChild className="mt-4">
              <Link href="/projects/requests/new">
                <Plus className="mr-2 h-4 w-4" />
                Submit Request
              </Link>
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4">
          {requests.map((request) => {
            const review = request.custom_fields?.review;
            const estimateAmount = review?.estimateAmount ? Number(review.estimateAmount) : null;
            const estimateHours = review?.estimatedHours ? Number(review.estimatedHours) : null;
            const estimateCurrency = review?.estimateCurrency || "USD";

            return (
              <Card key={request.id} className="transition hover:shadow-sm">
                <CardHeader>
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <CardTitle className="text-xl">{request.title}</CardTitle>
                      <CardDescription className="mt-1 line-clamp-2">
                        {request.custom_fields?.executiveSummary || request.description || "No executive summary provided"}
                      </CardDescription>
                    </div>
                    <div className="flex items-center gap-2">
                      <Badge variant={statusVariant(request.status)}>{request.status.replace(/_/g, " ")}</Badge>
                      <Badge variant="secondary">{request.priority}</Badge>
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className="grid gap-2 text-sm text-muted-foreground md:grid-cols-3">
                    <p>Client: {request.client?.company_name || "Unassigned"}</p>
                    <p>
                      Submitted{" "}
                      {formatDistanceToNow(new Date(request.created_at), {
                        addSuffix: true,
                      })}
                    </p>
                    <p>Due: {request.due_date ? new Date(request.due_date).toLocaleDateString() : "Not set"}</p>
                  </div>

                  <div className="rounded-md bg-muted/40 p-3 text-sm">
                    <p className="font-medium">Latest estimate</p>
                    <p className="mt-1 text-muted-foreground">
                      {estimateAmount
                        ? `${estimateCurrency} ${estimateAmount.toLocaleString()}`
                        : "Amount not provided"}{" "}
                      {estimateHours ? `• ${estimateHours.toLocaleString()} hrs` : "• Hours not provided"}
                      {review?.status ? ` • ${String(review.status).replace(/_/g, " ")}` : ""}
                    </p>
                  </div>

                  <div className="flex justify-end">
                    <Button variant="outline" asChild>
                      <Link href={`/projects/requests/${request.id}`}>
                        Open Workspace
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
