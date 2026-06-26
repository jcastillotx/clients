"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Plus } from "lucide-react";
import { toast } from "sonner";
import { fetchApi } from "@/lib/api/client";

interface TimeEntryFormProps {
  onEntryCreated?: () => void;
  initialProjectId?: string | null;
}

export function TimeEntryForm({ onEntryCreated, initialProjectId }: TimeEntryFormProps) {
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    description: "",
    clientId: "",
    requestId: "",
    projectId: initialProjectId || "",
    startedAt: "",
    endedAt: "",
    isBillable: true,
    hourlyRate: "",
  });

  const calculateDuration = (): number | null => {
    if (!formData.startedAt || !formData.endedAt) return null;
    const start = new Date(formData.startedAt).getTime();
    const end = new Date(formData.endedAt).getTime();
    if (end <= start) return null;
    return Math.round((end - start) / 1000 / 60); // Minutes
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.startedAt || !formData.endedAt) {
      toast.error("Start and end times are required");
      return;
    }

    const duration = calculateDuration();
    if (!duration || duration <= 0) {
      toast.error("End time must be after start time");
      return;
    }

    try {
      setLoading(true);

      await fetchApi(
        "/api/time-tracking",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            description: formData.description,
            clientId: formData.clientId || null,
            requestId: formData.requestId || null,
            projectId: formData.projectId || null,
            startedAt: formData.startedAt,
            endedAt: formData.endedAt,
            durationMinutes: duration,
            isBillable: formData.isBillable,
            hourlyRate: formData.hourlyRate ? parseFloat(formData.hourlyRate) : null,
          }),
        },
        { fallbackMessage: "Failed to create entry" },
      );

      toast.success("Time entry created successfully");

      // Reset form
      setFormData({
        description: "",
        clientId: "",
        requestId: "",
        projectId: initialProjectId || "",
        startedAt: "",
        endedAt: "",
        isBillable: true,
        hourlyRate: "",
      });

      if (onEntryCreated) {
        onEntryCreated();
      }
    } catch (error: any) {
      toast.error(error.message || "Failed to create entry");
    } finally {
      setLoading(false);
    }
  };

  const duration = calculateDuration();

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Plus className="h-5 w-5" />
          Manual Time Entry
        </CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              placeholder="What did you work on?"
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              required
              rows={3}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="clientId">Client (Optional)</Label>
              <Input
                id="clientId"
                placeholder="Client ID"
                value={formData.clientId}
                onChange={(e) => setFormData({ ...formData, clientId: e.target.value })}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="requestId">Request (Optional)</Label>
              <Input
                id="requestId"
                placeholder="Request ID"
                value={formData.requestId}
                onChange={(e) => setFormData({ ...formData, requestId: e.target.value })}
              />
            </div>
          </div>

          {initialProjectId && (
            <div className="space-y-2">
              <Label htmlFor="projectId">Project</Label>
              <Input id="projectId" value={formData.projectId} readOnly />
            </div>
          )}

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="startedAt">Start Time</Label>
              <Input
                id="startedAt"
                type="datetime-local"
                value={formData.startedAt}
                onChange={(e) => setFormData({ ...formData, startedAt: e.target.value })}
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="endedAt">End Time</Label>
              <Input
                id="endedAt"
                type="datetime-local"
                value={formData.endedAt}
                onChange={(e) => setFormData({ ...formData, endedAt: e.target.value })}
                required
              />
            </div>
          </div>

          {duration !== null && duration > 0 && (
            <div className="p-3 bg-muted rounded-md">
              <p className="text-sm font-medium">
                Duration: {Math.floor(duration / 60)}h {duration % 60}m
              </p>
            </div>
          )}

          <div className="grid grid-cols-2 gap-4">
            <div className="flex items-center space-x-2">
              <Switch
                id="billable"
                checked={formData.isBillable}
                onCheckedChange={(checked) => setFormData({ ...formData, isBillable: checked })}
              />
              <Label htmlFor="billable">Billable</Label>
            </div>

            <div className="space-y-2">
              <Label htmlFor="hourlyRate">Hourly Rate ($)</Label>
              <Input
                id="hourlyRate"
                type="number"
                step="0.01"
                placeholder="0.00"
                value={formData.hourlyRate}
                onChange={(e) => setFormData({ ...formData, hourlyRate: e.target.value })}
              />
            </div>
          </div>

          <Button type="submit" disabled={loading} className="w-full">
            {loading ? "Creating..." : "Create Time Entry"}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
