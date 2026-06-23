"use client";

import { useCallback, useEffect, useState } from "react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { MoreHorizontal, Ban, CheckCircle, Trash2 } from "lucide-react";
import { formatCurrency } from "@/lib/utils";
import { fetchApi } from "@/lib/api/client";

type PartnerRow = {
  id: string;
  company_name: string;
  contact_name: string;
  email: string;
  partner_type: string;
  status: string;
  commission_rate: string | number;
  total_referrals: number;
  total_revenue: string | number;
  code: string;
};

const statusColors = {
  active: "default",
  inactive: "secondary",
  pending: "outline",
  suspended: "destructive",
} as const;

const partnerTypeLabels = {
  agency: "Agency",
  affiliate: "Affiliate",
  reseller: "Reseller",
  strategic: "Strategic",
};

export function PartnersTable() {
  const [partners, setPartners] = useState<PartnerRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadPartners = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchApi<PartnerRow[]>("/api/partners", undefined, {
        fallbackMessage: "Failed to load partners",
      });
      setPartners(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load partners");
      setPartners([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadPartners();
  }, [loadPartners]);

  const updateStatus = async (id: string, status: string) => {
    try {
      await fetchApi(
        `/api/partners/${id}`,
        {
          method: "PATCH",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ status }),
        },
        { fallbackMessage: "Failed to update partner" },
      );
      await loadPartners();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to update partner");
    }
  };

  const deletePartner = async (id: string) => {
    if (!window.confirm("Delete this partner?")) return;

    try {
      await fetchApi(`/api/partners/${id}`, { method: "DELETE" }, { fallbackMessage: "Failed to delete partner" });
      await loadPartners();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to delete partner");
    }
  };

  return (
    <div className="space-y-3">
      {error ? <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</div> : null}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Company</TableHead>
              <TableHead>Contact</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Code</TableHead>
              <TableHead>Commission</TableHead>
              <TableHead className="text-right">Referrals</TableHead>
              <TableHead className="text-right">Revenue</TableHead>
              <TableHead className="w-[50px]"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow>
                <TableCell colSpan={9} className="text-center text-muted-foreground">
                  Loading partners...
                </TableCell>
              </TableRow>
            ) : partners.length === 0 ? (
              <TableRow>
                <TableCell colSpan={9} className="text-center text-muted-foreground">
                  No partners found. Create your first partner to get started.
                </TableCell>
              </TableRow>
            ) : (
              partners.map((partner) => (
                <TableRow key={partner.id}>
                  <TableCell>
                    <div>
                      <div className="font-medium">{partner.company_name}</div>
                      <div className="text-sm text-muted-foreground">{partner.email}</div>
                    </div>
                  </TableCell>
                  <TableCell>{partner.contact_name}</TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {partnerTypeLabels[partner.partner_type as keyof typeof partnerTypeLabels] ?? partner.partner_type}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant={statusColors[partner.status as keyof typeof statusColors] ?? "secondary"}>
                      {partner.status}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <code className="text-xs bg-muted px-2 py-1 rounded">{partner.code}</code>
                  </TableCell>
                  <TableCell>{partner.commission_rate}%</TableCell>
                  <TableCell className="text-right">{partner.total_referrals ?? 0}</TableCell>
                  <TableCell className="text-right">
                    {formatCurrency(Number(partner.total_revenue ?? 0))}
                  </TableCell>
                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon">
                          <MoreHorizontal className="h-4 w-4" />
                          <span className="sr-only">Open menu</span>
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        {partner.status === "active" ? (
                          <DropdownMenuItem onClick={() => void updateStatus(partner.id, "suspended")}>
                            <Ban className="mr-2 h-4 w-4" />
                            Suspend
                          </DropdownMenuItem>
                        ) : (
                          <DropdownMenuItem onClick={() => void updateStatus(partner.id, "active")}>
                            <CheckCircle className="mr-2 h-4 w-4" />
                            Activate
                          </DropdownMenuItem>
                        )}
                        <DropdownMenuItem className="text-destructive" onClick={() => void deletePartner(partner.id)}>
                          <Trash2 className="mr-2 h-4 w-4" />
                          Delete
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </div>
  );
}
