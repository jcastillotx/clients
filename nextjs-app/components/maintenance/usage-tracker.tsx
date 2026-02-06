"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { Clock, TrendingUp, AlertTriangle, CheckCircle2, BarChart3 } from "lucide-react";
import { cn } from "@/lib/utils";

interface UsageTrackerProps {
  includedHours: number;
  usedHours: number;
  rolloverHoursAvailable: number;
  hourlyRateOverage: number;
  overageNotificationThreshold: number;
}

export function UsageTracker({
  includedHours,
  usedHours,
  rolloverHoursAvailable,
  hourlyRateOverage,
  overageNotificationThreshold,
}: UsageTrackerProps) {
  const totalAvailable = includedHours + rolloverHoursAvailable;
  const hoursRemaining = totalAvailable - usedHours;
  const utilizationPercent = (usedHours / totalAvailable) * 100;
  const isOverage = usedHours > totalAvailable;
  const isNearThreshold = utilizationPercent >= overageNotificationThreshold;
  const overageHours = isOverage ? usedHours - totalAvailable : 0;
  const overageAmount = overageHours * hourlyRateOverage;

  const getStatusColor = () => {
    if (isOverage) return "text-red-600";
    if (isNearThreshold) return "text-yellow-600";
    return "text-green-600";
  };

  const getStatusIcon = () => {
    if (isOverage) return <AlertTriangle className="h-5 w-5 text-red-600" />;
    if (isNearThreshold) return <TrendingUp className="h-5 w-5 text-yellow-600" />;
    return <CheckCircle2 className="h-5 w-5 text-green-600" />;
  };

  const getStatusText = () => {
    if (isOverage) return "Overage";
    if (isNearThreshold) return "Near Limit";
    return "On Track";
  };

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="flex items-center gap-2">
            <BarChart3 className="h-5 w-5" />
            Usage Tracker
          </CardTitle>
          <Badge
            variant={isOverage ? "destructive" : isNearThreshold ? "warning" : "default"}
            className="flex items-center gap-1.5"
          >
            {getStatusIcon()}
            {getStatusText()}
          </Badge>
        </div>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Main Progress */}
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Clock className="h-4 w-4 text-muted-foreground" />
              <span className="font-medium">Hours Used</span>
            </div>
            <span className={cn("font-bold text-lg", getStatusColor())}>
              {isOverage ? `${overageHours.toFixed(1)}h over` : `${hoursRemaining.toFixed(1)}h left`}
            </span>
          </div>

          <Progress
            value={Math.min(utilizationPercent, 100)}
            className={cn(
              "h-3",
              isOverage ? "[&>div]:bg-red-500" : isNearThreshold ? "[&>div]:bg-yellow-500" : "[&>div]:bg-green-500",
            )}
          />

          <div className="flex items-center justify-between text-sm text-muted-foreground">
            <span>
              {usedHours.toFixed(1)} / {totalAvailable.toFixed(1)} hours
            </span>
            <span className="font-semibold">{utilizationPercent.toFixed(1)}%</span>
          </div>
        </div>

        {/* Hours Breakdown */}
        <div className="grid grid-cols-2 gap-4 pt-4 border-t">
          <div className="space-y-1">
            <div className="text-sm text-muted-foreground">Included Hours</div>
            <div className="text-2xl font-bold">{includedHours.toFixed(1)}</div>
          </div>
          <div className="space-y-1">
            <div className="text-sm text-muted-foreground">Used Hours</div>
            <div className="text-2xl font-bold">{usedHours.toFixed(1)}</div>
          </div>
        </div>

        {/* Rollover Hours */}
        {rolloverHoursAvailable > 0 && (
          <div className="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
            <div className="flex items-center justify-between">
              <div className="text-sm font-medium text-blue-900 dark:text-blue-100">Rollover Hours</div>
              <div className="text-lg font-bold text-blue-900 dark:text-blue-100">
                {rolloverHoursAvailable.toFixed(1)}h
              </div>
            </div>
            <div className="text-xs text-blue-700 dark:text-blue-300 mt-1">Bonus hours from previous period</div>
          </div>
        )}

        {/* Overage Warning/Info */}
        {isOverage ? (
          <div className="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg p-3 space-y-2">
            <div className="flex items-center gap-2 text-red-900 dark:text-red-100 font-medium">
              <AlertTriangle className="h-4 w-4" />
              Overage Charges Apply
            </div>
            <div className="space-y-1">
              <div className="flex justify-between text-sm">
                <span className="text-red-700 dark:text-red-300">Overage Hours:</span>
                <span className="font-semibold text-red-900 dark:text-red-100">{overageHours.toFixed(2)}h</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-red-700 dark:text-red-300">Rate per Hour:</span>
                <span className="font-semibold text-red-900 dark:text-red-100">${hourlyRateOverage.toFixed(2)}</span>
              </div>
              <div className="flex justify-between text-sm pt-1 border-t border-red-200 dark:border-red-800">
                <span className="text-red-700 dark:text-red-300 font-medium">Estimated Overage:</span>
                <span className="font-bold text-red-900 dark:text-red-100">${overageAmount.toFixed(2)}</span>
              </div>
            </div>
          </div>
        ) : isNearThreshold ? (
          <div className="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
            <div className="flex items-center gap-2 text-yellow-900 dark:text-yellow-100 font-medium text-sm">
              <TrendingUp className="h-4 w-4" />
              Approaching usage limit
            </div>
            <div className="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
              You've used {utilizationPercent.toFixed(0)}% of your allocated hours. Overage charges of $
              {hourlyRateOverage.toFixed(2)}/hour will apply beyond {totalAvailable.toFixed(1)} hours.
            </div>
          </div>
        ) : (
          <div className="bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-lg p-3">
            <div className="flex items-center gap-2 text-green-900 dark:text-green-100 font-medium text-sm">
              <CheckCircle2 className="h-4 w-4" />
              Healthy usage
            </div>
            <div className="text-xs text-green-700 dark:text-green-300 mt-1">
              You have {hoursRemaining.toFixed(1)} hours remaining in your current billing period.
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
