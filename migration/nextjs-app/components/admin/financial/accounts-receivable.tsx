"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useEffect, useState } from "react";

interface AgingData {
  current: { count: number; amount: number }; // 0-30 days
  days30: { count: number; amount: number }; // 31-60 days
  days60: { count: number; amount: number }; // 61-90 days
  days90: { count: number; amount: number }; // 90+ days
}

export function AccountsReceivable() {
  const [aging, setAging] = useState<AgingData>({
    current: { count: 0, amount: 0 },
    days30: { count: 0, amount: 0 },
    days60: { count: 0, amount: 0 },
    days90: { count: 0, amount: 0 },
  });
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchAgingData();
  }, []);

  const fetchAgingData = async () => {
    try {
      const response = await fetch("/api/financial/accounts-receivable");
      if (response.ok) {
        const data = await response.json();
        setAging(data.aging);
      }
    } catch (error) {
      console.error("Error fetching aging data:", error);
    } finally {
      setIsLoading(false);
    }
  };

  const totalAmount = aging.current.amount + aging.days30.amount + aging.days60.amount + aging.days90.amount;

  return (
    <Card>
      <CardHeader>
        <CardTitle>Accounts Receivable Aging</CardTitle>
        <CardDescription>Outstanding invoices by age - Total: ${totalAmount.toLocaleString()}</CardDescription>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="text-center text-muted-foreground">Loading...</div>
        ) : (
          <div className="space-y-4">
            {/* Current (0-30 days) */}
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="font-medium">Current (0-30 days)</span>
                <span className="text-muted-foreground">
                  {aging.current.count} invoices - ${aging.current.amount.toLocaleString()}
                </span>
              </div>
              <div className="h-2 w-full bg-secondary rounded overflow-hidden">
                <div
                  className="h-full bg-green-500 transition-all"
                  style={{ width: `${totalAmount > 0 ? (aging.current.amount / totalAmount) * 100 : 0}%` }}
                />
              </div>
            </div>

            {/* 31-60 days */}
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="font-medium">31-60 days</span>
                <span className="text-muted-foreground">
                  {aging.days30.count} invoices - ${aging.days30.amount.toLocaleString()}
                </span>
              </div>
              <div className="h-2 w-full bg-secondary rounded overflow-hidden">
                <div
                  className="h-full bg-yellow-500 transition-all"
                  style={{ width: `${totalAmount > 0 ? (aging.days30.amount / totalAmount) * 100 : 0}%` }}
                />
              </div>
            </div>

            {/* 61-90 days */}
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="font-medium">61-90 days</span>
                <span className="text-muted-foreground">
                  {aging.days60.count} invoices - ${aging.days60.amount.toLocaleString()}
                </span>
              </div>
              <div className="h-2 w-full bg-secondary rounded overflow-hidden">
                <div
                  className="h-full bg-orange-500 transition-all"
                  style={{ width: `${totalAmount > 0 ? (aging.days60.amount / totalAmount) * 100 : 0}%` }}
                />
              </div>
            </div>

            {/* 90+ days */}
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="font-medium">90+ days</span>
                <span className="text-muted-foreground">
                  {aging.days90.count} invoices - ${aging.days90.amount.toLocaleString()}
                </span>
              </div>
              <div className="h-2 w-full bg-secondary rounded overflow-hidden">
                <div
                  className="h-full bg-red-500 transition-all"
                  style={{ width: `${totalAmount > 0 ? (aging.days90.amount / totalAmount) * 100 : 0}%` }}
                />
              </div>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
