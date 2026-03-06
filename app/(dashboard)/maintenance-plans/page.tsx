"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Alert, AlertDescription } from "@/components/ui/alert";
import {
  Clock,
  DollarSign,
  Calendar,
  CheckCircle,
  TrendingUp,
  AlertCircle,
  ShieldCheck,
  ArrowRight,
} from "lucide-react";
import { cn } from "@/lib/utils";

interface ActivePlan {
  plan: any;
  client: { id: string; companyName: string; email: string };
  creator: { id: string; name: string; email: string };
  hoursRemaining: number;
  utilizationPercent: number;
}

interface PlanTemplate {
  id: string;
  name: string;
  description: string | null;
  planType: string;
  billingCycle: string;
  monthlyRate: string;
  currency: string;
  includedHours: string;
  hourlyRateOverage: string;
  rolloverEnabled: boolean;
  servicesIncluded: Array<{ category: string; description: string; included: boolean }> | null;
}

export default function MaintenancePlansPage() {
  const router = useRouter();
  const [activePlans, setActivePlans] = useState<ActivePlan[]>([]);
  const [availableTemplates, setAvailableTemplates] = useState<PlanTemplate[]>([]);
  const [loading, setLoading] = useState(true);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [subscribeTemplate, setSubscribeTemplate] = useState<PlanTemplate | null>(null);
  const [subscribing, setSubscribing] = useState(false);
  const [clientId, setClientId] = useState<string | null>(null);

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      setLoading(true);

      // Fetch active plans and available templates in parallel
      const [plansRes, templatesRes, clientsRes] = await Promise.all([
        fetch("/api/maintenance-plans?activeOnly=true"),
        fetch("/api/admin/maintenance-plan-templates"),
        fetch("/api/clients"),
      ]);

      const plansData = await plansRes.json();
      const templatesData = await templatesRes.json();
      const clientsData = await clientsRes.json();

      if (plansRes.ok && plansData.success) {
        setActivePlans(plansData.data ?? []);
      }

      if (templatesRes.ok && templatesData.success) {
        // Only show active templates
        const active = (templatesData.data ?? []).filter((t: any) => t.isActive);
        setAvailableTemplates(active);
      }

      // Get client ID for subscription
      const clients = Array.isArray(clientsData)
        ? clientsData
        : Array.isArray(clientsData?.data)
          ? clientsData.data
          : clientsData?.client
            ? [clientsData.client]
            : [];
      if (clients.length > 0) {
        setClientId(clients[0].id);
      }

      setErrorMessage(null);
    } catch {
      setErrorMessage("Unable to load maintenance plans.");
    } finally {
      setLoading(false);
    }
  };

  const handleSubscribe = async () => {
    if (!subscribeTemplate || !clientId) return;

    try {
      setSubscribing(true);
      const response = await fetch("/api/maintenance-plans/subscribe", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          templateId: subscribeTemplate.id,
          clientId,
        }),
      });

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.error || "Failed to subscribe");
      }

      setSubscribeTemplate(null);
      fetchData();
    } catch (err) {
      setErrorMessage(err instanceof Error ? err.message : "Failed to subscribe to plan");
    } finally {
      setSubscribing(false);
    }
  };

  const planTypeLabels: Record<string, string> = {
    standard: "Standard",
    premium: "Premium",
    enterprise: "Enterprise",
    custom: "Custom",
  };

  const billingCycleLabels: Record<string, string> = {
    monthly: "Monthly",
    quarterly: "Quarterly",
    semi_annual: "Semi-Annual",
    annual: "Annual",
  };

  const statusColors: Record<string, string> = {
    active: "bg-green-500/10 text-green-600 border-green-500/20",
    paused: "bg-yellow-500/10 text-yellow-600 border-yellow-500/20",
    expired: "bg-gray-500/10 text-gray-600 border-gray-500/20",
    cancelled: "bg-red-500/10 text-red-600 border-red-500/20",
  };

  if (loading) {
    return (
      <div className="container mx-auto py-8 space-y-8">
        <div>
          <h1 className="text-3xl font-bold">Maintenance Plans</h1>
          <p className="text-muted-foreground mt-1">View your maintenance plan and track usage</p>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {[...Array(3)].map((_, i) => (
            <div key={i} className="h-64 bg-muted animate-pulse rounded-lg" />
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto py-8 space-y-8">
      <div>
        <h1 className="text-3xl font-bold">Maintenance Plans</h1>
        <p className="text-muted-foreground mt-1">View your maintenance plan and track usage</p>
      </div>

      {errorMessage && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{errorMessage}</AlertDescription>
        </Alert>
      )}

      {/* Active Plans Section */}
      {activePlans.length > 0 && (
        <div className="space-y-4">
          <h2 className="text-xl font-semibold">Your Active Plan</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {activePlans.map((item) => {
              const plan = item.plan;
              const isOverage = item.hoursRemaining < 0;
              const isWarning = item.utilizationPercent >= 80 && !isOverage;
              const totalHours = parseFloat(plan.includedHours) + parseFloat(plan.rolloverHoursAvailable);

              return (
                <Card
                  key={plan.id}
                  className={cn(
                    "cursor-pointer transition-all hover:shadow-lg",
                    isOverage && "border-red-300 dark:border-red-800",
                  )}
                  onClick={() => router.push(`/maintenance-plans/${plan.id}`)}
                >
                  <CardHeader className="pb-3">
                    <div className="flex items-start justify-between">
                      <div className="flex-1 min-w-0">
                        <CardTitle className="text-lg truncate">{plan.name}</CardTitle>
                        <CardDescription className="truncate">{item.client.companyName}</CardDescription>
                      </div>
                      <Badge variant="outline" className={cn("ml-2 flex-shrink-0", statusColors[plan.status])}>
                        {plan.status}
                      </Badge>
                    </div>
                    <Badge variant="secondary" className="w-fit mt-2">
                      {planTypeLabels[plan.planType]}
                    </Badge>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="space-y-2">
                      <div className="flex items-center justify-between text-sm">
                        <div className="flex items-center gap-2">
                          <Clock className="h-4 w-4 text-muted-foreground" />
                          <span className="font-medium">Hours Usage</span>
                        </div>
                        <span
                          className={cn(
                            "font-semibold",
                            isOverage ? "text-red-600" : isWarning ? "text-yellow-600" : "text-green-600",
                          )}
                        >
                          {isOverage
                            ? `${Math.abs(item.hoursRemaining).toFixed(1)}h over`
                            : `${item.hoursRemaining.toFixed(1)}h left`}
                        </span>
                      </div>
                      <Progress
                        value={Math.min(item.utilizationPercent, 100)}
                        className={cn(
                          "h-2",
                          isOverage
                            ? "[&>div]:bg-red-500"
                            : isWarning
                              ? "[&>div]:bg-yellow-500"
                              : "[&>div]:bg-green-500",
                        )}
                      />
                      <div className="flex justify-between text-xs text-muted-foreground">
                        <span>
                          {parseFloat(plan.usedHours).toFixed(1)} / {totalHours.toFixed(1)} hours used
                        </span>
                        <span>{item.utilizationPercent.toFixed(0)}%</span>
                      </div>
                    </div>

                    <div className="flex items-center gap-4 text-sm">
                      {isOverage ? (
                        <div className="flex items-center gap-1.5 text-red-600">
                          <AlertCircle className="h-4 w-4" />
                          <span className="font-medium">Overage</span>
                        </div>
                      ) : isWarning ? (
                        <div className="flex items-center gap-1.5 text-yellow-600">
                          <TrendingUp className="h-4 w-4" />
                          <span className="font-medium">High Usage</span>
                        </div>
                      ) : (
                        <div className="flex items-center gap-1.5 text-green-600">
                          <CheckCircle className="h-4 w-4" />
                          <span className="font-medium">On Track</span>
                        </div>
                      )}
                    </div>

                    <div className="grid grid-cols-2 gap-4 pt-2 border-t">
                      <div className="space-y-1">
                        <div className="flex items-center gap-1.5 text-muted-foreground">
                          <DollarSign className="h-3.5 w-3.5" />
                          <span className="text-xs">Monthly Rate</span>
                        </div>
                        <p className="font-semibold">${parseFloat(plan.monthlyRate).toLocaleString()}</p>
                      </div>
                      <div className="space-y-1">
                        <div className="flex items-center gap-1.5 text-muted-foreground">
                          <Calendar className="h-3.5 w-3.5" />
                          <span className="text-xs">Next Billing</span>
                        </div>
                        <p className="font-semibold text-sm">
                          {plan.nextBillingDate
                            ? new Date(plan.nextBillingDate).toLocaleDateString("en-US", {
                                month: "short",
                                day: "numeric",
                              })
                            : "N/A"}
                        </p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>
      )}

      {/* Available Plans Section */}
      {availableTemplates.length > 0 && (
        <div className="space-y-4">
          <h2 className="text-xl font-semibold">Available Plans</h2>
          <p className="text-muted-foreground">Choose a maintenance plan to subscribe to</p>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {availableTemplates.map((template) => (
              <Card key={template.id} className="transition-all hover:shadow-lg">
                <CardHeader className="pb-3">
                  <div className="flex items-start justify-between">
                    <CardTitle className="text-lg">{template.name}</CardTitle>
                    <Badge variant="outline">{planTypeLabels[template.planType]}</Badge>
                  </div>
                  {template.description && (
                    <CardDescription className="mt-1 line-clamp-2">{template.description}</CardDescription>
                  )}
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-1">
                      <div className="flex items-center gap-1.5 text-muted-foreground">
                        <DollarSign className="h-3.5 w-3.5" />
                        <span className="text-xs">{billingCycleLabels[template.billingCycle]}</span>
                      </div>
                      <p className="text-2xl font-bold">${parseFloat(template.monthlyRate).toLocaleString()}</p>
                    </div>
                    <div className="space-y-1">
                      <div className="flex items-center gap-1.5 text-muted-foreground">
                        <Clock className="h-3.5 w-3.5" />
                        <span className="text-xs">Included Hours</span>
                      </div>
                      <p className="text-2xl font-bold">{parseFloat(template.includedHours)}h</p>
                    </div>
                  </div>

                  <div className="text-sm text-muted-foreground space-y-1">
                    <p>Overage rate: ${parseFloat(template.hourlyRateOverage)}/hr</p>
                    {template.rolloverEnabled && <p>Unused hours roll over</p>}
                  </div>

                  {template.servicesIncluded && template.servicesIncluded.length > 0 && (
                    <div className="space-y-2">
                      <p className="text-sm font-medium">Services included:</p>
                      <ul className="text-sm text-muted-foreground space-y-1">
                        {template.servicesIncluded
                          .filter((s) => s.included)
                          .slice(0, 4)
                          .map((service, i) => (
                            <li key={i} className="flex items-center gap-2">
                              <CheckCircle className="h-3.5 w-3.5 text-green-600" />
                              {service.description}
                            </li>
                          ))}
                        {template.servicesIncluded.filter((s) => s.included).length > 4 && (
                          <li className="text-xs">
                            +{template.servicesIncluded.filter((s) => s.included).length - 4} more
                          </li>
                        )}
                      </ul>
                    </div>
                  )}

                  <Button className="w-full" onClick={() => setSubscribeTemplate(template)}>
                    Subscribe
                    <ArrowRight className="ml-2 h-4 w-4" />
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      )}

      {/* Empty state */}
      {activePlans.length === 0 && availableTemplates.length === 0 && (
        <div className="text-center py-12">
          <ShieldCheck className="mx-auto h-12 w-12 text-muted-foreground" />
          <h3 className="mt-4 text-lg font-semibold">No maintenance plans available</h3>
          <p className="text-muted-foreground mt-1">
            There are no maintenance plans available at this time. Please check back later.
          </p>
        </div>
      )}

      {/* Subscribe Confirmation Dialog */}
      <Dialog open={!!subscribeTemplate} onOpenChange={() => setSubscribeTemplate(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Subscribe to {subscribeTemplate?.name}</DialogTitle>
            <DialogDescription>
              You are about to subscribe to this maintenance plan. A recurring subscription will be created.
            </DialogDescription>
          </DialogHeader>

          {subscribeTemplate && (
            <div className="space-y-4 py-4">
              <div className="bg-muted rounded-lg p-4 space-y-3">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Plan</span>
                  <span className="font-medium">{subscribeTemplate.name}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Rate</span>
                  <span className="font-medium">
                    ${parseFloat(subscribeTemplate.monthlyRate).toLocaleString()}/{billingCycleLabels[subscribeTemplate.billingCycle].toLowerCase()}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Included Hours</span>
                  <span className="font-medium">{parseFloat(subscribeTemplate.includedHours)}h</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Overage Rate</span>
                  <span className="font-medium">${parseFloat(subscribeTemplate.hourlyRateOverage)}/hr</span>
                </div>
              </div>
            </div>
          )}

          <DialogFooter>
            <Button variant="outline" onClick={() => setSubscribeTemplate(null)} disabled={subscribing}>
              Cancel
            </Button>
            <Button onClick={handleSubscribe} disabled={subscribing}>
              {subscribing ? "Subscribing..." : "Confirm Subscription"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
