import Link from "next/link";
import { notFound } from "next/navigation";
import { format } from "date-fns";
import { ArrowLeft, CalendarClock, FileText, MessageCircle, ClipboardList, CalendarDays, CircleDollarSign } from "lucide-react";
import { createClient } from "@/lib/supabase/server";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ProjectRequestTasks } from "@/components/projects/project-request-tasks";
import { ProjectRequestCalendar } from "@/components/projects/project-request-calendar";
import { ProjectRequestMessaging } from "@/components/projects/project-request-messaging";
import { ProjectRequestFeedback } from "@/components/projects/project-request-feedback";
import { ProjectRequestReviewPanel } from "@/components/projects/project-request-review-panel";

interface RequestRow {
  id: string;
  title: string;
  description: string | null;
  status: string;
  priority: string;
  due_date: string | null;
  created_at: string;
  custom_fields: {
    executiveSummary?: string;
    desiredOutcome?: string | null;
    budgetRange?: string | null;
    requestedStartDate?: string | null;
    requestedLaunchDate?: string | null;
    source?: string | null;
    publicIntake?: {
      companyName?: string | null;
      contactName?: string | null;
      contactEmail?: string | null;
      contactPhone?: string | null;
      website?: string | null;
      industry?: string | null;
      address?: string | null;
      city?: string | null;
      state?: string | null;
      zipCode?: string | null;
      country?: string | null;
      businessOverview?: string | null;
    } | null;
    review?: {
      status?: string;
      estimateAmount?: number | string | null;
      estimateCurrency?: string | null;
      estimatedHours?: number | string | null;
      estimatedStartDate?: string | null;
      estimatedEndDate?: string | null;
      responseSummary?: string | null;
      reviewNotes?: string | null;
    };
    clientDecision?: string;
  } | null;
  client: {
    id: string;
    company_name: string;
  } | null;
  creator: {
    id: string;
    name: string;
    email: string;
    avatar?: string | null;
  } | null;
  assigned_user: {
    id: string;
    name: string;
    email: string;
    avatar?: string | null;
  } | null;
}

interface AttachmentRow {
  id: string;
  name: string | null;
  file_name: string | null;
  file_size: number | null;
  mime_type: string | null;
  storage_url: string | null;
  storage_path: string | null;
  created_at: string;
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

const formatDate = (value?: string | null) => {
  if (!value) {
    return "Not set";
  }
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return "Not set";
  }
  return format(date, "MMM d, yyyy");
};

