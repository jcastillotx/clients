"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { format } from "date-fns";
import Link from "next/link";
import { Receipt, Plus, ArrowRight } from "lucide-react";

interface Invoice {
  id: string;
  invoice_number: string;
  amount: number;
  status: string;
  due_date?: string;
  created_at: string;
}

interface ClientInvoicesProps {
  invoices: Invoice[];
  clientId: string;
}

export function ClientInvoices({ invoices, clientId }: ClientInvoicesProps) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0">
        <CardTitle className="flex items-center gap-2">
          <Receipt className="h-5 w-5" />
          Recent Invoices ({invoices.length})
        </CardTitle>
        <Button size="sm" asChild>
          <Link href={`/invoices/new?client_id=${clientId}`}>
            <Plus className="mr-2 h-4 w-4" />
            New Invoice
          </Link>
        </Button>
      </CardHeader>
      <CardContent>
        {invoices.length === 0 ? (
          <div className="text-center py-8">
            <p className="text-sm text-muted-foreground mb-4">No invoices yet</p>
            <Button size="sm" asChild>
              <Link href={`/invoices/new?client_id=${clientId}`}>
                <Plus className="mr-2 h-4 w-4" />
                Create First Invoice
              </Link>
            </Button>
          </div>
        ) : (
          <div className="space-y-3">
            {invoices.map((invoice) => (
              <Link
                key={invoice.id}
                href={`/invoices/${invoice.id}`}
                className="block p-3 rounded-lg border hover:bg-muted/50 transition-colors group"
              >
                <div className="flex items-start justify-between mb-2">
                  <div className="flex-1">
                    <h4 className="font-medium text-sm group-hover:text-primary transition-colors">
                      {invoice.invoice_number}
                    </h4>
                    <p className="text-sm font-semibold mt-1">
                      ${invoice.amount.toLocaleString("en-US", { minimumFractionDigits: 2 })}
                    </p>
                  </div>
                  <ArrowRight className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={getStatusVariant(invoice.status)} className="text-xs">
                    {invoice.status}
                  </Badge>
                  <span className="text-xs text-muted-foreground">
                    {invoice.due_date
                      ? `Due ${format(new Date(invoice.due_date), "MMM d")}`
                      : format(new Date(invoice.created_at), "MMM d, yyyy")}
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
    draft: "secondary",
    sent: "default",
    paid: "outline",
    overdue: "destructive",
    cancelled: "destructive",
  };
  return variants[status] || "default";
}
