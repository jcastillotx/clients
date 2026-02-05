"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { useEffect, useState } from "react";
import Link from "next/link";
import { Building2 } from "lucide-react";

interface TopClient {
  id: string;
  company_name: string;
  total_revenue: number;
  invoice_count: number;
  paid_count: number;
}

export function TopClients() {
  const [clients, setClients] = useState<TopClient[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchTopClients();
  }, []);

  const fetchTopClients = async () => {
    try {
      const response = await fetch("/api/financial/top-clients");
      if (response.ok) {
        const data = await response.json();
        setClients(data.clients);
      }
    } catch (error) {
      console.error("Error fetching top clients:", error);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Top Clients by Revenue</CardTitle>
        <CardDescription>Highest revenue generating clients</CardDescription>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="text-center text-muted-foreground">Loading...</div>
        ) : clients.length === 0 ? (
          <div className="text-center text-muted-foreground">No client data available</div>
        ) : (
          <div className="space-y-4">
            {clients.map((client, index) => {
              const paymentRate = client.invoice_count > 0 ? (client.paid_count / client.invoice_count) * 100 : 0;

              return (
                <Link
                  key={client.id}
                  href={`/clients/${client.id}`}
                  className="flex items-center gap-4 p-2 rounded-lg hover:bg-muted transition-colors"
                >
                  <div className="flex items-center gap-3 flex-1">
                    <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-sm">
                      #{index + 1}
                    </div>
                    <Avatar className="h-10 w-10">
                      <AvatarFallback>
                        <Building2 className="h-5 w-5" />
                      </AvatarFallback>
                    </Avatar>
                    <div className="flex-1 min-w-0">
                      <p className="font-medium truncate">{client.company_name}</p>
                      <p className="text-sm text-muted-foreground">
                        {client.invoice_count} invoices • {paymentRate.toFixed(0)}% paid
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="font-bold text-lg">${client.total_revenue.toLocaleString()}</p>
                    <p className="text-xs text-muted-foreground">total revenue</p>
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
