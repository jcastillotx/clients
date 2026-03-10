"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useToast } from "@/hooks/use-toast";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { format } from "date-fns";
import { Calendar, Clock, User, Building2, AlertCircle, CheckCircle2, XCircle, Pause, Trash2 } from "lucide-react";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";

interface RequestDetailProps {
  request: {
    id: string;
    title: string;
    description?: string;
    status: string;
    priority: string;
    created_at: string;
    updated_at: string;
    due_date?: string;
    custom_fields?: Record<string, any>;
    client: {
      id: string;
      company_name: string;
      domain?: string;
    } | null;
    created_by_user: {
      id: string;
      name: string;
      avatar?: string;
    } | null;
    assigned_user?: {
      id: string;
      name: string;
      avatar?: string;
    } | null;
  };
  assignableUsers?: Array<{
    id: string;
    name: string;
    email?: string | null;
  }>;
  canManageWorkflow?: boolean;
}

const requestStatuses = [
  "pending",
  "in_progress",
  "awaiting_approval",
  "approved",
  "on_hold",
  "rejected",
  "completed",
  "cancelled",
] as const;

export function RequestDetail({ request, assignableUsers = [], canManageWorkflow = false }: RequestDetailProps) {
  const router = useRouter();
  const { toast } = useToast();
  const [currentStatus, setCurrentStatus] = useState(request.status);
  const [assignedToId, setAssignedToId] = useState<string>(request.assigned_user?.id || "unassigned");
  const [isSaving, setIsSaving] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const requestType = String(request.custom_fields?.type || "support");
  const createdByName = request.created_by_user?.name || "Unknown user";
  const createdByInitials =
    createdByName
      .split(" ")
      .map((n) => n[0])
      .join("") || "?";
  const assignedUserName = request.assigned_user?.name || "Unassigned";
  const assignedUserInitials =
    assignedUserName
      .split(" ")
      .map((n) => n[0])
      .join("") || "?";

  const doDeleteRequest = async () => {
    setIsDeleting(true);
    try {
      const res = await fetch(`/api/requests/${request.id}`, { method: "DELETE", credentials: "same-origin" });
      if (!res.ok) {
        const body = await res.json();
        throw new Error(body?.error || "Failed to delete request");
      }
      toast({ title: "Deleted", description: "Service request deleted." });
      router.push("/requests");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to delete request");
    } finally {
      setIsDeleting(false);
    }
  };

  const updateRequest = async (payload: Record<string, any>) => {
    setIsSaving(true);
    setError(null);
    try {
      const response = await fetch(`/api/requests/${request.id}`, {
        method: "PATCH",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const body = await response.json();
      if (!response.ok) {
        throw new Error(body?.error || "Failed to update request");
      }
      if (payload.status !== undefined) {
        setCurrentStatus(payload.status);
      }
      if (payload.assignedTo !== undefined) {
        setAssignedToId(payload.assignedTo || "unassigned");
      }
      toast({ title: "Saved", description: "Request updated successfully." });
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to update request");
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div className="space-y-2">
          <div className="flex items-center gap-3">
            <h1 className="text-3xl font-bold tracking-tight">{request.title}</h1>
            <Badge variant={getStatusVariant(request.status)}>
              {getStatusIcon(request.status)}
              <span className="ml-1.5">{request.status.replace("_", " ")}</span>
            </Badge>
            <Badge variant={getPriorityVariant(request.priority)}>{request.priority}</Badge>
          </div>
          <div className="flex items-center gap-4 text-sm text-muted-foreground">
            <div className="flex items-center gap-1.5">
              <Building2 className="h-4 w-4" />
              <span>{request.client?.company_name || "Unknown client"}</span>
            </div>
            <div className="flex items-center gap-1.5">
              <Calendar className="h-4 w-4" />
              <span>Created {format(new Date(request.created_at), "MMM d, yyyy")}</span>
            </div>
            {request.due_date && (
              <div className="flex items-center gap-1.5">
                <Clock className="h-4 w-4" />
                <span>Due {format(new Date(request.due_date), "MMM d, yyyy")}</span>
              </div>
            )}
          </div>
        </div>

        <div className="flex flex-col items-end gap-2">
          {error ? <p className="text-xs text-destructive">{error}</p> : null}
          <div className="flex gap-2">
            <Button variant="outline" onClick={() => router.back()}>Back</Button>
            {canManageWorkflow && (
              <>
                <Button disabled={isSaving} onClick={() => updateRequest({ status: currentStatus })}>
                  Update Status
                </Button>
                <Button variant="destructive" size="icon" disabled={isDeleting} onClick={() => setConfirmOpen(true)} title="Delete request">
                  <Trash2 className="h-4 w-4" />
                </Button>
              </>
            )}
          </div>
        </div>
      </div>

      <Separator />

      <div className="grid gap-6 md:grid-cols-3">
        {/* Main Content */}
        <div className="md:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Description</CardTitle>
            </CardHeader>
            <CardContent>
              {request.description ? (
                <p className="text-muted-foreground whitespace-pre-wrap">{request.description}</p>
              ) : (
                <p className="text-muted-foreground italic">No description provided</p>
              )}
            </CardContent>
          </Card>

          {/* Activity/Timeline would go here */}
          <Card>
            <CardHeader>
              <CardTitle>Activity</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex gap-4">
                  <Avatar className="h-8 w-8">
                    <AvatarImage src={request.created_by_user?.avatar} />
                    <AvatarFallback>
                      {createdByInitials}
                    </AvatarFallback>
                  </Avatar>
                  <div className="flex-1">
                    <p className="text-sm">
                      <span className="font-medium">{createdByName}</span> created this request
                    </p>
                    <p className="text-xs text-muted-foreground">{format(new Date(request.created_at), "PPpp")}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Details</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <p className="text-sm font-medium mb-2">Type</p>
                <Badge variant="secondary" className="w-full justify-center capitalize">
                  {requestType.replace(/_/g, " ")}
                </Badge>
              </div>

              <Separator />

              <div>
                <p className="text-sm font-medium mb-2">Status</p>
                {canManageWorkflow ? (
                  <Select value={currentStatus} onValueChange={(value) => setCurrentStatus(value)}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {requestStatuses.map((status) => (
                        <SelectItem key={status} value={status}>
                          {status.replace(/_/g, " ")}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                ) : (
                  <Badge variant={getStatusVariant(request.status)} className="w-full justify-center">
                    {getStatusIcon(request.status)}
                    <span className="ml-1.5">{request.status.replace("_", " ")}</span>
                  </Badge>
                )}
              </div>

              <Separator />

              <div>
                <p className="text-sm font-medium mb-2">Priority</p>
                <Badge variant={getPriorityVariant(request.priority)} className="w-full justify-center">
                  {request.priority}
                </Badge>
              </div>

              <Separator />

              <div>
                <p className="text-sm font-medium mb-2">Client</p>
                <div className="flex items-center gap-2">
                  <Building2 className="h-4 w-4 text-muted-foreground" />
                  <span className="text-sm">{request.client?.company_name || "Unknown client"}</span>
                </div>
              </div>

              <Separator />

              <div>
                <p className="text-sm font-medium mb-2">Assigned To</p>
                {canManageWorkflow ? (
                  <div className="space-y-2">
                    <Select value={assignedToId} onValueChange={(value) => setAssignedToId(value)}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="unassigned">Unassigned</SelectItem>
                        {assignableUsers.map((u) => (
                          <SelectItem key={u.id} value={u.id}>
                            {u.name || u.email || "User"}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <Button
                      size="sm"
                      className="w-full"
                      variant="outline"
                      disabled={isSaving}
                      onClick={() => updateRequest({ assignedTo: assignedToId === "unassigned" ? null : assignedToId })}
                    >
                      <User className="mr-2 h-4 w-4" />
                      Save Assignment
                    </Button>
                  </div>
                ) : request.assigned_user ? (
                  <div className="flex items-center gap-2">
                    <Avatar className="h-8 w-8">
                      <AvatarImage src={request.assigned_user.avatar} />
                      <AvatarFallback>{assignedUserInitials}</AvatarFallback>
                    </Avatar>
                    <span className="text-sm">{assignedUserName}</span>
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground">Unassigned</p>
                )}
              </div>

              <Separator />

              <div>
                <p className="text-sm font-medium mb-2">Last Updated</p>
                <p className="text-sm text-muted-foreground">{format(new Date(request.updated_at), "PPpp")}</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
      <ConfirmDialog
        open={confirmOpen}
        onOpenChange={setConfirmOpen}
        title="Delete service request?"
        description="This will permanently delete the request and all associated comments. This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={doDeleteRequest}
        loading={isDeleting}
      />
    </div>
  );
}

function getStatusVariant(status: string): "default" | "secondary" | "destructive" | "outline" {
  const variants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
    pending: "secondary",
    in_progress: "default",
    completed: "outline",
    cancelled: "destructive",
    on_hold: "secondary",
  };
  return variants[status] || "default";
}

function getPriorityVariant(priority: string): "default" | "secondary" | "destructive" {
  const variants: Record<string, "default" | "secondary" | "destructive"> = {
    low: "secondary",
    medium: "default",
    high: "destructive",
  };
  return variants[priority] || "default";
}

function getStatusIcon(status: string) {
  const icons: Record<string, React.ReactNode> = {
    pending: <AlertCircle className="h-3.5 w-3.5" />,
    in_progress: <Clock className="h-3.5 w-3.5" />,
    completed: <CheckCircle2 className="h-3.5 w-3.5" />,
    cancelled: <XCircle className="h-3.5 w-3.5" />,
    on_hold: <Pause className="h-3.5 w-3.5" />,
  };
  return icons[status] || <AlertCircle className="h-3.5 w-3.5" />;
}
