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
import { MoreHorizontal, CheckCircle, XCircle } from "lucide-react";
import { formatDate } from "@/lib/utils";
import { fetchApi } from "@/lib/api/client";

type ReferralRow = {
  id: string;
  referred_name: string;
  referred_email: string | null;
  status: string;
  referred_at: string;
  converted_at: string | null;
  commission_amount: string | number | null;
  partner: { company_name: string } | Array<{ company_name: string }> | null;
};

const statusColors = {
  pending: "outline",
  contacted: "secondary",
  qualified: "default",
  converted: "default",
  rejected: "destructive",
  lost: "destructive",
} as const;

function partnerName(partner: ReferralRow["partner"]): string {
  if (!partner) return "Unknown";
  if (Array.isArray(partner)) return partner[0]?.company_name ?? "Unknown";
  return partner.company_name;
}

export function ReferralsTable() {
  const [referrals, setReferrals] = useState<ReferralRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadReferrals = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchApi<ReferralRow[]>("/api/referrals", undefined, {
        fallbackMessage: "Failed to load referrals",
      });
      setReferrals(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load referrals");
      setReferrals([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadReferrals();
  }, [loadReferrals]);

  const updateStatus = async (id: string, status: string) => {
    try {
      await fetchApi(
        `/api/referrals/${id}`,
        {
          method: "PATCH",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ status }),
        },
        { fallbackMessage: "Failed to update referral" },
      );
      await loadReferrals();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to update referral");
    }
  };

  return (
    <div className="space-y-3">
      {error ? <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</div> : null}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Partner</TableHead>
              <TableHead>Referred Contact</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Referred Date</TableHead>
              <TableHead>Converted Date</TableHead>
              <TableHead className="text-right">Commission</TableHead>
              <TableHead className="w-[50px]"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center text-muted-foreground">
                  Loading referrals...
                </TableCell>
              </TableRow>
            ) : referrals.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center text-muted-foreground">
                  No referrals found.
                </TableCell>
              </TableRow>
            ) : (
              referrals.map((referral) => (
                <TableRow key={referral.id}>
                  <TableCell className="font-medium">{partnerName(referral.partner)}</TableCell>
                  <TableCell>
                    <div>
                      <div>{referral.referred_name}</div>
                      <div className="text-sm text-muted-foreground">{referral.referred_email ?? "—"}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant={statusColors[referral.status as keyof typeof statusColors] ?? "secondary"}>
                      {referral.status}
                    </Badge>
                  </TableCell>
                  <TableCell>{formatDate(referral.referred_at)}</TableCell>
                  <TableCell>{referral.converted_at ? formatDate(referral.converted_at) : "-"}</TableCell>
                  <TableCell className="text-right">
                    {referral.commission_amount != null ? `$${referral.commission_amount}` : "-"}
                  </TableCell>
                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon">
                          <MoreHorizontal className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => void updateStatus(referral.id, "converted")}>
                          <CheckCircle className="mr-2 h-4 w-4" />
                          Mark as Converted
                        </DropdownMenuItem>
                        <DropdownMenuItem className="text-destructive" onClick={() => void updateStatus(referral.id, "lost")}>
                          <XCircle className="mr-2 h-4 w-4" />
                          Mark as Lost
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
