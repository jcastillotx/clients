"use client";

import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Calendar, Clock, DollarSign, TrendingUp, AlertCircle, CheckCircle } from "lucide-react";
import { cn } from "@/lib/utils";

interface PlanCardProps {
  plan: any;
  client: {
    companyName: string;
    email: string;
  };
  hoursRemaining: number;
  utilizationPercent: number;
  onClick?: () => void;
}

export function PlanCard({ plan, client, hoursRemaining, utilizationPercent, onClick }: PlanCardProps) {
  const statusColors = {
    active: "bg-green-500/10 text-green-600 border-green-500/20",
    paused: "bg-yellow-500/10 text-yellow-600 border-yellow-500/20",
    expired: "bg-gray-500/10 text-gray-600 border-gray-500/20",
    cancelled: "bg-red-500/10 text-red-600 border-red-500/20",
  };

  const planTypeLabels = {
    standard: "Standard",
    premium: "Premium",
    enterprise: "Enterprise",
    custom: "Custom",
  };

  const isOverage = hoursRemaining < 0;
  const isWarning = utilizationPercent >= 80 && !isOverage;
  const totalHours = parseFloat(plan.includedHours) + parseFloat(plan.rolloverHoursAvailable);

  return (
    <Card
      className={cn(
        "cursor-pointer transition-all hover:shadow-lg hover:scale-[1.02]",
        isOverage && "border-red-300 dark:border-red-800",
      )}
      onClick={onClick}
    >
      <CardHeader className="pb-3">
        <div className="flex items-start justify-between">
          <div className="flex-1 min-w-0">
            <h3 className="font-semibold text-lg truncate">{plan.name}</h3>
            <p className="text-sm text-muted-foreground truncate">{client.companyName}</p>
          </div>
          <Badge
            variant="outline"
            className={cn("ml-2 flex-shrink-0", statusColors[plan.status as keyof typeof statusColors])}
          >
            {plan.status}
          </Badge>
        </div>
        <Badge variant="secondary" className="w-fit mt-2">
          {planTypeLabels[plan.planType as keyof typeof planTypeLabels]}
        </Badge>
      </CardHeader>

      <CardContent className="space-y-4">
        {/* Hours Progress */}
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
              {isOverage ? `${Math.abs(hoursRemaining).toFixed(1)}h over` : `${hoursRemaining.toFixed(1)}h left`}
            </span>
          </div>
          <Progress
            value={Math.min(utilizationPercent, 100)}
            className={cn(
              "h-2",
              isOverage ? "[&>div]:bg-red-500" : isWarning ? "[&>div]:bg-yellow-500" : "[&>div]:bg-green-500",
            )}
          />
          <div className="flex justify-between text-xs text-muted-foreground">
            <span>
              {parseFloat(plan.usedHours).toFixed(1)} / {totalHours.toFixed(1)} hours used
            </span>
            <span>{utilizationPercent.toFixed(0)}%</span>
          </div>
        </div>

        {/* Status Indicators */}
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

        {/* Billing Info */}
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

        {/* Rollover indicator */}
        {plan.rolloverEnabled && parseFloat(plan.rolloverHoursAvailable) > 0 && (
          <div className="text-xs text-muted-foreground bg-muted px-2 py-1.5 rounded">
            {parseFloat(plan.rolloverHoursAvailable).toFixed(1)}h rollover hours available
          </div>
        )}
      </CardContent>
    </Card>
  );
}
