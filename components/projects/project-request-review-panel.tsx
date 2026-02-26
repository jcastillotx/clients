"use client";

import { useState } from "react";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";

interface ReviewState {
  status?: string;
  estimateAmount?: number | null;
  estimateCurrency?: string | null;
  estimatedHours?: number | null;
  estimatedStartDate?: string | null;
  estimatedEndDate?: string | null;
  responseSummary?: string | null;
  reviewNotes?: string | null;
}

interface ProjectRequestReviewPanelProps {
  requestId: string;
  currentStatus: string;
  review: ReviewState | null;
  canReview: boolean;
}

const toDateInput = (value?: string | null) => {
  if (!value) {
    return "";
  }
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return "";
  }
  return parsed.toISOString().slice(0, 10);
};

export function ProjectRequestReviewPanel({ requestId, currentStatus, review, canReview }: ProjectRequestReviewPanelProps) {
  const [status, setStatus] = useState(currentStatus || "pending");
  const [reviewStatus, setReviewStatus] = useState(review?.status || "awaiting_review");
  const [estimateAmount, setEstimateAmount] = useState(review?.estimateAmount ? String(review.estimateAmount) : "");
  const [estimateCurrency, setEstimateCurrency] = useState(review?.estimateCurrency || "USD");
  const [estimatedHours, setEstimatedHours] = useState(review?.estimatedHours ? String(review.estimatedHours) : "");
  const [estimatedStartDate, setEstimatedStartDate] = useState(toDateInput(review?.estimatedStartDate || null));
  const [estimatedEndDate, setEstimatedEndDate] = useState(toDateInput(review?.estimatedEndDate || null));
  const [responseSummary, setResponseSummary] = useState(review?.responseSummary || "");
  const [reviewNotes, setReviewNotes] = useState(review?.reviewNotes || "");

  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const saveReview = async () => {
    try {
      setIsSaving(true);
      setError(null);
      setSuccess(null);

      const response = await fetch(`/api/projects/requests/${requestId}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          status,
          review: {
            status: reviewStatus,
            estimateAmount: estimateAmount ? Number(estimateAmount) : null,
            estimateCurrency,
            estimatedHours: estimatedHours ? Number(estimatedHours) : null,
            estimatedStartDate: estimatedStartDate || null,
            estimatedEndDate: estimatedEndDate || null,
            responseSummary: responseSummary || null,
            reviewNotes: reviewNotes || null,
          },
        }),
      });

      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.error || "Failed to save review");
      }
      setSuccess("Review and estimate saved");
    } catch (saveError) {
      setError(saveError instanceof Error ? saveError.message : "Failed to save review");
    } finally {
      setIsSaving(false);
    }
  };

  const submitDecision = async (decision: "approved" | "needs_changes" | "declined") => {
    try {
      setIsSaving(true);
      setError(null);
      setSuccess(null);

      const response = await fetch(`/api/projects/requests/${requestId}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          clientDecision: decision,
        }),
      });
      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.error || "Failed to submit decision");
      }
      setSuccess("Decision submitted");
    } catch (decisionError) {
      setError(decisionError instanceof Error ? decisionError.message : "Failed to submit decision");
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Review & Estimate</CardTitle>
        <CardDescription>
          {canReview
            ? "Internal review workspace to respond with estimates, timeline, and approval status."
            : "Review the estimate and submit your project decision online."}
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {error ? <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</div> : null}
        {success ? <div className="rounded-md bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700">{success}</div> : null}

        {canReview ? (
          <>
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="request-status">Request Status</Label>
                <Select value={status} onValueChange={setStatus}>
                  <SelectTrigger id="request-status">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="in_progress">In Progress</SelectItem>
                    <SelectItem value="awaiting_approval">Awaiting Approval</SelectItem>
                    <SelectItem value="approved">Approved</SelectItem>
                    <SelectItem value="on_hold">On Hold</SelectItem>
                    <SelectItem value="rejected">Rejected</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                    <SelectItem value="cancelled">Cancelled</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="review-status">Review Stage</Label>
                <Select value={reviewStatus} onValueChange={setReviewStatus}>
                  <SelectTrigger id="review-status">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="awaiting_review">Awaiting Review</SelectItem>
                    <SelectItem value="in_review">In Review</SelectItem>
                    <SelectItem value="estimated">Estimated</SelectItem>
                    <SelectItem value="approved">Approved</SelectItem>
                    <SelectItem value="needs_changes">Needs Changes</SelectItem>
                    <SelectItem value="declined">Declined</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="grid gap-4 md:grid-cols-3">
              <div className="space-y-2">
                <Label htmlFor="estimate-amount">Estimate Amount</Label>
                <Input
                  id="estimate-amount"
                  type="number"
                  step="0.01"
                  value={estimateAmount}
                  onChange={(event) => setEstimateAmount(event.target.value)}
                  placeholder="0.00"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="estimate-currency">Currency</Label>
                <Select value={estimateCurrency} onValueChange={setEstimateCurrency}>
                  <SelectTrigger id="estimate-currency">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="USD">USD</SelectItem>
                    <SelectItem value="EUR">EUR</SelectItem>
                    <SelectItem value="GBP">GBP</SelectItem>
                    <SelectItem value="CAD">CAD</SelectItem>
                    <SelectItem value="AUD">AUD</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="estimate-hours">Estimated Hours</Label>
                <Input
                  id="estimate-hours"
                  type="number"
                  step="0.5"
                  value={estimatedHours}
                  onChange={(event) => setEstimatedHours(event.target.value)}
                  placeholder="0"
                />
              </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="estimate-start">Estimated Start Date</Label>
                <Input
                  id="estimate-start"
                  type="date"
                  value={estimatedStartDate}
                  onChange={(event) => setEstimatedStartDate(event.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="estimate-end">Estimated End Date</Label>
                <Input
                  id="estimate-end"
                  type="date"
                  value={estimatedEndDate}
                  onChange={(event) => setEstimatedEndDate(event.target.value)}
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="response-summary">Estimate Response (client-facing)</Label>
              <Textarea
                id="response-summary"
                value={responseSummary}
                onChange={(event) => setResponseSummary(event.target.value)}
                rows={4}
                placeholder="Summarize the estimate, approach, assumptions, and timeline."
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="review-notes">Internal Review Notes</Label>
              <Textarea
                id="review-notes"
                value={reviewNotes}
                onChange={(event) => setReviewNotes(event.target.value)}
                rows={4}
                placeholder="Internal notes for reviewers and delivery team."
              />
            </div>

            <div className="flex justify-end">
              <Button onClick={saveReview} disabled={isSaving}>
                {isSaving ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                Save Review & Estimate
              </Button>
            </div>
          </>
        ) : (
          <div className="space-y-3">
            <p className="text-sm text-muted-foreground">
              Once the estimate is shared, you can approve it, request changes, or decline directly here.
            </p>
            <div className="flex flex-wrap gap-2">
              <Button onClick={() => void submitDecision("approved")} disabled={isSaving}>
                Approve Estimate
              </Button>
              <Button variant="secondary" onClick={() => void submitDecision("needs_changes")} disabled={isSaving}>
                Request Changes
              </Button>
              <Button variant="destructive" onClick={() => void submitDecision("declined")} disabled={isSaving}>
                Decline
              </Button>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
