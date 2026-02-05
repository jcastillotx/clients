"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { formatDistanceToNow } from "date-fns";
import Link from "next/link";
import { FileText, Plus, ArrowRight } from "lucide-react";

interface Request {
  id: string;
  title: string;
  status: string;
  priority: string;
  created_at: string;
  assigned_user?: {
    id: string;
    name: string;
    avatar?: string;
  } | null;
}

interface ClientRequestsProps {
  requests: Request[];
  clientId: string;
}

export function ClientRequests({ requests, clientId }: ClientRequestsProps) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0">
        <CardTitle className="flex items-center gap-2">
          <FileText className="h-5 w-5" />
          Recent Requests ({requests.length})
        </CardTitle>
        <Button size="sm" asChild>
          <Link href={`/requests/new?client_id=${clientId}`}>
            <Plus className="mr-2 h-4 w-4" />
            New Request
          </Link>
        </Button>
      </CardHeader>
      <CardContent>
        {requests.length === 0 ? (
          <div className="text-center py-8">
            <p className="text-sm text-muted-foreground mb-4">No requests yet</p>
            <Button size="sm" asChild>
              <Link href={`/requests/new?client_id=${clientId}`}>
                <Plus className="mr-2 h-4 w-4" />
                Create First Request
              </Link>
            </Button>
          </div>
        ) : (
          <div className="space-y-3">
            {requests.map((request) => (
              <Link
                key={request.id}
                href={`/requests/${request.id}`}
                className="block p-3 rounded-lg border hover:bg-muted/50 transition-colors group"
              >
                <div className="flex items-start justify-between mb-2">
                  <h4 className="font-medium text-sm group-hover:text-primary transition-colors">{request.title}</h4>
                  <ArrowRight className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={getStatusVariant(request.status)} className="text-xs">
                    {request.status.replace("_", " ")}
                  </Badge>
                  <Badge variant={getPriorityVariant(request.priority)} className="text-xs">
                    {request.priority}
                  </Badge>
                  <span className="text-xs text-muted-foreground">
                    {formatDistanceToNow(new Date(request.created_at), { addSuffix: true })}
                  </span>
                </div>
              </Link>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function getStatusVariant(status: string): "default" | "secondary" | "destructive" | "outline" {
  const variants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
    pending: "secondary",
    in_progress: "default",
    completed: "outline",
    cancelled: "destructive",
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
