"use client";

import { useState, useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { formatDistanceToNow } from "date-fns";
import { useDebounce } from "@/hooks/use-debounce";
import { Building2, Mail, Phone, Globe, Plus } from "lucide-react";

interface Client {
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
  };
  _count?: {
    requests: number;
  };
}

interface ClientListProps {
  initialData: Client[];
  totalCount: number;
  currentPage: number;
  pageSize: number;
}

export function ClientList({ initialData, totalCount, currentPage, pageSize }: ClientListProps) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [search, setSearch] = useState(searchParams.get("search") || "");
  const [status, setStatus] = useState(searchParams.get("status") || "all");
  const debouncedSearch = useDebounce(search, 300);

  // Update URL when search or status changes
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

    // Reset to page 1 when filters change
    params.delete("page");

    router.push(`?${params.toString()}`);
  }, [debouncedSearch, status]);

  const totalPages = Math.ceil(totalCount / pageSize);

  return (
    <div className="space-y-4">
      {/* Filters */}
      <div className="flex gap-4">
        <div className="relative flex-1 max-w-sm">
          <Input
            placeholder="Search clients..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-10"
          />
          <Building2 className="absolute left-3 top-2.5 h-5 w-5 text-muted-foreground" />
        </div>
        <Select value={status} onValueChange={setStatus}>
          <SelectTrigger className="w-[180px]">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Statuses</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="inactive">Inactive</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="suspended">Suspended</SelectItem>
          </SelectContent>
        </Select>
        <Button onClick={() => router.push("/clients/new")}>
          <Plus className="mr-2 h-4 w-4" />
          New Client
        </Button>
      </div>

      {/* Client Cards Grid */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {initialData.length === 0 ? (
          <Card className="col-span-full">
            <CardContent className="flex flex-col items-center justify-center py-12">
              <Building2 className="h-12 w-12 text-muted-foreground mb-4" />
              <p className="text-lg font-medium text-muted-foreground">No clients found</p>
              <p className="text-sm text-muted-foreground">Try adjusting your search filters</p>
            </CardContent>
          </Card>
        ) : (
          initialData.map((client) => (
            <Card
              key={client.id}
              className="cursor-pointer transition-all hover:shadow-lg hover:scale-[1.02]"
              onClick={() => router.push(`/clients/${client.id}`)}
            >
              <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <h3 className="font-semibold text-lg leading-none mb-2">{client.company_name}</h3>
                    {client.industry && <p className="text-sm text-muted-foreground">{client.industry}</p>}
                  </div>
                  <Badge variant={getStatusVariant(client.status)}>{client.status}</Badge>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                {client.domain && (
                  <div className="flex items-center gap-2 text-sm">
                    <Globe className="h-4 w-4 text-muted-foreground" />
                    <a
                      href={`https://${client.domain}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      onClick={(e) => e.stopPropagation()}
                      className="text-primary hover:underline"
                    >
                      {client.domain}
                    </a>
                  </div>
                )}

                {client.primary_contact && (
                  <div className="space-y-1.5 pt-2 border-t">
                    <div className="flex items-center gap-2 text-sm">
                      <Mail className="h-4 w-4 text-muted-foreground" />
                      <span className="text-muted-foreground">{client.primary_contact.email}</span>
                    </div>
                    {client.primary_contact.phone && (
                      <div className="flex items-center gap-2 text-sm">
                        <Phone className="h-4 w-4 text-muted-foreground" />
                        <span className="text-muted-foreground">{client.primary_contact.phone}</span>
                      </div>
                    )}
                  </div>
                )}

                <div className="flex items-center justify-between pt-2 border-t text-xs text-muted-foreground">
                  <span>{client._count?.requests || 0} requests</span>
                  <span>
                    {formatDistanceToNow(new Date(client.created_at), {
                      addSuffix: true,
                    })}
                  </span>
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-muted-foreground">
            Showing {(currentPage - 1) * pageSize + 1} to {Math.min(currentPage * pageSize, totalCount)} of {totalCount}{" "}
            clients
          </p>
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={currentPage === 1}
              onClick={() => {
                const params = new URLSearchParams(searchParams);
                params.set("page", String(currentPage - 1));
                router.push(`?${params.toString()}`);
              }}
            >
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={currentPage === totalPages}
              onClick={() => {
                const params = new URLSearchParams(searchParams);
                params.set("page", String(currentPage + 1));
                router.push(`?${params.toString()}`);
              }}
            >
              Next
            </Button>
          </div>
        </div>
      )}
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
