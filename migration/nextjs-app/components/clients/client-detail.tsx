"use client";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { format } from "date-fns";
import Link from "next/link";
import { Building2, Globe, Mail, Phone, Calendar, Users, FileText, DollarSign, Edit } from "lucide-react";

interface ClientDetailProps {
  client: {
    id: string;
    company_name: string;
    domain?: string;
    industry?: string;
    status: string;
    created_at: string;
    primary_contact?: {
      id: string;
      name: string;
      email: string;
      phone?: string;
      avatar?: string;
    } | null;
  };
  staffAssignments: Array<{
    id: string;
    role: string;
    user: {
      id: string;
      name: string;
      email: string;
      avatar?: string;
    };
  }>;
  stats: {
    totalRequests: number;
    openRequests: number;
    totalRevenue: number;
    paidRevenue: number;
  };
}

export function ClientDetail({ client, staffAssignments, stats }: ClientDetailProps) {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div className="space-y-2">
          <div className="flex items-center gap-3">
            <Building2 className="h-8 w-8 text-primary" />
            <div>
              <h1 className="text-3xl font-bold tracking-tight">{client.company_name}</h1>
              {client.industry && <p className="text-muted-foreground">{client.industry}</p>}
            </div>
          </div>
          <div className="flex items-center gap-4">
            <Badge variant={getStatusVariant(client.status)}>{client.status}</Badge>
            {client.domain && (
              <a
                href={`https://${client.domain}`}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2 text-sm text-primary hover:underline"
              >
                <Globe className="h-4 w-4" />
                {client.domain}
              </a>
            )}
          </div>
        </div>

        <Button asChild>
          <Link href={`/clients/${client.id}/edit`}>
            <Edit className="mr-2 h-4 w-4" />
            Edit Client
          </Link>
        </Button>
      </div>

      <Separator />

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Requests</CardTitle>
            <FileText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.totalRequests}</div>
            <p className="text-xs text-muted-foreground">{stats.openRequests} open</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">${stats.totalRevenue.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">All time</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Paid Revenue</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">${stats.paidRevenue.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">Collected</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Team Members</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{staffAssignments.length}</div>
            <p className="text-xs text-muted-foreground">Assigned staff</p>
          </CardContent>
        </Card>
      </div>

      {/* Details Grid */}
      <div className="grid gap-6 md:grid-cols-3">
        {/* Primary Contact */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Primary Contact</CardTitle>
          </CardHeader>
          <CardContent>
            {client.primary_contact ? (
              <div className="space-y-4">
                <div className="flex items-center gap-3">
                  <Avatar className="h-12 w-12">
                    <AvatarImage src={client.primary_contact.avatar} />
                    <AvatarFallback>
                      {client.primary_contact.name
                        .split(" ")
                        .map((n) => n[0])
                        .join("")}
                    </AvatarFallback>
                  </Avatar>
                  <div>
                    <p className="font-medium">{client.primary_contact.name}</p>
                    <p className="text-sm text-muted-foreground">Primary Contact</p>
                  </div>
                </div>
                <div className="space-y-2">
                  <div className="flex items-center gap-2 text-sm">
                    <Mail className="h-4 w-4 text-muted-foreground" />
                    <a href={`mailto:${client.primary_contact.email}`} className="hover:underline">
                      {client.primary_contact.email}
                    </a>
                  </div>
                  {client.primary_contact.phone && (
                    <div className="flex items-center gap-2 text-sm">
                      <Phone className="h-4 w-4 text-muted-foreground" />
                      <a href={`tel:${client.primary_contact.phone}`} className="hover:underline">
                        {client.primary_contact.phone}
                      </a>
                    </div>
                  )}
                </div>
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">No primary contact assigned</p>
            )}
          </CardContent>
        </Card>

        {/* Staff Assignments */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Assigned Staff ({staffAssignments.length})</CardTitle>
          </CardHeader>
          <CardContent>
            {staffAssignments.length === 0 ? (
              <p className="text-sm text-muted-foreground">No staff assigned</p>
            ) : (
              <div className="space-y-3">
                {staffAssignments.slice(0, 3).map((assignment) => (
                  <div key={assignment.id} className="flex items-center gap-3">
                    <Avatar className="h-8 w-8">
                      <AvatarImage src={assignment.user.avatar} />
                      <AvatarFallback>
                        {assignment.user.name
                          .split(" ")
                          .map((n) => n[0])
                          .join("")}
                      </AvatarFallback>
                    </Avatar>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium truncate">{assignment.user.name}</p>
                      <p className="text-xs text-muted-foreground truncate">{assignment.role}</p>
                    </div>
                  </div>
                ))}
                {staffAssignments.length > 3 && (
                  <p className="text-xs text-muted-foreground">+{staffAssignments.length - 3} more</p>
                )}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Client Info */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Client Information</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div>
              <p className="text-sm font-medium mb-1">Status</p>
              <Badge variant={getStatusVariant(client.status)}>{client.status}</Badge>
            </div>
            <Separator />
            <div>
              <p className="text-sm font-medium mb-1">Member Since</p>
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Calendar className="h-4 w-4" />
                {format(new Date(client.created_at), "MMMM d, yyyy")}
              </div>
            </div>
            {client.industry && (
              <>
                <Separator />
                <div>
                  <p className="text-sm font-medium mb-1">Industry</p>
                  <p className="text-sm text-muted-foreground">{client.industry}</p>
                </div>
              </>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

function getStatusVariant(status: string): "default" | "secondary" | "destructive" | "outline" {
  const variants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
    active: "default",
    inactive: "secondary",
    pending: "outline",
    suspended: "destructive",
  };
  return variants[status] || "default";
}
