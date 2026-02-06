"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Clock, AlertCircle } from "lucide-react";
import { Alert, AlertDescription } from "@/components/ui/alert";

interface LogHoursDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  planId: string;
  currentUserId: string;
  hoursRemaining: number;
  hourlyRateOverage: number;
  onSuccess?: () => void;
}

export function LogHoursDialog({
  open,
  onOpenChange,
  planId,
  currentUserId,
  hoursRemaining,
  hourlyRateOverage,
  onSuccess,
}: LogHoursDialogProps) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState({
    hoursUsed: "",
    description: "",
    taskCategory: "",
    supportTicketId: "",
    workPerformedAt: "",
  });

  const hoursUsedNum = parseFloat(formData.hoursUsed) || 0;
  const willCauseOverage = hoursUsedNum > hoursRemaining && hoursRemaining > 0;
  const overageHours = willCauseOverage ? hoursUsedNum - hoursRemaining : 0;
  const overageAmount = overageHours * hourlyRateOverage;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!formData.hoursUsed || parseFloat(formData.hoursUsed) <= 0) {
      setError("Please enter a valid number of hours");
      return;
    }

    if (!formData.description.trim()) {
      setError("Please provide a description of the work performed");
      return;
    }

    try {
      setLoading(true);

      const response = await fetch(`/api/maintenance-plans/${planId}/usage`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          ...formData,
          hoursUsed: parseFloat(formData.hoursUsed),
          loggedBy: currentUserId,
          workPerformedAt: formData.workPerformedAt || new Date().toISOString(),
        }),
      });

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.error || "Failed to log hours");
      }

      // Reset form
      setFormData({
        hoursUsed: "",
        description: "",
        taskCategory: "",
        supportTicketId: "",
        workPerformedAt: "",
      });

      // Close dialog and refresh
      onOpenChange(false);
      if (onSuccess) {
        onSuccess();
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "An error occurred");
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <form onSubmit={handleSubmit}>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Clock className="h-5 w-5" />
              Log Hours
            </DialogTitle>
            <DialogDescription>Record time spent on maintenance tasks for this plan.</DialogDescription>
          </DialogHeader>

          <div className="space-y-4 py-4">
            {error && (
              <Alert variant="destructive">
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}

            {/* Hours Used */}
            <div className="space-y-2">
              <Label htmlFor="hoursUsed">
                Hours Worked <span className="text-red-500">*</span>
              </Label>
              <Input
                id="hoursUsed"
                type="number"
                step="0.25"
                min="0.25"
                placeholder="2.5"
                value={formData.hoursUsed}
                onChange={(e) => setFormData({ ...formData, hoursUsed: e.target.value })}
                required
              />
              {hoursRemaining > 0 && (
                <div className="text-xs text-muted-foreground">{hoursRemaining.toFixed(1)} hours remaining</div>
              )}
            </div>

            {/* Overage Warning */}
            {willCauseOverage && (
              <Alert className="bg-yellow-50 dark:bg-yellow-950/20 border-yellow-200 dark:border-yellow-800">
                <AlertCircle className="h-4 w-4 text-yellow-600" />
                <AlertDescription className="text-yellow-800 dark:text-yellow-200">
                  <div className="font-medium mb-1">This will cause overage charges</div>
                  <div className="text-sm space-y-0.5">
                    <div>
                      Overage hours: <strong>{overageHours.toFixed(2)}h</strong>
                    </div>
                    <div>
                      Estimated charge: <strong>${overageAmount.toFixed(2)}</strong> (${hourlyRateOverage.toFixed(2)}
                      /hour)
                    </div>
                  </div>
                </AlertDescription>
              </Alert>
            )}

            {/* Description */}
            <div className="space-y-2">
              <Label htmlFor="description">
                Work Description <span className="text-red-500">*</span>
              </Label>
              <Textarea
                id="description"
                placeholder="Describe the work performed..."
                rows={3}
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                required
              />
            </div>

            {/* Task Category */}
            <div className="space-y-2">
              <Label htmlFor="taskCategory">Task Category</Label>
              <Select
                value={formData.taskCategory}
                onValueChange={(value) => setFormData({ ...formData, taskCategory: value })}
              >
                <SelectTrigger id="taskCategory">
                  <SelectValue placeholder="Select category..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="maintenance">Maintenance</SelectItem>
                  <SelectItem value="bug_fix">Bug Fix</SelectItem>
                  <SelectItem value="feature_development">Feature Development</SelectItem>
                  <SelectItem value="support">Support</SelectItem>
                  <SelectItem value="security">Security</SelectItem>
                  <SelectItem value="performance">Performance</SelectItem>
                  <SelectItem value="documentation">Documentation</SelectItem>
                  <SelectItem value="other">Other</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {/* Support Ticket (Optional) */}
            <div className="space-y-2">
              <Label htmlFor="supportTicketId">Related Support Ticket</Label>
              <Input
                id="supportTicketId"
                placeholder="Ticket ID (optional)"
                value={formData.supportTicketId}
                onChange={(e) => setFormData({ ...formData, supportTicketId: e.target.value })}
              />
            </div>

            {/* Work Performed Date */}
            <div className="space-y-2">
              <Label htmlFor="workPerformedAt">Work Performed On</Label>
              <Input
                id="workPerformedAt"
                type="datetime-local"
                value={formData.workPerformedAt}
                onChange={(e) => setFormData({ ...formData, workPerformedAt: e.target.value })}
              />
            </div>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
              Cancel
            </Button>
            <Button type="submit" disabled={loading}>
              {loading ? "Logging..." : "Log Hours"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
