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
import { toast } from "sonner";
import { ClockIcon, UserIcon, TagIcon, AlertTriangleIcon } from "@radix-ui/react-icons";

interface StaffUser {
  id: string;
  name: string;
  email: string;
}

interface TicketDetailProps {
  ticket: any;
  staffUsers: StaffUser[];
}

export function SupportTicketDetail({ ticket, staffUsers }: TicketDetailProps) {
  const router = useRouter();
  const [isEditing, setIsEditing] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

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

  async function onSubmit(data: UpdateSupportTicketInput) {
    setIsSubmitting(true);

    try {
      const response = await fetch(`/api/support/${ticket.id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || "Failed to update ticket");
      }

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
      <Card>
        <CardHeader>
          <div className="flex items-start justify-between">
            <div className="space-y-1">
              <div className="flex items-center gap-2">
                <CardTitle className="text-2xl">{ticket.subject}</CardTitle>
                <Badge variant={getStatusVariant(ticket.status)}>{ticket.status.replace(/_/g, " ")}</Badge>
                <Badge variant={getPriorityVariant(ticket.priority)}>{ticket.priority}</Badge>
              </div>
              <CardDescription className="flex items-center gap-4 text-base">
                <span className="font-mono">{ticket.ticket_number}</span>
                <span>•</span>
                <span>Created {formatDistanceToNow(new Date(ticket.created_at), { addSuffix: true })}</span>
              </CardDescription>
            </div>
            <div className="flex gap-2">
              {!isEditing && (
                <Button variant="outline" onClick={() => setIsEditing(true)}>
                  Edit
                </Button>
              )}
              <Button variant="outline" onClick={() => router.back()}>
                Back
              </Button>
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
          {isEditing ? (
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
                        <Select onValueChange={field.onChange} defaultValue={field.value || undefined}>
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
                    <TagIcon className="h-4 w-4" />
                    Category
                  </div>
                  <p className="font-medium capitalize">{ticket.category.replace(/_/g, " ")}</p>
                </div>

                <div>
                  <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                    <UserIcon className="h-4 w-4" />
                    Assigned To
                  </div>
                  <p className="font-medium">{ticket.assigned_user?.name || "Unassigned"}</p>
                </div>

                <div>
                  <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                    <UserIcon className="h-4 w-4" />
                    Created By
                  </div>
                  <p className="font-medium">{ticket.creator?.name}</p>
                </div>
              </div>

              {/* Timeline */}
              <div className="space-y-4">
                <div>
                  <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                    <ClockIcon className="h-4 w-4" />
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

      {/* SLA Tracking */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <AlertTriangleIcon className="h-5 w-5" />
            SLA Tracking
          </CardTitle>
          <CardDescription>Service Level Agreement monitoring and escalation</CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Overall SLA Status */}
          <div>
            <div className="flex items-center justify-between mb-2">
              <span className="text-sm font-medium">Overall Status</span>
              <Badge variant={getSlaVariant(slaInfo.status)}>{slaInfo.status.replace(/_/g, " ").toUpperCase()}</Badge>
            </div>
          </div>

          {/* Response SLA */}
          {!ticket.first_response_at && ticket.sla_response_due_at && (
            <div>
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
            <div>
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
