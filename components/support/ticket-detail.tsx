"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Progress } from "@/components/ui/progress";
import { Separator } from "@/components/ui/separator";
import { formatDistanceToNow, format } from "date-fns";
import { updateSupportTicketSchema, type UpdateSupportTicketInput } from "@/lib/validations/support-ticket";
import { getSlaStatus, formatTimeRemaining, getSlaStatusColor } from "@/lib/utils/sla";
import { cn } from "@/lib/utils";
import { fetchApi } from "@/lib/api/client";
import { toast } from "sonner";
import { Clock, User, Tag, AlertTriangle, Trash2 } from "lucide-react";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import type { WebsiteSupportTriage } from "@/lib/support/website-ticket-triage";
import type { TicketPriority, TicketStatus } from "@/lib/db/schema/support-tickets";

interface StaffUser {
  id: string;
  name: string;
  email: string;
}

interface SupportTicketDetailData {
  id: string;
  ticket_number: string;
  subject: string;
  description: string;
  category: string;
  status: TicketStatus;
  priority: TicketPriority;
  assigned_to: string | null;
  assigned_user?: { name?: string | null } | null;
  creator?: { name?: string | null } | null;
  first_response_at: string | null;
  resolved_at: string | null;
  closed_at: string | null;
  created_at: string;
  sla_response_due_at: string | null;
  sla_resolution_due_at: string | null;
  sla_response_breached: boolean;
  sla_resolution_breached: boolean;
  sla_paused: boolean;
  sla_paused_duration_minutes: number;
  escalation_level: number;
  last_escalated_at: string | null;
  metadata?: {
    customFields?: {
      websiteSupportTriage?: WebsiteSupportTriage;
    } & Record<string, unknown>;
  } | null;
}

interface TicketDetailProps {
  ticket: SupportTicketDetailData;
  staffUsers: StaffUser[];
  isStaff: boolean;
}

