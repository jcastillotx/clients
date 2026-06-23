"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { UserPlus, Clock, CheckCircle, DollarSign } from "lucide-react";
import { fetchApi } from "@/lib/api/client";

type ReferralRow = {
  status: string;
  commission_amount: string | number | null;
};

export function ReferralsStats() {
  const [stats, setStats] = useState({
    totalReferrals: 0,
    pending: 0,
    converted: 0,
    conversionRate: 0,
    totalCommission: 0,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    void (async () => {
      try {
        const referrals = await fetchApi<ReferralRow[]>("/api/referrals", undefined, {
          fallbackMessage: "Failed to load referral stats",
        });

        const pending = referrals.filter((r) => r.status === "pending" || r.status === "contacted").length;
        const converted = referrals.filter((r) => r.status === "converted").length;
        const totalCommission = referrals.reduce((sum, r) => sum + Number(r.commission_amount ?? 0), 0);
        const conversionRate = referrals.length > 0 ? (converted / referrals.length) * 100 : 0;

        setStats({
          totalReferrals: referrals.length,
          pending,
          converted,
          conversionRate,
          totalCommission,
        });
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  if (loading) {
    return <div className="text-sm text-muted-foreground">Loading stats...</div>;
  }

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Referrals</CardTitle>
          <UserPlus className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.totalReferrals}</div>
          <p className="text-xs text-muted-foreground">All time referrals</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Pending</CardTitle>
          <Clock className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.pending}</div>
          <p className="text-xs text-muted-foreground">Awaiting conversion</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Converted</CardTitle>
          <CheckCircle className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.converted}</div>
          <p className="text-xs text-muted-foreground">{stats.conversionRate.toFixed(1)}% conversion rate</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Commission</CardTitle>
          <DollarSign className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">
            {stats.totalCommission >= 1000
              ? `$${(stats.totalCommission / 1000).toFixed(1)}K`
              : `$${stats.totalCommission.toFixed(0)}`}
          </div>
          <p className="text-xs text-muted-foreground">Paid to partners</p>
        </CardContent>
      </Card>
    </div>
  );
}
