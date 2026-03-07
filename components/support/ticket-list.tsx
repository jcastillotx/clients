"use client";

import { useState, useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { formatDistanceToNow } from "date-fns";
import { useDebounce } from "@/hooks/use-debounce";
import { getSlaStatus, formatTimeRemaining } from "@/lib/utils/sla";

interface SupportTicket {
  id: string;
  ticket_number: string;
  subject: string;
  status: string;
  priority: string;
  category: string;
  created_at: string;
  first_response_at: string | null;
  sla_response_due_at: string | null;
  sla_resolution_due_at: string | null;
  sla_response_breached: boolean;
  sla_resolution_breached: boolean;
  sla_paused: boolean;
  sla_paused_duration_minutes: number;
  client: { company_name: string };
  assigned_user: { name: string; avatar?: string } | null;
}

export function SupportTicketList({ initialData }: { initialData: SupportTicket[] }) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [search, setSearch] = useState(searchParams.get("search") || "");
  const [status, setStatus] = useState(searchParams.get("status") || "all");
  const [priority, setPriority] = useState(searchParams.get("priority") || "all");
  const [category, setCategory] = useState(searchParams.get("category") || "all");
  const debouncedSearch = useDebounce(search, 300);

  // Update URL when filters change
  useEffect(() => {
    const params = new URLSearchParams(searchParams);

    if (debouncedSearch) {
      params.set("search", debouncedSearch);
    } else {
      params.delete("search");
    }

    if (status && status !== "all") {
      params.set("status", status);
    } else {
      params.delete("status");
    }

    if (priority && priority !== "all") {
      params.set("priority", priority);
    } else {
      params.delete("priority");
    }

    if (category && category !== "all") {
      params.set("category", category);
    } else {
      params.delete("category");
    }

    router.push(`?${params.toString()}`);
  }, [debouncedSearch, status, priority, category, router, searchParams]);

  return (
    <div className="space-y-4">
      {/* Filters */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Input
          placeholder="Search tickets..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="md:col-span-1"
        />
        <Select value={status} onValueChange={setStatus}>
          <SelectTrigger>
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Statuses</SelectItem>
            <SelectItem value="open">Open</SelectItem>
            <SelectItem value="in_progress">In Progress</SelectItem>
            <SelectItem value="waiting_on_client">Waiting on Client</SelectItem>
            <SelectItem value="waiting_on_vendor">Waiting on Vendor</SelectItem>
            <SelectItem value="resolved">Resolved</SelectItem>
            <SelectItem value="closed">Closed</SelectItem>
          </SelectContent>
        </Select>
        <Select value={priority} onValueChange={setPriority}>
          <SelectTrigger>
            <SelectValue placeholder="Filter by priority" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Priorities</SelectItem>
            <SelectItem value="low">Low</SelectItem>
            <SelectItem value="medium">Medium</SelectItem>
            <SelectItem value="high">High</SelectItem>
            <SelectItem value="urgent">Urgent</SelectItem>
          </SelectContent>
        </Select>
        <Select value={category} onValueChange={setCategory}>
          <SelectTrigger>
            <SelectValue placeholder="Filter by category" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Categories</SelectItem>
            <SelectItem value="technical">Technical</SelectItem>
            <SelectItem value="billing">Billing</SelectItem>
            <SelectItem value="general">General</SelectItem>
            <SelectItem value="feature_request">Feature Request</SelectItem>
            <SelectItem value="bug_report">Bug Report</SelectItem>
            <SelectItem value="security">Security</SelectItem>
            <SelectItem value="performance">Performance</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Table */}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Ticket #</TableHead>
              <TableHead>Subject</TableHead>
              <TableHead>Acknowledged</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Priority</TableHead>
              <TableHead>SLA Status</TableHead>
              <TableHead>Assigned To</TableHead>
              <TableHead>Created</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {initialData.length === 0 ? (
              <TableRow>
                <TableCell colSpan={8} className="text-center text-muted-foreground">
                  No tickets found
                </TableCell>
              </TableRow>
            ) : (
              initialData.map((ticket) => {
                const isAcknowledged = Boolean(ticket.first_response_at);
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
                const responseDueAt = ticket.sla_response_due_at ? new Date(ticket.sla_response_due_at) : null;
                const resolutionDueAt = ticket.sla_resolution_due_at ? new Date(ticket.sla_resolution_due_at) : null;
                const primaryDueLabel = isAcknowledged
                  ? formatTimeRemaining(resolutionDueAt)
                  : formatTimeRemaining(responseDueAt);

                return (
                  <TableRow
                    key={ticket.id}
                    className={`cursor-pointer border-l-4 transition-colors hover:bg-muted/50 ${getTicketRowClassName(slaInfo.status, isAcknowledged)}`}
                    onClick={() => router.push(`/support/${ticket.id}`)}
                  >
                    <TableCell className="font-mono text-sm">{ticket.ticket_number}</TableCell>
                    <TableCell className="font-medium max-w-xs truncate">{ticket.subject}</TableCell>
                    <TableCell>
                      <Badge variant={isAcknowledged ? "default" : "secondary"} className={getAcknowledgementBadgeClassName(isAcknowledged, slaInfo.status)}>
                        {isAcknowledged ? "Acknowledged" : "Awaiting staff"}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant={getStatusVariant(ticket.status)}>{ticket.status.replace(/_/g, " ")}</Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant={getPriorityVariant(ticket.priority)}>{ticket.priority}</Badge>
                    </TableCell>
                    <TableCell>
                      <div className="space-y-1">
                        <Badge variant={getSlaVariant(slaInfo.status)} className={getSlaStatusBadgeClassName(slaInfo.status)}>
                          {slaInfo.status.replace(/_/g, " ")}
                        </Badge>
                        <div className="text-xs text-muted-foreground">
                          {primaryDueLabel
                            ? isAcknowledged
                              ? `Resolution due in ${primaryDueLabel}`
                              : `Response due in ${primaryDueLabel}`
                            : isAcknowledged
                              ? "Staff has responded"
                              : "Awaiting response"}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>{ticket.assigned_user?.name || "Unassigned"}</TableCell>
                    <TableCell className="text-muted-foreground">
                      {formatDistanceToNow(new Date(ticket.created_at), { addSuffix: true })}
                    </TableCell>
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </div>
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

function getTicketRowClassName(slaStatus: string, isAcknowledged: boolean): string {
  if (slaStatus === "breached") {
    return "border-l-red-600 bg-red-50/80";
  }

  if (slaStatus === "response_breached") {
    return "border-l-orange-600 bg-orange-50/80";
  }

  if (!isAcknowledged) {
    if (slaStatus === "warning") {
      return "border-l-amber-500 bg-amber-50/80";
    }

    return "border-l-yellow-500 bg-yellow-50/70";
  }

  if (slaStatus === "paused") {
    return "border-l-slate-400 bg-slate-50/70";
  }

  if (slaStatus === "warning") {
    return "border-l-blue-500 bg-blue-50/70";
  }

  return "border-l-emerald-500 bg-emerald-50/60";
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

function getSlaStatusBadgeClassName(slaStatus: string): string {
  const classes: Record<string, string> = {
    on_track: "bg-green-100 text-green-800 hover:bg-green-100",
    warning: "bg-yellow-100 text-yellow-800 hover:bg-yellow-100",
    response_breached: "bg-orange-100 text-orange-800 hover:bg-orange-100",
    breached: "bg-red-100 text-red-800 hover:bg-red-100",
    paused: "bg-slate-100 text-slate-800 hover:bg-slate-100",
  };

  return classes[slaStatus] || "";
}
