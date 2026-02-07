"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { formatDistanceToNow } from "date-fns";
import Link from "next/link";
import { FileText } from "lucide-react";

interface Request {
  id: string;
  title: string;
  status: string;
  priority: string;
  created_at: string;
  client: {
    company_name: string;
  };
}

interface RecentActivityProps {
  requests: Request[];
}

export function RecentActivity({ requests }: RecentActivityProps) {
  return (
    <Card className="bg-gradient-to-br from-card to-secondary/20">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <FileText className="h-5 w-5" />
          Recent Requests
        </CardTitle>
      </CardHeader>
      <CardContent>
        {requests.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted-foreground">No recent requests</p>
        ) : (
          <div className="space-y-4">
            {requests.map((request) => (
              <Link
                key={request.id}
                href={`/requests/${request.id}`}
                className="block rounded-xl border border-border/70 bg-background/80 p-3.5 transition-all hover:-translate-y-0.5 hover:border-primary/30 hover:bg-primary/5"
              >
                <div className="flex items-start justify-between mb-2">
                  <h4 className="font-medium text-sm">{request.title}</h4>
                  <Badge variant={getStatusVariant(request.status)} className="ml-2 shrink-0">
                    {request.status.replace("_", " ")}
                  </Badge>
                </div>
                <div className="flex items-center justify-between text-xs text-muted-foreground">
                  <span>{request.client.company_name}</span>
                  <span>{formatDistanceToNow(new Date(request.created_at), { addSuffix: true })}</span>
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
