"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { PlanCard } from "@/components/maintenance/plan-card";
import { Plus, Search, Filter } from "lucide-react";

interface MaintenancePlan {
  plan: any;
  client: {
    id: string;
    companyName: string;
    email: string;
  };
  creator: {
    id: string;
    name: string;
    email: string;
  };
  hoursRemaining: number;
  utilizationPercent: number;
}

export default function MaintenancePlansPage() {
  const router = useRouter();
  const [plans, setPlans] = useState<MaintenancePlan[]>([]);
  const [loading, setLoading] = useState(true);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [planTypeFilter, setPlanTypeFilter] = useState<string>("all");

  useEffect(() => {
    fetchPlans();
  }, [statusFilter, planTypeFilter]);

  const fetchPlans = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();

      if (statusFilter !== "all") {
        params.append("status", statusFilter);
      }
      if (planTypeFilter !== "all") {
        params.append("planType", planTypeFilter);
      }

      const response = await fetch(`/api/maintenance-plans?${params}`);
      const data = await response.json();

      if (response.ok && data.success) {
        setPlans(data.data ?? []);
        setErrorMessage(null);
      } else {
        setPlans([]);
        setErrorMessage(data?.message || data?.error || "Unable to load maintenance plans.");
      }
    } catch (error) {
      console.error("Error fetching maintenance plans:", error);
      setPlans([]);
      setErrorMessage("Unable to load maintenance plans. Check your database configuration.");
    } finally {
      setLoading(false);
    }
  };

  const filteredPlans = plans.filter((plan) => {
    if (!searchQuery) return true;
    const query = searchQuery.toLowerCase();
    return (
      plan.plan.name.toLowerCase().includes(query) ||
      plan.client.companyName.toLowerCase().includes(query) ||
      plan.plan.planType.toLowerCase().includes(query)
    );
  });

  // Calculate summary statistics
  const stats = {
    total: plans.length,
    active: plans.filter((p) => p.plan.status === "active").length,
    totalRevenue: plans.reduce((sum, p) => sum + parseFloat(p.plan.monthlyRate), 0),
    avgUtilization: plans.length > 0 ? plans.reduce((sum, p) => sum + p.utilizationPercent, 0) / plans.length : 0,
  };

  return (
    <div className="container mx-auto py-8 space-y-8">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Maintenance Plans</h1>
          <p className="text-muted-foreground mt-1">Manage recurring maintenance plans and track usage</p>
        </div>
        <Button onClick={() => router.push("/maintenance-plans/new")}>
          <Plus className="mr-2 h-4 w-4" />
          New Plan
        </Button>
      </div>

      {/* Summary Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-card border rounded-lg p-4">
          <div className="text-sm text-muted-foreground">Total Plans</div>
          <div className="text-2xl font-bold mt-1">{stats.total}</div>
        </div>
        <div className="bg-card border rounded-lg p-4">
          <div className="text-sm text-muted-foreground">Active Plans</div>
          <div className="text-2xl font-bold mt-1 text-green-600">{stats.active}</div>
        </div>
        <div className="bg-card border rounded-lg p-4">
          <div className="text-sm text-muted-foreground">Monthly Revenue</div>
          <div className="text-2xl font-bold mt-1">${stats.totalRevenue.toLocaleString()}</div>
        </div>
        <div className="bg-card border rounded-lg p-4">
          <div className="text-sm text-muted-foreground">Avg Utilization</div>
          <div className="text-2xl font-bold mt-1">{stats.avgUtilization.toFixed(1)}%</div>
        </div>
      </div>

      {/* Filters */}
      <div className="flex flex-col sm:flex-row gap-4">
        <div className="flex-1 relative">
          <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Search plans by name, client, or type..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-9"
          />
        </div>
        <Select value={statusFilter} onValueChange={setStatusFilter}>
          <SelectTrigger className="w-full sm:w-[180px]">
            <Filter className="mr-2 h-4 w-4" />
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Statuses</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="paused">Paused</SelectItem>
            <SelectItem value="expired">Expired</SelectItem>
            <SelectItem value="cancelled">Cancelled</SelectItem>
          </SelectContent>
        </Select>
        <Select value={planTypeFilter} onValueChange={setPlanTypeFilter}>
          <SelectTrigger className="w-full sm:w-[180px]">
            <SelectValue placeholder="Plan Type" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Types</SelectItem>
            <SelectItem value="standard">Standard</SelectItem>
            <SelectItem value="premium">Premium</SelectItem>
            <SelectItem value="enterprise">Enterprise</SelectItem>
            <SelectItem value="custom">Custom</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {errorMessage && (
        <div className="rounded-md border border-destructive/50 bg-destructive/10 p-4 text-sm text-destructive">
          {errorMessage}
        </div>
      )}

      {/* Plans Grid */}
      {loading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {[...Array(6)].map((_, i) => (
            <div key={i} className="h-64 bg-muted animate-pulse rounded-lg" />
          ))}
        </div>
      ) : filteredPlans.length === 0 ? (
        <div className="text-center py-12">
          <div className="text-muted-foreground">
            {searchQuery || statusFilter !== "all" || planTypeFilter !== "all"
              ? "No maintenance plans found matching your filters"
              : "No maintenance plans yet"}
          </div>
          {!searchQuery && statusFilter === "all" && planTypeFilter === "all" && (
            <Button variant="outline" className="mt-4" onClick={() => router.push("/maintenance-plans/new")}>
              Create your first plan
            </Button>
          )}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredPlans.map((plan) => (
            <PlanCard
              key={plan.plan.id}
              plan={plan.plan}
              client={plan.client}
              hoursRemaining={plan.hoursRemaining}
              utilizationPercent={plan.utilizationPercent}
              onClick={() => router.push(`/maintenance-plans/${plan.plan.id}`)}
            />
          ))}
        </div>
      )}
    </div>
  );
}
