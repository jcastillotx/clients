"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { formatDistanceToNow } from "date-fns";
import Link from "next/link";
import { LifeBuoy } from "lucide-react";

interface Ticket {
  id: string;
  ticket_number: string;
  subject: string;
  status: string;
  priority: string;
  created_at: string;
  first_response_at?: string | null;
  client: {
    company_name: string;
  };
}

interface SupportOverviewProps {
  tickets: Ticket[];
}

export function SupportOverview({ tickets }: SupportOverviewProps) {
  return (
    <Card className="bg-gradient-to-br from-card to-secondary/20">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <LifeBuoy className="h-5 w-5" />
          Recent Tickets
        </CardTitle>
      </CardHeader>
      <CardContent>
        {tickets.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted-foreground">No recent tickets</p>
        ) : (
          <div className="space-y-4">
            {tickets.map((ticket) => {
              const isAcknowledged = Boolean(ticket.first_response_at);
              return (
                <Link
                  key={ticket.id}
                  href={`/support/${ticket.id}`}
                  className="block rounded-xl border border-border/70 bg-background/80 p-3.5 transition-all hover:-translate-y-0.5 hover:border-primary/30 hover:bg-primary/5"
                >
                  <div className="mb-2 flex items-start justify-between gap-2">
                    <div>
                      <p className="text-xs font-mono text-muted-foreground">{ticket.ticket_number}</p>
                      <h4 className="font-medium text-sm">{ticket.subject}</h4>
                    </div>
                    <Badge variant={getStatusVariant(ticket.status)} className="ml-2 shrink-0 capitalize">
                      {ticket.status.replace(/_/g, " ")}
                    </Badge>
                  </div>
                  <div className="mb-2 flex flex-wrap items-center gap-2">
                    <Badge variant="outline" className={getPriorityClassName(ticket.priority)}>
                      {ticket.priority}
                    </Badge>
                    <Badge variant="outline" className={isAcknowledged ? "border-emerald-200 bg-emerald-50 text-emerald-700" : "border-amber-200 bg-amber-50 text-amber-700"}>
                      {isAcknowledged ? "Acknowledged" : "Awaiting staff"}
                    </Badge>
                  </div>
                  <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <span>{ticket.client.company_name}</span>
                    <span>{formatDistanceToNow(new Date(ticket.created_at), { addSuffix: true })}</span>
                  </div>
                </Link>
              );
            })}
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function getStatusVariant(status: string): "default" | "secondary" | "destructive" | "outline" {
  const variants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
    open: "secondary",
    in_progress: "default",
    waiting_on_client: "outline",
    resolved: "outline",
    closed: "outline",
    cancelled: "destructive",
  };
  return variants[status] || "default";
}

function getPriorityClassName(priority: string): string {
  const variants: Record<string, string> = {
    low: "border-slate-200 bg-slate-50 text-slate-700",
    medium: "border-blue-200 bg-blue-50 text-blue-700",
    high: "border-amber-200 bg-amber-50 text-amber-700",
    urgent: "border-red-200 bg-red-50 text-red-700",
  };
  return variants[priority] || "border-slate-200 bg-slate-50 text-slate-700";
}