export default async function ProjectRequestDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
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
  const canReview = Boolean(
    isAdmin ||
      metadataRole === "staff" ||
      metadataRole === "account_manager" ||
      roleNames.includes("staff") ||
      roleNames.includes("account_manager"),
  );

  const { data: requestRow, error } = await supabase
    .from("requests")
    .select(
      `
      *,
      client:clients(id, company_name),
      creator:users!requests_created_by_fkey(id, name, email, avatar),
      assigned_user:users!requests_assigned_to_fkey(id, name, email, avatar)
    `,
    )
    .eq("id", id)
    .contains("custom_fields", { type: "project" })
    .single();

  if (error || !requestRow) {
    notFound();
  }

  if (!canReview && dbUser?.client_id && dbUser.client_id !== requestRow.client_id) {
    notFound();
  }

  const request = requestRow as RequestRow;
  const review = request.custom_fields?.review;
  const publicIntake = request.custom_fields?.publicIntake;

  const [attachmentsResult, tasksCountResult, meetingsCountResult, feedbackCountResult] = await Promise.all([
    supabase
      .from("documents")
      .select("id, name, file_name, file_size, mime_type, storage_url, storage_path, created_at")
      .eq("request_id", id)
      .is("deleted_at", null)
      .order("created_at", { ascending: false }),
    supabase.from("staff_tasks").select("id", { count: "exact", head: true }).eq("request_id", id),
    supabase.from("meetings").select("id", { count: "exact", head: true }).eq("request_id", id),
    supabase.from("request_comments").select("id", { count: "exact", head: true }).eq("request_id", id),
  ]);

  const attachments = (attachmentsResult.data || []) as AttachmentRow[];
  const tasksCount = tasksCountResult.count || 0;
  const meetingsCount = meetingsCountResult.count || 0;
  const feedbackCount = feedbackCountResult.count || 0;

  const estimateAmount = review?.estimateAmount ? Number(review.estimateAmount) : null;
  const estimateCurrency = review?.estimateCurrency || "USD";
  const estimatedHours = review?.estimatedHours ? Number(review.estimatedHours) : null;

  return (
    <div className="container mx-auto space-y-6 py-8">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="space-y-2">
          <Button variant="ghost" asChild className="-ml-3">
            <Link href="/projects/requests">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to Project Requests
            </Link>
          </Button>
          <div className="flex flex-wrap items-center gap-2">
            <h1 className="text-3xl font-bold tracking-tight">{request.title}</h1>
            <Badge variant={statusVariant(request.status)}>{request.status.replace(/_/g, " ")}</Badge>
            <Badge variant="secondary">{request.priority}</Badge>
            {review?.status ? <Badge variant="outline">Review: {String(review.status).replace(/_/g, " ")}</Badge> : null}
          </div>
          <p className="text-muted-foreground">
            Client: {request.client?.company_name || "Unknown"} • Submitted by {request.creator?.name || "Unknown"} •{" "}
            {formatDate(request.created_at)}
          </p>
        </div>
        <Button asChild>
          <Link href="/projects/requests/new">New Request</Link>
        </Button>
      </div>

      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <ClipboardList className="h-4 w-4" />
              Tasks
            </div>
            <p className="mt-2 text-2xl font-semibold">{tasksCount}</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <CalendarDays className="h-4 w-4" />
              Meetings
            </div>
            <p className="mt-2 text-2xl font-semibold">{meetingsCount}</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <MessageCircle className="h-4 w-4" />
              Feedback
            </div>
            <p className="mt-2 text-2xl font-semibold">{feedbackCount}</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <CircleDollarSign className="h-4 w-4" />
              Estimate
            </div>
            <p className="mt-2 text-lg font-semibold">
              {estimateAmount ? `${estimateCurrency} ${estimateAmount.toLocaleString()}` : "Pending"}
            </p>
            <p className="text-xs text-muted-foreground">
              {estimatedHours ? `${estimatedHours.toLocaleString()} hrs` : "Hours not set"}
            </p>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="overview" className="space-y-4">
        <TabsList className="flex flex-wrap h-auto">
          <TabsTrigger value="overview">Executive Summary</TabsTrigger>
          <TabsTrigger value="tasks">Tasks ({tasksCount})</TabsTrigger>
          <TabsTrigger value="calendar">Calendar ({meetingsCount})</TabsTrigger>
          <TabsTrigger value="messaging">Messaging</TabsTrigger>
          <TabsTrigger value="feedback">Feedback ({feedbackCount})</TabsTrigger>
          <TabsTrigger value="review">Review & Estimate</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Executive Summary</CardTitle>
              <CardDescription>Business objective and project intent.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                {request.custom_fields?.executiveSummary || "No executive summary provided."}
              </p>
              <div className="grid gap-3 md:grid-cols-2">
                <div className="rounded-md border p-3">
                  <p className="text-sm font-medium">Requested Start Date</p>
                  <p className="mt-1 text-sm text-muted-foreground">{formatDate(request.custom_fields?.requestedStartDate || null)}</p>
                </div>
                <div className="rounded-md border p-3">
                  <p className="text-sm font-medium">Requested Due / Launch Date</p>
                  <p className="mt-1 text-sm text-muted-foreground">{formatDate(request.custom_fields?.requestedLaunchDate || request.due_date)}</p>
                </div>
                <div className="rounded-md border p-3">
                  <p className="text-sm font-medium">Budget Context</p>
                  <p className="mt-1 text-sm text-muted-foreground">{request.custom_fields?.budgetRange || "Not provided"}</p>
                </div>
                <div className="rounded-md border p-3">
                  <p className="text-sm font-medium">Assigned Reviewer</p>
                  <p className="mt-1 text-sm text-muted-foreground">{request.assigned_user?.name || "Unassigned"}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Detailed Scope</CardTitle>
              <CardDescription>Requirements, notes, and expected outcomes.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <p className="text-sm font-medium">Description</p>
                <p className="mt-1 whitespace-pre-wrap text-sm text-muted-foreground">
                  {request.description || "No detailed scope provided."}
                </p>
              </div>
              <div>
                <p className="text-sm font-medium">Desired Outcome / KPIs</p>
                <p className="mt-1 whitespace-pre-wrap text-sm text-muted-foreground">
                  {request.custom_fields?.desiredOutcome || "No desired outcome provided."}
                </p>
              </div>
            </CardContent>
          </Card>

          {publicIntake ? (
            <Card>
              <CardHeader>
                <CardTitle>Organization Intake</CardTitle>
                <CardDescription>Business information submitted from the public project request form.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-3 md:grid-cols-2">
                  <div className="rounded-md border p-3">
                    <p className="text-sm font-medium">Primary Contact</p>
                    <p className="mt-1 text-sm text-muted-foreground">{publicIntake.contactName || "Not provided"}</p>
                  </div>
                  <div className="rounded-md border p-3">
                    <p className="text-sm font-medium">Contact Email</p>
                    <p className="mt-1 text-sm text-muted-foreground">{publicIntake.contactEmail || "Not provided"}</p>
                  </div>
                  <div className="rounded-md border p-3">
                    <p className="text-sm font-medium">Phone</p>
                    <p className="mt-1 text-sm text-muted-foreground">{publicIntake.contactPhone || "Not provided"}</p>
                  </div>
                  <div className="rounded-md border p-3">
                    <p className="text-sm font-medium">Website</p>
                    <p className="mt-1 text-sm text-muted-foreground">{publicIntake.website || "Not provided"}</p>
                  </div>
                  <div className="rounded-md border p-3">
                    <p className="text-sm font-medium">Industry</p>
                    <p className="mt-1 text-sm text-muted-foreground">{publicIntake.industry || "Not provided"}</p>
                  </div>
                  <div className="rounded-md border p-3">
                    <p className="text-sm font-medium">Submitted Via</p>
                    <p className="mt-1 text-sm text-muted-foreground">{request.custom_fields?.source || "Public intake"}</p>
                  </div>
                </div>
                <div className="rounded-md border p-3">
                  <p className="text-sm font-medium">Business Overview</p>
                  <p className="mt-1 whitespace-pre-wrap text-sm text-muted-foreground">
                    {publicIntake.businessOverview || "No overview provided."}
                  </p>
                </div>
                <div className="rounded-md border p-3">
                  <p className="text-sm font-medium">Address</p>
                  <p className="mt-1 whitespace-pre-wrap text-sm text-muted-foreground">
                    {[publicIntake.address, publicIntake.city, publicIntake.state, publicIntake.zipCode, publicIntake.country]
                      .filter(Boolean)
                      .join(", ") || "Not provided"}
                  </p>
                </div>
              </CardContent>
            </Card>
          ) : null}

          <Card>
            <CardHeader>
              <CardTitle>Project Files</CardTitle>
              <CardDescription>Uploaded source files, briefs, and supporting documents.</CardDescription>
            </CardHeader>
            <CardContent>
              {attachments.length === 0 ? (
                <div className="rounded-md border border-dashed py-8 text-center text-sm text-muted-foreground">
                  No files uploaded with this request.
                </div>
              ) : (
                <div className="space-y-2">
                  {attachments.map((attachment) => (
                    <div key={attachment.id} className="flex items-center justify-between rounded-md border p-3">
                      <div className="flex min-w-0 items-center gap-2">
                        <FileText className="h-4 w-4 text-muted-foreground" />
                        <div className="min-w-0">
                          <p className="truncate text-sm font-medium">{attachment.name || attachment.file_name}</p>
                          <p className="text-xs text-muted-foreground">
                            {attachment.mime_type || "Unknown type"} • {formatDate(attachment.created_at)}
                          </p>
                        </div>
                      </div>
                      {attachment.storage_url ? (
                        <Button asChild size="sm" variant="outline">
                          <a href={attachment.storage_url} target="_blank" rel="noreferrer">
                            Open
                          </a>
                        </Button>
                      ) : null}
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="tasks">
          <ProjectRequestTasks requestId={id} />
        </TabsContent>

        <TabsContent value="calendar">
          <ProjectRequestCalendar requestId={id} />
        </TabsContent>

        <TabsContent value="messaging">
          <ProjectRequestMessaging requestId={id} />
        </TabsContent>

        <TabsContent value="feedback">
          <ProjectRequestFeedback requestId={id} />
        </TabsContent>

        <TabsContent value="review">
          <ProjectRequestReviewPanel requestId={id} currentStatus={request.status} review={review || null} canReview={canReview} />
          <Card className="mt-4">
            <CardHeader>
              <CardTitle>Estimate Snapshot</CardTitle>
              <CardDescription>Shared estimate details currently visible in this request.</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-3 md:grid-cols-2">
              <div className="rounded-md border p-3">
                <p className="text-sm font-medium">Estimated Amount</p>
                <p className="mt-1 text-sm text-muted-foreground">
                  {estimateAmount ? `${estimateCurrency} ${estimateAmount.toLocaleString()}` : "Not provided"}
                </p>
              </div>
              <div className="rounded-md border p-3">
                <p className="text-sm font-medium">Estimated Hours</p>
                <p className="mt-1 text-sm text-muted-foreground">{estimatedHours ? `${estimatedHours} hrs` : "Not provided"}</p>
              </div>
              <div className="rounded-md border p-3">
                <p className="text-sm font-medium">Estimated Start</p>
                <p className="mt-1 text-sm text-muted-foreground">{formatDate(review?.estimatedStartDate || null)}</p>
              </div>
              <div className="rounded-md border p-3">
                <p className="text-sm font-medium">Estimated End</p>
                <p className="mt-1 text-sm text-muted-foreground">{formatDate(review?.estimatedEndDate || null)}</p>
              </div>
              <div className="rounded-md border p-3 md:col-span-2">
                <p className="text-sm font-medium">Response Summary</p>
                <p className="mt-1 whitespace-pre-wrap text-sm text-muted-foreground">{review?.responseSummary || "No estimate response shared yet."}</p>
              </div>
              <div className="rounded-md border p-3 md:col-span-2">
                <p className="text-sm font-medium">Request Due Date</p>
                <p className="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                  <CalendarClock className="h-4 w-4" />
                  {formatDate(request.due_date)}
                </p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
