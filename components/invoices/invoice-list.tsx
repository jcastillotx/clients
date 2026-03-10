"use client";

import { useState, useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { format, isPast } from "date-fns";
import { useDebounce } from "@/hooks/use-debounce";
import { Search, DollarSign, TrendingUp, Clock, Plus, AlertCircle, FileText } from "lucide-react";
import { EmptyState } from "@/components/ui/empty-state";

interface Invoice {
  id: string;
  invoice_number: string;
  amount: number;
  status: string;
  due_date?: string;
  created_at: string;
  client: {
    id: string;
    company_name: string;
  };
  created_by_user: {
    id: string;
    name: string;
  };
}

interface InvoiceListProps {
  initialData: Invoice[];
  totalCount: number;
  currentPage: number;
  pageSize: number;
  stats: {
    totalRevenue: number;
    paidRevenue: number;
    pendingRevenue: number;
  };
  canCreateInvoices: boolean;
}

export function InvoiceList({ initialData, totalCount, currentPage, pageSize, stats, canCreateInvoices }: InvoiceListProps) {
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

    params.delete("page");
    router.push(`?${params.toString()}`);
  }, [debouncedSearch, status]);

  const totalPages = Math.ceil(totalCount / pageSize);

  return (
    <div className="space-y-6">
      {/* Revenue Stats */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" aria-hidden="true" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">${stats.totalRevenue.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">All time</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Paid Revenue</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" aria-hidden="true" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">${stats.paidRevenue.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">Collected</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pending Revenue</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" aria-hidden="true" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">${stats.pendingRevenue.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">Outstanding</p>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <div className="flex gap-4">
        <div className="relative flex-1 max-w-sm">
          <label htmlFor="invoice-search" className="sr-only">Search invoices</label>
          <Input
            id="invoice-search"
            placeholder="Search invoices..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-10"
          />
          <Search className="absolute left-3 top-2.5 h-5 w-5 text-muted-foreground" aria-hidden="true" />
        </div>
        <Select value={status} onValueChange={setStatus}>
          <SelectTrigger className="w-[180px]">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Statuses</SelectItem>
            <SelectItem value="draft">Draft</SelectItem>
            <SelectItem value="sent">Sent</SelectItem>
            <SelectItem value="paid">Paid</SelectItem>
            <SelectItem value="overdue">Overdue</SelectItem>
            <SelectItem value="cancelled">Cancelled</SelectItem>
          </SelectContent>
        </Select>
        {canCreateInvoices && (
          <Button onClick={() => router.push("/invoices/new")}>
            <Plus className="mr-2 h-4 w-4" />
            New Invoice
          </Button>
        )}
      </div>

      {/* Invoice Table */}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Invoice #</TableHead>
              <TableHead>Client</TableHead>
              <TableHead>Amount</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Due Date</TableHead>
              <TableHead>Created</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {initialData.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="p-0">
                  <EmptyState
                    icon={FileText}
                    title="No invoices found"
                    description={search || status !== "all" ? "Try adjusting your search or filters." : "You don't have any invoices yet."}
                    action={canCreateInvoices && !search && status === "all" ? {
                      label: "Create Invoice",
                      onClick: () => router.push("/invoices/new"),
                    } : undefined}
                  />
                </TableCell>
              </TableRow>
            ) : (
              initialData.map((invoice) => {
                const isOverdue = invoice.due_date && isPast(new Date(invoice.due_date)) && invoice.status === "sent";
                return (
                  <TableRow
                    key={invoice.id}
                    className="cursor-pointer hover:bg-muted/50 focus-within:bg-muted/50"
                    onClick={() => router.push(`/invoices/${invoice.id}`)}
                    onKeyDown={(e) => {
                      if (e.key === "Enter" || e.key === " ") {
                        e.preventDefault();
                        router.push(`/invoices/${invoice.id}`);
                      }
                    }}
                    tabIndex={0}
                    role="link"
                    aria-label={`Invoice ${invoice.invoice_number} for ${invoice.client.company_name}`}
                  >
                    <TableCell className="font-medium">{invoice.invoice_number}</TableCell>
                    <TableCell>{invoice.client.company_name}</TableCell>
                    <TableCell className="font-semibold">
                      ${invoice.amount.toLocaleString("en-US", { minimumFractionDigits: 2 })}
                    </TableCell>
                    <TableCell>
                      <Badge variant={getStatusVariant(invoice.status)}>
                        {isOverdue && <AlertCircle className="h-3 w-3 mr-1" aria-hidden="true" />}
                        {isOverdue ? "Overdue" : invoice.status}
                      </Badge>
                    </TableCell>
                    <TableCell>{invoice.due_date ? format(new Date(invoice.due_date), "MMM d, yyyy") : "-"}</TableCell>
                    <TableCell className="text-muted-foreground">
                      {format(new Date(invoice.created_at), "MMM d, yyyy")}
                    </TableCell>
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-muted-foreground">
            Showing {(currentPage - 1) * pageSize + 1} to {Math.min(currentPage * pageSize, totalCount)} of {totalCount}{" "}
            invoices
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
    draft: "secondary",
    sent: "default",
    paid: "outline",
    overdue: "destructive",
    cancelled: "destructive",
  };
  return variants[status] || "default";
}
