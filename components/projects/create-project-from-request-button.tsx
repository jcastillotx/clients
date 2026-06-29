"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { FolderPlus, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

interface CreateProjectFromRequestButtonProps {
  requestId: string;
  requestTitle: string;
  requestStatus: string;
  clientId: string;
  estimateAmount?: string | number | null;
  estimateCurrency?: string | null;
  estimatedStartDate?: string | null;
  estimatedEndDate?: string | null;
  executiveSummary?: string | null;
}

interface FormData {
  name: string;
  description: string;
  status: string;
  startDate: string;
  endDate: string;
  budgetAmount: string;
  currency: string;
}

export function CreateProjectFromRequestButton({
  requestId,
  requestTitle,
  requestStatus,
  clientId,
  estimateAmount,
  estimateCurrency,
  estimatedStartDate,
  estimatedEndDate,
  executiveSummary,
}: CreateProjectFromRequestButtonProps) {
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const [formData, setFormData] = useState<FormData>({
    name: requestTitle,
    description: executiveSummary ?? "",
    status: "planning",
    startDate: estimatedStartDate ? estimatedStartDate.slice(0, 10) : "",
    endDate: estimatedEndDate ? estimatedEndDate.slice(0, 10) : "",
    budgetAmount: estimateAmount != null ? String(estimateAmount) : "",
    currency: estimateCurrency ?? "USD",
  });

  if (requestStatus !== "approved") {
    return null;
  }

  function handleFieldChange(field: keyof FormData, value: string) {
    setFormData((prev) => ({ ...prev, [field]: value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();

    if (!formData.name.trim()) {
      toast.error("Project name is required.");
      return;
    }

    setIsSubmitting(true);

    try {
      // Step 1: Create a task board for the project
      const boardResponse = await fetch("/api/tasks/boards", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: `${formData.name} Board` }),
      });

      if (!boardResponse.ok) {
        throw new Error("Failed to create task board");
      }

      const boardResult = await boardResponse.json() as { success: boolean; data: { id: string } };
      const boardId = boardResult.data.id;

      // Step 2: Create the project
      const projectResponse = await fetch("/api/projects", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: formData.name,
          description: formData.description || null,
          status: formData.status,
          startDate: formData.startDate || null,
          endDate: formData.endDate || null,
          budgetAmount: formData.budgetAmount
            ? parseFloat(formData.budgetAmount)
            : null,
          currency: formData.currency,
          clientId,
          metadata: {
            sourceProjectRequestId: requestId,
            taskBoardId: boardId,
            projectConvertedAt: new Date().toISOString(),
          },
        }),
      });

      if (!projectResponse.ok) {
        throw new Error("Failed to create project");
      }

      const projectResult = await projectResponse.json() as { success: boolean; data: { id: string } };
      const project = projectResult.data;

      toast.success("Project created successfully!");
      setOpen(false);
      router.push(`/projects/${project.id}`);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Something went wrong";
      toast.error(message);
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <>
      <Button onClick={() => setOpen(true)}>
        <FolderPlus className="mr-2 h-4 w-4" />
        Create Project
      </Button>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>Create Project from Request</DialogTitle>
          </DialogHeader>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="project-name">Project Name *</Label>
              <Input
                id="project-name"
                value={formData.name}
                onChange={(e) => handleFieldChange("name", e.target.value)}
                placeholder="Project name"
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="project-description">Description</Label>
              <Textarea
                id="project-description"
                value={formData.description}
                onChange={(e) => handleFieldChange("description", e.target.value)}
                placeholder="Project description"
                rows={3}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="project-status">Status</Label>
              <Select
                value={formData.status}
                onValueChange={(value) => handleFieldChange("status", value)}
              >
                <SelectTrigger id="project-status">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="planning">Planning</SelectItem>
                  <SelectItem value="active">Active</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="project-start-date">Start Date</Label>
                <Input
                  id="project-start-date"
                  type="date"
                  value={formData.startDate}
                  onChange={(e) => handleFieldChange("startDate", e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="project-end-date">End Date</Label>
                <Input
                  id="project-end-date"
                  type="date"
                  value={formData.endDate}
                  onChange={(e) => handleFieldChange("endDate", e.target.value)}
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="project-budget">Budget Amount</Label>
                <Input
                  id="project-budget"
                  type="number"
                  min="0"
                  step="0.01"
                  value={formData.budgetAmount}
                  onChange={(e) => handleFieldChange("budgetAmount", e.target.value)}
                  placeholder="0.00"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="project-currency">Currency</Label>
                <Select
                  value={formData.currency}
                  onValueChange={(value) => handleFieldChange("currency", value)}
                >
                  <SelectTrigger id="project-currency">
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
            </div>

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => setOpen(false)}
                disabled={isSubmitting}
              >
                Cancel
              </Button>
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Creating…
                  </>
                ) : (
                  "Create Project"
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
}
