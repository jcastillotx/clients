"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Users, TrendingUp, DollarSign, PercentIcon } from "lucide-react";
import { fetchApi } from "@/lib/api/client";

type PartnerRow = {
  status: string;
  total_referrals: number;
  total_revenue: string | number;
  commission_rate: string | number;
};

export function PartnersStats() {
  const [stats, setStats] = useState({
    totalPartners: 0,
    activePartners: 0,
    totalReferrals: 0,
    totalRevenue: 0,
    avgCommissionRate: 0,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    void (async () => {
      try {
        const partners = await fetchApi<PartnerRow[]>("/api/partners", undefined, {
          fallbackMessage: "Failed to load partner stats",
        });

        const activePartners = partners.filter((p) => p.status === "active").length;
        const totalReferrals = partners.reduce((sum, p) => sum + (p.total_referrals ?? 0), 0);
        const totalRevenue = partners.reduce((sum, p) => sum + Number(p.total_revenue ?? 0), 0);
        const avgCommissionRate =
          partners.length > 0
            ? partners.reduce((sum, p) => sum + Number(p.commission_rate ?? 0), 0) / partners.length
            : 0;

        setStats({
          totalPartners: partners.length,
          activePartners,
          totalReferrals,
          totalRevenue,
          avgCommissionRate,
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
          <CardTitle className="text-sm font-medium">Total Partners</CardTitle>
          <Users className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.totalPartners}</div>
          <p className="text-xs text-muted-foreground">{stats.activePartners} active partners</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Referrals</CardTitle>
          <TrendingUp className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.totalReferrals}</div>
          <p className="text-xs text-muted-foreground">Across all partners</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
          <DollarSign className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">
            {stats.totalRevenue >= 1000 ? `$${(stats.totalRevenue / 1000).toFixed(1)}K` : `$${stats.totalRevenue.toFixed(0)}`}
          </div>
          <p className="text-xs text-muted-foreground">From partner referrals</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Avg Commission</CardTitle>
          <PercentIcon className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.avgCommissionRate.toFixed(1)}%</div>
          <p className="text-xs text-muted-foreground">Average commission rate</p>
        </CardContent>
      </Card>
    </div>
  );
}
