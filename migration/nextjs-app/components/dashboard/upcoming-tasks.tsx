"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { format, formatDistanceToNow, isPast } from "date-fns";
import Link from "next/link";
import { Receipt, AlertCircle } from "lucide-react";

interface Invoice {
  id: string;
  invoice_number: string;
  amount: number;
  due_date: string;
  status: string;
  client: {
    company_name: string;
  };
}

interface UpcomingTasksProps {
  invoices: Invoice[];
}

export function UpcomingTasks({ invoices }: UpcomingTasksProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Receipt className="h-5 w-5" />
          Upcoming Invoices
        </CardTitle>
      </CardHeader>
      <CardContent>
        {invoices.length === 0 ? (
          <p className="text-sm text-muted-foreground text-center py-8">No upcoming invoices</p>
        ) : (
          <div className="space-y-4">
            {invoices.map((invoice) => {
              const isOverdue = isPast(new Date(invoice.due_date));
              return (
                <Link
                  key={invoice.id}
                  href={`/invoices/${invoice.id}`}
                  className="block p-3 rounded-lg border hover:bg-muted/50 transition-colors"
                >
                  <div className="flex items-start justify-between mb-2">
                    <div className="flex-1">
                      <h4 className="font-medium text-sm">{invoice.invoice_number}</h4>
                      <p className="text-xs text-muted-foreground">{invoice.client.company_name}</p>
                    </div>
                    <div className="text-right ml-2">
                      <p className="font-semibold text-sm">
                        ${invoice.amount.toLocaleString("en-US", { minimumFractionDigits: 2 })}
                      </p>
                      {isOverdue && (
                        <Badge variant="destructive" className="mt-1">
                          <AlertCircle className="h-3 w-3 mr-1" />
                          Overdue
                        </Badge>
                      )}
                    </div>
                  </div>
                  <div className="text-xs text-muted-foreground">
                    Due {format(new Date(invoice.due_date), "MMM d, yyyy")} (
                    {formatDistanceToNow(new Date(invoice.due_date), { addSuffix: true })})
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
