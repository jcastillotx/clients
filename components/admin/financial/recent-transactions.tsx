"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { useEffect, useState } from "react";
import Link from "next/link";
import { format, parseISO } from "date-fns";
import { CheckCircle2, Clock, XCircle } from "lucide-react";

interface Transaction {
  id: string;
  invoice_number: string;
  client_name: string;
  amount: number;
  status: string;
  date: string;
}

export function RecentTransactions() {
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchTransactions();
  }, []);

  const fetchTransactions = async () => {
    try {
      const response = await fetch("/api/financial/recent-transactions");
      if (response.ok) {
        const data = await response.json();
        setTransactions(data.transactions);
      }
    } catch (error) {
      console.error("Error fetching transactions:", error);
    } finally {
      setIsLoading(false);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case "paid":
        return <CheckCircle2 className="h-4 w-4 text-green-600" />;
      case "sent":
        return <Clock className="h-4 w-4 text-yellow-600" />;
      case "cancelled":
        return <XCircle className="h-4 w-4 text-red-600" />;
      default:
        return <Clock className="h-4 w-4 text-muted-foreground" />;
    }
  };

  const getStatusVariant = (status: string): "default" | "secondary" | "outline" => {
    switch (status) {
      case "paid":
        return "default";
      case "sent":
        return "secondary";
      default:
        return "outline";
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Recent Transactions</CardTitle>
        <CardDescription>Latest invoice activity</CardDescription>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="text-center text-muted-foreground">Loading...</div>
        ) : transactions.length === 0 ? (
          <div className="text-center text-muted-foreground">No recent transactions</div>
        ) : (
          <div className="space-y-3">
            {transactions.map((transaction) => (
              <Link
                key={transaction.id}
                href={`/invoices/${transaction.id}`}
                className="flex items-center gap-4 p-2 rounded-lg hover:bg-muted transition-colors"
              >
                <div className="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10">
                  {getStatusIcon(transaction.status)}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium truncate">{transaction.invoice_number}</p>
                  <p className="text-sm text-muted-foreground truncate">{transaction.client_name}</p>
                </div>
                <div className="text-right">
                  <p className="font-medium">${transaction.amount.toLocaleString()}</p>
                  <p className="text-xs text-muted-foreground">{format(parseISO(transaction.date), "MMM dd")}</p>
                </div>
                <Badge variant={getStatusVariant(transaction.status)} className="capitalize">
                  {transaction.status}
                </Badge>
              </Link>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
