"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { createRequestSchema, type CreateRequestInput } from "@/lib/validations/request";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Loader2 } from "lucide-react";

interface RequestFormProps {
  clients: Array<{
    id: string;
    company_name: string;
  }>;
  assignableUsers: Array<{
    id: string;
    name: string;
    email?: string | null;
  }>;
  preselectedClientId?: string;
  canAssignUsers?: boolean;
}

const requestTypes = [
  { value: "support", label: "Support" },
  { value: "maintenance", label: "Maintenance" },
  { value: "design", label: "Design" },
  { value: "development", label: "Development" },
  { value: "content", label: "Content" },
  { value: "other", label: "Other" },
] as const;

export function RequestForm({ clients, assignableUsers, preselectedClientId, canAssignUsers = false }: RequestFormProps) {
  const router = useRouter();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [attachments, setAttachments] = useState<FileList | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    watch,
  } = useForm<CreateRequestInput>({
    resolver: zodResolver(createRequestSchema),
    defaultValues: {
      clientId: preselectedClientId || "",
      priority: "medium",
      status: "pending",
      type: "support",
    },
  });

  const clientId = watch("clientId");
  const priority = watch("priority");
  const status = watch("status");
  const type = watch("type");
  const assignedTo = watch("assignedTo");

  const onSubmit = async (data: CreateRequestInput) => {
    setIsSubmitting(true);
    setError(null);

    try {
      const internalNotes = (document.getElementById("internalNotes") as HTMLTextAreaElement | null)?.value?.trim() || "";
      const notifyAdmins = (document.getElementById("notifyAdmins") as HTMLInputElement | null)?.checked ?? true;
      const notifyAssigned = (document.getElementById("notifyAssigned") as HTMLInputElement | null)?.checked ?? true;

      const attachmentMeta =
        attachments && attachments.length > 0
          ? Array.from(attachments).map((file) => ({
              name: file.name,
              size: file.size,
              type: file.type,
            }))
          : [];

      const response = await fetch("/api/requests", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          ...data,
          assignedTo: canAssignUsers ? data.assignedTo || undefined : undefined,
          customFields: {
            ...(data.customFields || {}),
            internalNotes,
            notifyAdmins,
            notifyAssigned,
            attachmentMeta,
          },
        }),
      });

      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.error || "Failed to create request");
      }

      const request = payload;
      router.push(`/requests/${request.id}`);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to create request");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Request Details</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          {error && <div className="rounded-md bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}

          {/* Client Selection */}
          <div className="space-y-2">
            <Label htmlFor="clientId">
              Client <span className="text-destructive">*</span>
            </Label>
            <Select value={clientId} onValueChange={(value) => setValue("clientId", value)}>
              <SelectTrigger id="clientId">
                <SelectValue placeholder="Select a client" />
              </SelectTrigger>
              <SelectContent>
                {clients.map((client) => (
                  <SelectItem key={client.id} value={client.id}>
                    {client.company_name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {errors.clientId && <p className="text-sm text-destructive">{errors.clientId.message}</p>}
          </div>

          {canAssignUsers && (
            <div className="space-y-2">
              <Label htmlFor="assignedTo">Assign immediately (optional)</Label>
              <Select value={assignedTo || "unassigned"} onValueChange={(value) => setValue("assignedTo", value === "unassigned" ? undefined : value)}>
                <SelectTrigger id="assignedTo">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="unassigned">Unassigned</SelectItem>
                  {assignableUsers.map((u) => (
                    <SelectItem key={u.id} value={u.id}>
                      {u.name || u.email || "User"}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          )}

          {/* Title */}
          <div className="space-y-2">
            <Label htmlFor="title">
              Title <span className="text-destructive">*</span>
            </Label>
            <Input id="title" placeholder="Brief description of the request" {...register("title")} />
            {errors.title && <p className="text-sm text-destructive">{errors.title.message}</p>}
          </div>

          {/* Description */}
          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              placeholder="Detailed description of what you need..."
              rows={5}
              {...register("description")}
            />
            {errors.description && <p className="text-sm text-destructive">{errors.description.message}</p>}
          </div>

          {/* Type, Priority & Status */}
          <div className="grid gap-4 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="type">Type</Label>
              <Select value={type} onValueChange={(value) => setValue("type", value as any)}>
                <SelectTrigger id="type">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {requestTypes.map((item) => (
                    <SelectItem key={item.value} value={item.value}>
                      {item.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="priority">Priority</Label>
              <Select value={priority} onValueChange={(value) => setValue("priority", value as any)}>
                <SelectTrigger id="priority">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="low">Low</SelectItem>
                  <SelectItem value="medium">Medium</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="status">Status</Label>
              <Select value={status} onValueChange={(value) => setValue("status", value as any)}>
                <SelectTrigger id="status">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="in_progress">In Progress</SelectItem>
                  <SelectItem value="awaiting_approval">In Review</SelectItem>
                  <SelectItem value="approved">Approved</SelectItem>
                  <SelectItem value="on_hold">On Hold</SelectItem>
                  <SelectItem value="rejected">Rejected</SelectItem>
                  <SelectItem value="completed">Completed</SelectItem>
                  <SelectItem value="cancelled">Cancelled</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          {/* Due Date */}
          <div className="space-y-2">
            <Label htmlFor="dueDate">Due Date</Label>
            <Input id="dueDate" type="datetime-local" {...register("dueDate")} />
            {errors.dueDate && <p className="text-sm text-destructive">{errors.dueDate.message}</p>}
          </div>

          <div className="space-y-2">
            <Label htmlFor="internalNotes">Internal Notes (staff only)</Label>
            <Textarea id="internalNotes" placeholder="Internal implementation details, blockers, or instructions..." rows={3} />
          </div>

          <div className="space-y-2">
            <Label htmlFor="attachments">Attachments</Label>
            <Input
              id="attachments"
              type="file"
              multiple
              onChange={(e) => setAttachments(e.target.files)}
              accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
            />
            <p className="text-xs text-muted-foreground">Accepted: PDF, DOC, DOCX, JPG, PNG. Max size per file depends on your upload settings.</p>
          </div>

          <div className="space-y-2">
            <label className="flex items-center gap-2 text-sm">
              <input id="notifyAdmins" type="checkbox" defaultChecked />
              Notify admins (email)
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input id="notifyAssigned" type="checkbox" defaultChecked />
              Notify assigned staff (email)
            </label>
          </div>

          {/* Actions */}
          <div className="flex gap-4">
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Create Request
            </Button>
            <Button type="button" variant="outline" onClick={() => router.back()}>
              Cancel
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
