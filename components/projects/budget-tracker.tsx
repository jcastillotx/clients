"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ProjectBudget, ProjectCostEntry } from "@/lib/db/schema/projects";
import { Progress } from "@/components/ui/progress";
import { formatCurrency } from "@/lib/utils";
import { AlertCircle, TrendingUp, TrendingDown, DollarSign } from "lucide-react";
import { format } from "date-fns";

interface BudgetTrackerProps {
  budgets: (ProjectBudget & { totalSpent?: number; entriesCount?: number })[];
  costEntries: ProjectCostEntry[];
  currency?: string;
}

const categoryLabels = {
  development: "Development",
  design: "Design",
  marketing: "Marketing",
  infrastructure: "Infrastructure",
  other: "Other",
};

export function BudgetTracker({ budgets, costEntries, currency = "USD" }: BudgetTrackerProps) {
  // Calculate totals
  const totalAllocated = budgets.reduce((sum, b) => sum + parseFloat(b.allocatedAmount), 0);
  const totalSpent = budgets.reduce((sum, b) => sum + parseFloat(b.spentAmount), 0);
  const remaining = totalAllocated - totalSpent;
  const percentageUsed = totalAllocated > 0 ? (totalSpent / totalAllocated) * 100 : 0;
  const isOverBudget = percentageUsed > 100;

  return (
    <div className="space-y-6">
      {/* Summary Card */}
      <Card>
        <CardHeader>
          <CardTitle>Budget Overview</CardTitle>
          <CardDescription>Total budget allocation and spending</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-6">
            {/* Main stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="space-y-2">
                <div className="text-sm text-muted-foreground">Total Allocated</div>
                <div className="text-2xl font-bold">{formatCurrency(totalAllocated, currency)}</div>
              </div>
              <div className="space-y-2">
                <div className="text-sm text-muted-foreground">Total Spent</div>
                <div className={`text-2xl font-bold ${isOverBudget ? "text-red-600" : ""}`}>
                  {formatCurrency(totalSpent, currency)}
                </div>
              </div>
              <div className="space-y-2">
                <div className="text-sm text-muted-foreground">Remaining</div>
                <div className={`text-2xl font-bold ${remaining < 0 ? "text-red-600" : "text-green-600"}`}>
                  {formatCurrency(remaining, currency)}
                </div>
              </div>
            </div>

            {/* Progress bar */}
            <div>
              <div className="flex justify-between text-sm mb-2">
                <span className="text-muted-foreground">Budget Usage</span>
                <span className={`font-medium ${isOverBudget ? "text-red-600" : ""}`}>
                  {percentageUsed.toFixed(1)}%
                </span>
              </div>
              <Progress
                value={Math.min(percentageUsed, 100)}
                className={`h-3 ${isOverBudget ? "[&>div]:bg-red-500" : ""}`}
              />
              {isOverBudget && (
                <div className="flex items-center gap-2 mt-2 text-sm text-red-600">
                  <AlertCircle className="h-4 w-4" />
                  <span>Over budget by {formatCurrency(Math.abs(remaining), currency)}</span>
                </div>
              )}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Budget by Category */}
      <Card>
        <CardHeader>
          <CardTitle>Budget by Category</CardTitle>
          <CardDescription>Breakdown of spending by category</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {budgets.map((budget) => {
              const allocated = parseFloat(budget.allocatedAmount);
              const spent = parseFloat(budget.spentAmount);
              const percentage = allocated > 0 ? (spent / allocated) * 100 : 0;
              const isOver = percentage > 100;

              return (
                <div key={budget.id} className="space-y-2">
                  <div className="flex justify-between items-center">
                    <div>
                      <div className="font-medium">{categoryLabels[budget.category]}</div>
                      {budget.notes && <div className="text-xs text-muted-foreground">{budget.notes}</div>}
                    </div>
                    <div className="text-right">
                      <div className={`font-semibold ${isOver ? "text-red-600" : ""}`}>
                        {formatCurrency(spent, budget.currency)} / {formatCurrency(allocated, budget.currency)}
                      </div>
                      <div className="text-xs text-muted-foreground">{percentage.toFixed(1)}% used</div>
                    </div>
                  </div>
                  <Progress value={Math.min(percentage, 100)} className={`h-2 ${isOver ? "[&>div]:bg-red-500" : ""}`} />
                </div>
              );
            })}

            {budgets.length === 0 && (
              <div className="text-center py-4 text-muted-foreground">No budget categories defined yet</div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Recent Cost Entries */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Expenses</CardTitle>
          <CardDescription>Latest cost entries</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {costEntries.slice(0, 10).map((entry) => (
              <div key={entry.id} className="flex items-center justify-between p-3 rounded-lg border">
                <div className="flex items-center gap-3">
                  <div className="p-2 rounded-full bg-muted">
                    <DollarSign className="h-4 w-4" />
                  </div>
                  <div>
                    <div className="font-medium">{entry.description}</div>
                    <div className="text-xs text-muted-foreground">
                      {format(new Date(entry.entryDate), "MMM dd, yyyy")}
                      {entry.approvedBy && <span className="ml-2 text-green-600">• Approved</span>}
                    </div>
                  </div>
                </div>
                <div className="text-right">
                  <div className="font-semibold">{formatCurrency(parseFloat(entry.amount))}</div>
                  {entry.metadata?.category && (
                    <div className="text-xs text-muted-foreground">{entry.metadata.category}</div>
                  )}
                </div>
              </div>
            ))}

            {costEntries.length === 0 && (
              <div className="text-center py-4 text-muted-foreground">No expenses recorded yet</div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
