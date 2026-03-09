"use client";

import Link from "next/link";
import { format, isPast } from "date-fns";
import { AlertCircle, Receipt } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

interface InvoiceItem {
  id: string;
  invoice_number: string;
  amount: number;
  status: string;
  due_date?: string | null;
  created_at: string;
  client: {
    company_name: string;
  } | null;
}

interface LatestInvoicesProps {
  invoices: InvoiceItem[];
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

export function LatestInvoices({ invoices }: LatestInvoicesProps) {
  return (
    <Card className="bg-gradient-to-br from-card to-secondary/20">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Receipt className="h-5 w-5" />
          Latest Invoices
        </CardTitle>
      </CardHeader>
      <CardContent>
        {invoices.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted-foreground">No recent invoices</p>
        ) : (
          <div className="space-y-4">
            {invoices.map((invoice) => {
              const isOverdue = Boolean(invoice.due_date && isPast(new Date(invoice.due_date)) && invoice.status === "sent");
              return (
                <Link
                  key={invoice.id}
                  href={`/invoices/${invoice.id}`}
                  className="block rounded-xl border border-border/70 bg-background/80 p-3.5 transition-all hover:-translate-y-0.5 hover:border-primary/30 hover:bg-primary/5"
                >
                  <div className="mb-2 flex items-start justify-between gap-2">
                    <div>
                      <h4 className="text-sm font-medium">{invoice.invoice_number}</h4>
                      <p className="text-xs text-muted-foreground">{invoice.client?.company_name || "No client"}</p>
                    </div>
                    <Badge variant={getStatusVariant(isOverdue ? "overdue" : invoice.status)}>
                      {isOverdue ? <AlertCircle className="mr-1 h-3 w-3" /> : null}
                      {isOverdue ? "Overdue" : invoice.status}
                    </Badge>
                  </div>
                  <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <span>${Number(invoice.amount || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                    <span>
                      {invoice.due_date ? `Due ${format(new Date(invoice.due_date), "MMM d, yyyy")}` : format(new Date(invoice.created_at), "MMM d, yyyy")}
                    </span>
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
