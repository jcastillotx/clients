"use client";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { format, isPast } from "date-fns";
import { Receipt, Building2, Calendar, DollarSign, Clock, CheckCircle2, AlertCircle, Edit } from "lucide-react";

interface InvoiceDetailProps {
  invoice: {
    id: string;
    invoice_number: string;
    amount: number;
    status: string;
    due_date?: string;
    paid_at?: string;
    created_at: string;
    notes?: string;
    client: {
      id: string;
      company_name: string;
      domain?: string;
      primary_contact?: {
        id: string;
        name: string;
        email: string;
      } | null;
    };
    created_by_user: {
      id: string;
      name: string;
      avatar?: string;
    };
  };
}

export function InvoiceDetail({ invoice }: InvoiceDetailProps) {
  const isOverdue = invoice.due_date && isPast(new Date(invoice.due_date)) && invoice.status === "sent";

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div className="space-y-2">
          <div className="flex items-center gap-3">
            <Receipt className="h-8 w-8 text-primary" />
            <div>
              <h1 className="text-3xl font-bold tracking-tight">{invoice.invoice_number}</h1>
              <p className="text-muted-foreground">Invoice Details</p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <Badge variant={getStatusVariant(invoice.status)}>
              {isOverdue && <AlertCircle className="h-3 w-3 mr-1" />}
              {isOverdue ? "Overdue" : invoice.status}
            </Badge>
            {invoice.status === "paid" && invoice.paid_at && (
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <CheckCircle2 className="h-4 w-4 text-green-600" />
                Paid on {format(new Date(invoice.paid_at), "MMM d, yyyy")}
              </div>
            )}
          </div>
        </div>

        <div className="flex gap-2">
          <Button variant="outline">
            <Edit className="mr-2 h-4 w-4" />
            Edit
          </Button>
        </div>
      </div>

      <Separator />

      {/* Summary Cards */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Amount</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              ${invoice.amount.toLocaleString("en-US", { minimumFractionDigits: 2 })}
            </div>
            <p className="text-xs text-muted-foreground">Total invoice amount</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Status</CardTitle>
            <CheckCircle2 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold capitalize">{invoice.status}</div>
            <p className="text-xs text-muted-foreground">Payment status</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Created</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{format(new Date(invoice.created_at), "MMM d")}</div>
            <p className="text-xs text-muted-foreground">{format(new Date(invoice.created_at), "yyyy")}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Due Date</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            {invoice.due_date ? (
              <>
                <div className="text-2xl font-bold">{format(new Date(invoice.due_date), "MMM d")}</div>
                <p className="text-xs text-muted-foreground">{format(new Date(invoice.due_date), "yyyy")}</p>
              </>
            ) : (
              <div className="text-2xl font-bold">-</div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Client & Invoice Info Grid */}
      <div className="grid gap-6 md:grid-cols-2">
        {/* Bill To */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Bill To</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div>
              <div className="flex items-center gap-2 mb-2">
                <Building2 className="h-4 w-4 text-muted-foreground" />
                <p className="font-medium">{invoice.client.company_name}</p>
              </div>
              {invoice.client.domain && <p className="text-sm text-muted-foreground ml-6">{invoice.client.domain}</p>}
            </div>

            {invoice.client.primary_contact && (
              <>
                <Separator />
                <div>
                  <p className="text-sm font-medium mb-1">Contact</p>
                  <p className="text-sm">{invoice.client.primary_contact.name}</p>
                  <p className="text-sm text-muted-foreground">{invoice.client.primary_contact.email}</p>
                </div>
              </>
            )}
          </CardContent>
        </Card>

        {/* Invoice Details */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Invoice Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="grid grid-cols-2 gap-2">
              <div>
                <p className="text-sm font-medium mb-1">Invoice #</p>
                <p className="text-sm text-muted-foreground">{invoice.invoice_number}</p>
              </div>
              <div>
                <p className="text-sm font-medium mb-1">Created By</p>
                <p className="text-sm text-muted-foreground">{invoice.created_by_user.name}</p>
              </div>
            </div>

            <Separator />

            <div className="grid grid-cols-2 gap-2">
              <div>
                <p className="text-sm font-medium mb-1">Issue Date</p>
                <p className="text-sm text-muted-foreground">{format(new Date(invoice.created_at), "MMM d, yyyy")}</p>
              </div>
              <div>
                <p className="text-sm font-medium mb-1">Due Date</p>
                <p className="text-sm text-muted-foreground">
                  {invoice.due_date ? format(new Date(invoice.due_date), "MMM d, yyyy") : "No due date"}
                </p>
              </div>
            </div>

            {invoice.notes && (
              <>
                <Separator />
                <div>
                  <p className="text-sm font-medium mb-1">Notes</p>
                  <p className="text-sm text-muted-foreground whitespace-pre-wrap">{invoice.notes}</p>
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
    draft: "secondary",
    sent: "default",
    paid: "outline",
    overdue: "destructive",
    cancelled: "destructive",
  };
  return variants[status] || "default";
}
