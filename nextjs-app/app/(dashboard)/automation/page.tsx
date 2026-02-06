import { Suspense } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Plus, Zap, Activity, Clock } from "lucide-react";
import Link from "next/link";
import { AutomationRulesList } from "@/components/automation/automation-rules-list";
import { AutomationStats } from "@/components/automation/automation-stats";

export const metadata = {
  title: "Automation Rules",
  description: "Manage workflow automation rules and triggers",
};

export default function AutomationPage() {
  return (
    <div className="container mx-auto py-8 space-y-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Automation</h1>
          <p className="text-muted-foreground">Create and manage workflow automation rules</p>
        </div>
        <Link href="/automation/builder">
          <Button>
            <Plus className="mr-2 h-4 w-4" />
            New Rule
          </Button>
        </Link>
      </div>

      <Suspense fallback={<StatsLoadingSkeleton />}>
        <AutomationStats />
      </Suspense>

      <Card>
        <CardHeader>
          <CardTitle>Automation Rules</CardTitle>
          <CardDescription>View and manage all your automation rules</CardDescription>
        </CardHeader>
        <CardContent>
          <Suspense fallback={<RulesLoadingSkeleton />}>
            <AutomationRulesList />
          </Suspense>
        </CardContent>
      </Card>
    </div>
  );
}

function StatsLoadingSkeleton() {
  return (
    <div className="grid gap-4 md:grid-cols-3">
      {[1, 2, 3].map((i) => (
        <Card key={i}>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <div className="h-4 w-24 bg-muted animate-pulse rounded" />
            <div className="h-4 w-4 bg-muted animate-pulse rounded" />
          </CardHeader>
          <CardContent>
            <div className="h-8 w-16 bg-muted animate-pulse rounded mb-2" />
            <div className="h-3 w-32 bg-muted animate-pulse rounded" />
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function RulesLoadingSkeleton() {
  return (
    <div className="space-y-4">
      {[1, 2, 3, 4].map((i) => (
        <div key={i} className="flex items-center space-x-4 p-4 border rounded-lg">
          <div className="h-10 w-10 bg-muted animate-pulse rounded" />
          <div className="flex-1 space-y-2">
            <div className="h-4 w-48 bg-muted animate-pulse rounded" />
            <div className="h-3 w-64 bg-muted animate-pulse rounded" />
          </div>
          <div className="h-6 w-16 bg-muted animate-pulse rounded" />
        </div>
      ))}
    </div>
  );
}
