"use client";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { format } from "date-fns";
import { Calendar, Clock, User, Building2, AlertCircle, CheckCircle2, XCircle, Pause } from "lucide-react";

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
    client: {
      id: string;
      company_name: string;
      domain?: string;
    };
    created_by_user: {
      id: string;
      name: string;
      avatar?: string;
    };
    assigned_user?: {
      id: string;
      name: string;
      avatar?: string;
    } | null;
  };
}

export function RequestDetail({ request }: RequestDetailProps) {
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
              <span>{request.client.company_name}</span>
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

        <div className="flex gap-2">
          <Button variant="outline">Edit</Button>
          <Button>Update Status</Button>
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
                    <AvatarImage src={request.created_by_user.avatar} />
                    <AvatarFallback>
                      {request.created_by_user.name
                        .split(" ")
                        .map((n) => n[0])
                        .join("")}
                    </AvatarFallback>
                  </Avatar>
                  <div className="flex-1">
                    <p className="text-sm">
                      <span className="font-medium">{request.created_by_user.name}</span> created this request
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
                <p className="text-sm font-medium mb-2">Assigned To</p>
                {request.assigned_user ? (
                  <div className="flex items-center gap-2">
                    <Avatar className="h-8 w-8">
                      <AvatarImage src={request.assigned_user.avatar} />
                      <AvatarFallback>
                        {request.assigned_user.name
                          .split(" ")
                          .map((n) => n[0])
                          .join("")}
                      </AvatarFallback>
                    </Avatar>
                    <span className="text-sm">{request.assigned_user.name}</span>
                  </div>
                ) : (
                  <Button variant="outline" size="sm" className="w-full">
                    <User className="mr-2 h-4 w-4" />
                    Assign
                  </Button>
                )}
              </div>

              <Separator />

              <div>
                <p className="text-sm font-medium mb-2">Status</p>
                <Badge variant={getStatusVariant(request.status)} className="w-full justify-center">
                  {getStatusIcon(request.status)}
                  <span className="ml-1.5">{request.status.replace("_", " ")}</span>
                </Badge>
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
                  <span className="text-sm">{request.client.company_name}</span>
                </div>
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