export function SupportTicketDetail({ ticket, staffUsers, isStaff }: TicketDetailProps) {
  const router = useRouter();
  const [isEditing, setIsEditing] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const websiteTriage = ticket.metadata?.customFields?.websiteSupportTriage;

  const form = useForm<UpdateSupportTicketInput>({
    resolver: zodResolver(updateSupportTicketSchema),
    defaultValues: {
      status: ticket.status,
      priority: ticket.priority,
      assignedTo: ticket.assigned_to || null,
    },
  });

  const slaInfo = getSlaStatus(
    ticket.first_response_at ? new Date(ticket.first_response_at) : null,
    ticket.sla_response_due_at ? new Date(ticket.sla_response_due_at) : null,
    ticket.sla_resolution_due_at ? new Date(ticket.sla_resolution_due_at) : null,
    ticket.sla_response_breached,
    ticket.sla_resolution_breached,
    ticket.sla_paused,
    ticket.status,
    new Date(ticket.created_at),
    ticket.sla_paused_duration_minutes,
  );
  const isAcknowledged = Boolean(ticket.first_response_at);
  const headerAccentClassName = getTicketHeaderClassName(slaInfo.status, isAcknowledged);

  async function doDelete() {
    setIsDeleting(true);
    try {
      await fetchApi(`/api/support/${ticket.id}`, { method: "DELETE" }, {
        fallbackMessage: "Failed to delete ticket",
      });
      toast.success("Ticket deleted");
      router.push("/support");
      router.refresh();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Failed to delete ticket");
    } finally {
      setIsDeleting(false);
    }
  }

  function handleDelete() {
    setConfirmOpen(true);
  }

  async function onSubmit(data: UpdateSupportTicketInput) {
    setIsSubmitting(true);

    try {
      await fetchApi(
        `/api/support/${ticket.id}`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(data),
        },
        { fallbackMessage: "Failed to update ticket" },
      );

      toast.success("Ticket updated successfully");
      setIsEditing(false);
      router.refresh();
    } catch (error) {
      console.error("Error updating ticket:", error);
      toast.error(error instanceof Error ? error.message : "Failed to update ticket");
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <Card className={cn("border-l-4", headerAccentClassName)}>
        <CardHeader>
          <div className="flex items-start justify-between">
            <div className="space-y-1">
              <div className="flex items-center gap-2">
                <CardTitle className="text-2xl">{ticket.subject}</CardTitle>
                <Badge variant={getStatusVariant(ticket.status)}>{ticket.status.replace(/_/g, " ")}</Badge>
                <Badge variant={getPriorityVariant(ticket.priority)}>{ticket.priority}</Badge>
                <Badge variant={isAcknowledged ? "default" : "secondary"} className={getAcknowledgementBadgeClassName(isAcknowledged, slaInfo.status)}>
                  {isAcknowledged ? "Acknowledged" : "Awaiting staff acknowledgment"}
                </Badge>
              </div>
              <CardDescription className="flex items-center gap-4 text-base">
                <span className="font-mono">{ticket.ticket_number}</span>
                <span>•</span>
                <span>Created {formatDistanceToNow(new Date(ticket.created_at), { addSuffix: true })}</span>
              </CardDescription>
            </div>
            <div className="flex gap-2">
              {isStaff && !isEditing && (
                <>
                  <Button variant="outline" onClick={() => setIsEditing(true)}>Edit</Button>
                  <Button variant="destructive" size="icon" disabled={isDeleting} onClick={handleDelete} title="Delete ticket">
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </>
              )}
              <Button variant="outline" onClick={() => router.back()}>Back</Button>
            </div>
          </div>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Description */}
          <div>
            <h3 className="font-semibold mb-2">Description</h3>
            <p className="text-muted-foreground whitespace-pre-wrap">{ticket.description}</p>
          </div>

          <Separator />

          {/* Edit Form or Details View */}
          {isStaff && isEditing ? (
            <Form {...form}>
              <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <FormField
                    control={form.control}
                    name="status"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Status</FormLabel>
                        <Select onValueChange={field.onChange} defaultValue={field.value}>
                          <FormControl>
                            <SelectTrigger>
                              <SelectValue />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            <SelectItem value="open">Open</SelectItem>
                            <SelectItem value="in_progress">In Progress</SelectItem>
                            <SelectItem value="waiting_on_client">Waiting on Client</SelectItem>
                            <SelectItem value="waiting_on_vendor">Waiting on Vendor</SelectItem>
                            <SelectItem value="resolved">Resolved</SelectItem>
                            <SelectItem value="closed">Closed</SelectItem>
                          </SelectContent>
                        </Select>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="priority"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Priority</FormLabel>
                        <Select onValueChange={field.onChange} defaultValue={field.value}>
                          <FormControl>
                            <SelectTrigger>
                              <SelectValue />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            <SelectItem value="low">Low</SelectItem>
                            <SelectItem value="medium">Medium</SelectItem>
                            <SelectItem value="high">High</SelectItem>
                            <SelectItem value="urgent">Urgent</SelectItem>
                          </SelectContent>
                        </Select>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="assignedTo"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Assigned To</FormLabel>
                        <Select
                          onValueChange={(v) => field.onChange(v === "unassigned" ? null : v)}
                          defaultValue={field.value || "unassigned"}
                        >
                          <FormControl>
                            <SelectTrigger>
                              <SelectValue placeholder="Select staff member" />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            <SelectItem value="unassigned">Unassigned</SelectItem>
                            {staffUsers.map((user) => (
                              <SelectItem key={user.id} value={user.id}>
                                {user.name}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </div>

                <div className="flex gap-2">
                  <Button type="submit" disabled={isSubmitting}>
                    {isSubmitting ? "Saving..." : "Save Changes"}
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => {
                      setIsEditing(false);
                      form.reset();
                    }}
                    disabled={isSubmitting}
                  >
                    Cancel
                  </Button>
                </div>
              </form>
            </Form>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Ticket Info */}
              <div className="space-y-4">
                <div>
                  <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                    <Tag className="h-4 w-4" />
                    Category
                  </div>
                  <p className="font-medium capitalize">{ticket.category.replace(/_/g, " ")}</p>
                </div>

                <div>
                  <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                    <User className="h-4 w-4" />
                    Assigned To
                  </div>
                  <p className="font-medium">{ticket.assigned_user?.name || "Unassigned"}</p>
                </div>

                <div>
                  <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                    <User className="h-4 w-4" />
                    Created By
                  </div>
                  <p className="font-medium">{ticket.creator?.name || "Unknown"}</p>
                </div>
              </div>

              {/* Timeline */}
              <div className="space-y-4">
                <div>
                  <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                    <Clock className="h-4 w-4" />
                    Created
                  </div>
                  <p className="font-medium">{format(new Date(ticket.created_at), "PPpp")}</p>
                </div>

                {ticket.first_response_at && (
                  <div>
                    <div className="text-sm text-muted-foreground mb-1">First Response</div>
                    <p className="font-medium">{format(new Date(ticket.first_response_at), "PPpp")}</p>
                  </div>
                )}

                {ticket.resolved_at && (
                  <div>
                    <div className="text-sm text-muted-foreground mb-1">Resolved</div>
                    <p className="font-medium">{format(new Date(ticket.resolved_at), "PPpp")}</p>
                  </div>
                )}

                {ticket.closed_at && (
                  <div>
                    <div className="text-sm text-muted-foreground mb-1">Closed</div>
                    <p className="font-medium">{format(new Date(ticket.closed_at), "PPpp")}</p>
                  </div>
                )}
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      {websiteTriage && (
        <Card>
          <CardHeader>
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div>
                <CardTitle>Website Support Triage</CardTitle>
                <CardDescription>AI agent handoff for WordPress support review and Codex routing</CardDescription>
              </div>
              <div className="flex flex-wrap gap-2">
                <Badge variant={websiteTriage.riskLevel === "High Risk" ? "destructive" : "secondary"}>
                  {websiteTriage.riskLevel}
                </Badge>
                <Badge variant="outline">{websiteTriage.recommendedRouting}</Badge>
              </div>
            </div>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <TriageField label="Production URL" value={websiteTriage.website.productionUrl} />
              <TriageField label="Staging URL" value={websiteTriage.website.stagingUrl} />
              <TriageField label="Affected Page" value={websiteTriage.website.affectedPageUrl} />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 className="font-semibold mb-2">Problem Statement</h3>
                <p className="text-sm text-muted-foreground whitespace-pre-wrap">{websiteTriage.problemStatement}</p>
              </div>
              <div>
                <h3 className="font-semibold mb-2">Requested Change</h3>
                <p className="text-sm text-muted-foreground whitespace-pre-wrap">{websiteTriage.requestedChange}</p>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 className="font-semibold mb-2">Risk Reason</h3>
                <p className="text-sm text-muted-foreground">{websiteTriage.riskReason}</p>
              </div>
              <div>
                <h3 className="font-semibold mb-2">Routing Reason</h3>
                <p className="text-sm text-muted-foreground">{websiteTriage.routingReason}</p>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <TriageList title="Acceptance Criteria" items={websiteTriage.acceptanceCriteria} />
              <TriageList title="Do Not Change" items={websiteTriage.doNotChange} />
              <TriageList title="Testing Instructions" items={websiteTriage.testingInstructions} />
            </div>

            {websiteTriage.codexReadyPrompt ? (
              <div>
                <h3 className="font-semibold mb-2">Codex Ready Prompt</h3>
                <pre className="max-h-[520px] overflow-auto rounded-md border bg-muted p-4 text-xs leading-relaxed whitespace-pre-wrap">
                  {websiteTriage.codexReadyPrompt}
                </pre>
              </div>
            ) : (
              <div className="rounded-md border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900">
                High-risk ticket: Codex may analyze and recommend, but should not execute production-impacting changes.
                Human developer review, backup, staging validation, and production approval are required.
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* SLA Tracking */}
      <Card className={cn("border", getSlaStatusColor(slaInfo.status))}>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <AlertTriangle className="h-5 w-5" />
            SLA Tracking
          </CardTitle>
          <CardDescription>Service Level Agreement monitoring and escalation</CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Overall SLA Status */}
          <div>
            <div className="flex items-center justify-between mb-2">
              <span className="text-sm font-medium">Overall Status</span>
              <Badge variant={getSlaVariant(slaInfo.status)} className={getSlaBadgeClassName(slaInfo.status)}>
                {slaInfo.status.replace(/_/g, " ").toUpperCase()}
              </Badge>
            </div>
            <div className="text-sm text-muted-foreground">
              {isAcknowledged
                ? "Staff has acknowledged this ticket. Resolution SLA remains active."
                : "This ticket has not been acknowledged by staff yet. Response SLA is active."}
            </div>
          </div>

          {/* Response SLA */}
          {!ticket.first_response_at && ticket.sla_response_due_at && (
            <div className={cn("rounded-lg border p-4", getSlaPanelClassName(isAcknowledged ? "on_track" : slaInfo.status))}>
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm font-medium">Response Time</span>
                <span className="text-sm text-muted-foreground">
                  {formatTimeRemaining(new Date(ticket.sla_response_due_at))} remaining
                </span>
              </div>
              <Progress value={slaInfo.responsePercentUsed} className="h-2" />
              <p className="text-xs text-muted-foreground mt-1">
                Due: {format(new Date(ticket.sla_response_due_at), "PPpp")}
              </p>
            </div>
          )}

          {/* Resolution SLA */}
          {ticket.status !== "resolved" && ticket.status !== "closed" && ticket.sla_resolution_due_at && (
            <div className={cn("rounded-lg border p-4", getSlaPanelClassName(slaInfo.status))}>
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm font-medium">Resolution Time</span>
                <span className="text-sm text-muted-foreground">
                  {formatTimeRemaining(new Date(ticket.sla_resolution_due_at))} remaining
                </span>
              </div>
              <Progress value={slaInfo.resolutionPercentUsed} className="h-2" />
              <p className="text-xs text-muted-foreground mt-1">
                Due: {format(new Date(ticket.sla_resolution_due_at), "PPpp")}
              </p>
            </div>
          )}

          {/* Escalation Info */}
          {ticket.escalation_level > 0 && (
            <div className="p-4 border rounded-lg bg-orange-50 border-orange-200">
              <p className="text-sm font-medium text-orange-900">Escalation Level: {ticket.escalation_level}</p>
              {ticket.last_escalated_at && (
                <p className="text-xs text-orange-700 mt-1">
                  Last escalated: {formatDistanceToNow(new Date(ticket.last_escalated_at), { addSuffix: true })}
                </p>
              )}
            </div>
          )}

          {/* Paused Status */}
          {ticket.sla_paused && (
            <div className="p-4 border rounded-lg bg-gray-50 border-gray-200">
              <p className="text-sm font-medium text-gray-900">SLA Timer Paused</p>
              <p className="text-xs text-gray-700 mt-1">
                Total paused time: {Math.floor(ticket.sla_paused_duration_minutes / 60)}h{" "}
                {ticket.sla_paused_duration_minutes % 60}m
              </p>
            </div>
          )}
        </CardContent>
      </Card>
      <ConfirmDialog
        open={confirmOpen}
        onOpenChange={setConfirmOpen}
        title="Delete support ticket?"
        description="This will permanently delete the ticket and all comments. This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={doDelete}
        loading={isDeleting}
      />
    </div>
  );
}

function TriageField({ label, value }: { label: string; value?: string | null }) {
  return (
    <div>
      <div className="text-sm text-muted-foreground mb-1">{label}</div>
      <p className="font-medium break-words">{value || "Not provided"}</p>
    </div>
  );
}

function TriageList({ title, items }: { title: string; items: string[] }) {
  return (
    <div>
      <h3 className="font-semibold mb-2">{title}</h3>
      <ul className="space-y-2 text-sm text-muted-foreground">
        {items.map((item, index) => (
          <li key={`${title}-${index}`} className="flex gap-2">
            <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-muted-foreground" />
            <span>{item}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}

function getStatusVariant(status: string): "default" | "secondary" | "destructive" | "outline" {
  const variants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
    open: "default",
    in_progress: "default",
    waiting_on_client: "secondary",
    waiting_on_vendor: "secondary",
    resolved: "outline",
    closed: "outline",
  };
  return variants[status] || "default";
}

function getPriorityVariant(priority: string): "default" | "secondary" | "destructive" {
  const variants: Record<string, "default" | "secondary" | "destructive"> = {
    low: "secondary",
    medium: "default",
    high: "destructive",
    urgent: "destructive",
  };
  return variants[priority] || "default";
}

function getSlaVariant(slaStatus: string): "default" | "secondary" | "destructive" {
  const variants: Record<string, "default" | "secondary" | "destructive"> = {
    on_track: "default",
    warning: "secondary",
    response_breached: "destructive",
    breached: "destructive",
    paused: "secondary",
  };
  return variants[slaStatus] || "default";
}

function getTicketHeaderClassName(slaStatus: string, isAcknowledged: boolean): string {
  if (slaStatus === "breached") {
    return "border-l-red-600 bg-red-50/70";
  }

  if (slaStatus === "response_breached") {
    return "border-l-orange-600 bg-orange-50/70";
  }

  if (!isAcknowledged) {
    if (slaStatus === "warning") {
      return "border-l-amber-500 bg-amber-50/70";
    }

    return "border-l-yellow-500 bg-yellow-50/60";
  }

  if (slaStatus === "paused") {
    return "border-l-slate-400 bg-slate-50/60";
  }

  if (slaStatus === "warning") {
    return "border-l-blue-500 bg-blue-50/60";
  }

  return "border-l-emerald-500 bg-emerald-50/50";
}

function getAcknowledgementBadgeClassName(isAcknowledged: boolean, slaStatus: string): string {
  if (isAcknowledged) {
    return "bg-emerald-100 text-emerald-800 hover:bg-emerald-100";
  }

  if (slaStatus === "breached") {
    return "bg-red-100 text-red-800 hover:bg-red-100";
  }

  if (slaStatus === "response_breached" || slaStatus === "warning") {
    return "bg-orange-100 text-orange-800 hover:bg-orange-100";
  }

  return "bg-yellow-100 text-yellow-900 hover:bg-yellow-100";
}

function getSlaBadgeClassName(slaStatus: string): string {
  const classes: Record<string, string> = {
    on_track: "bg-green-100 text-green-800 hover:bg-green-100",
    warning: "bg-yellow-100 text-yellow-800 hover:bg-yellow-100",
    response_breached: "bg-orange-100 text-orange-800 hover:bg-orange-100",
    breached: "bg-red-100 text-red-800 hover:bg-red-100",
    paused: "bg-slate-100 text-slate-800 hover:bg-slate-100",
  };

  return classes[slaStatus] || "";
}

function getSlaPanelClassName(slaStatus: string): string {
  const classes: Record<string, string> = {
    on_track: "border-green-200 bg-green-50/50",
    warning: "border-yellow-200 bg-yellow-50/60",
    response_breached: "border-orange-200 bg-orange-50/70",
    breached: "border-red-200 bg-red-50/70",
    paused: "border-slate-200 bg-slate-50/70",
  };

  return classes[slaStatus] || "border-border bg-background";
}
