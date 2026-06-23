"use client";

import { useState, useEffect } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Webhook, Plus, Trash2, Power } from "lucide-react";
import { toast } from "sonner";
import { fetchApi } from "@/lib/api/client";

interface WebhookEndpoint {
  id: string;
  url: string;
  events: string[];
  isActive: boolean;
  retryConfig: {
    maxAttempts?: number;
    backoffMultiplier?: number;
    initialDelay?: number;
  } | null;
  createdAt: string;
}

interface WebhookManagerProps {
  clientId: string;
}

const AVAILABLE_EVENTS = [
  { value: "client.created", label: "Client Created" },
  { value: "client.updated", label: "Client Updated" },
  { value: "invoice.created", label: "Invoice Created" },
  { value: "invoice.paid", label: "Invoice Paid" },
  { value: "project.completed", label: "Project Completed" },
  { value: "ticket.created", label: "Ticket Created" },
  { value: "ticket.resolved", label: "Ticket Resolved" },
  { value: "user.created", label: "User Created" },
];

export function WebhookManager({ clientId }: WebhookManagerProps) {
  const [endpoints, setEndpoints] = useState<WebhookEndpoint[]>([]);
  const [loading, setLoading] = useState(true);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [formData, setFormData] = useState({
    url: "",
    events: [] as string[],
  });

  useEffect(() => {
    fetchEndpoints();
  }, [clientId]);

  const fetchEndpoints = async () => {
    try {
      setLoading(true);
      const data = await fetchApi<WebhookEndpoint[]>(
        `/api/webhooks?clientId=${clientId}`,
        undefined,
        { fallbackMessage: "Failed to fetch webhooks" },
      );
      setEndpoints(data);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Failed to load webhooks");
    } finally {
      setLoading(false);
    }
  };

  const handleCreate = async () => {
    try {
      if (!formData.url || formData.events.length === 0) {
        toast.error("Please fill in all fields");
        return;
      }

      await fetchApi(
        "/api/webhooks",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            clientId,
            url: formData.url,
            events: formData.events,
          }),
        },
        { fallbackMessage: "Failed to create webhook" },
      );

      toast.success("Webhook endpoint created");
      setDialogOpen(false);
      fetchEndpoints();
      setFormData({ url: "", events: [] });
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Failed to create webhook");
    }
  };

  const handleToggleActive = async (id: string, currentStatus: boolean) => {
    try {
      await fetchApi(
        "/api/webhooks",
        {
          method: "PATCH",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            id,
            isActive: !currentStatus,
          }),
        },
        { fallbackMessage: "Failed to update webhook" },
      );

      toast.success(`Webhook ${!currentStatus ? "enabled" : "disabled"}`);
      fetchEndpoints();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Failed to update webhook");
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm("Are you sure you want to delete this webhook?")) return;

    try {
      await fetchApi(
        `/api/webhooks?id=${id}`,
        { method: "DELETE" },
        { fallbackMessage: "Failed to delete webhook" },
      );

      toast.success("Webhook deleted");
      fetchEndpoints();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Failed to delete webhook");
    }
  };

  const handleEventToggle = (eventValue: string, checked: boolean) => {
    setFormData((prev) => ({
      ...prev,
      events: checked ? [...prev.events, eventValue] : prev.events.filter((e) => e !== eventValue),
    }));
  };

  if (loading) {
    return <div className="text-center p-8">Loading webhooks...</div>;
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold">Webhook Endpoints</h2>
          <p className="text-gray-500">Configure webhooks for event notifications</p>
        </div>
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Add Webhook
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>New Webhook Endpoint</DialogTitle>
              <DialogDescription>Configure a webhook to receive event notifications</DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              <div>
                <Label>Endpoint URL</Label>
                <Input
                  value={formData.url}
                  onChange={(e) => setFormData({ ...formData, url: e.target.value })}
                  placeholder="https://example.com/webhooks"
                />
              </div>
              <div>
                <Label>Events to Subscribe</Label>
                <div className="mt-2 space-y-2 max-h-64 overflow-y-auto border rounded-lg p-4">
                  {AVAILABLE_EVENTS.map((event) => (
                    <div key={event.value} className="flex items-center space-x-2">
                      <Checkbox
                        id={event.value}
                        checked={formData.events.includes(event.value)}
                        onCheckedChange={(checked) => handleEventToggle(event.value, checked as boolean)}
                      />
                      <label htmlFor={event.value} className="text-sm font-medium cursor-pointer">
                        {event.label}
                      </label>
                    </div>
                  ))}
                </div>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setDialogOpen(false)}>
                Cancel
              </Button>
              <Button onClick={handleCreate}>Create Webhook</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <div className="space-y-4">
        {endpoints.map((endpoint) => (
          <Card key={endpoint.id}>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div className="flex items-center gap-3">
                  <Webhook className="h-5 w-5" />
                  <div className="flex-1 min-w-0">
                    <CardTitle className="text-lg truncate">{endpoint.url}</CardTitle>
                    <CardDescription>
                      {endpoint.events.length} event{endpoint.events.length !== 1 && "s"}
                    </CardDescription>
                  </div>
                </div>
                <Badge variant={endpoint.isActive ? "default" : "secondary"}>
                  {endpoint.isActive ? "Active" : "Inactive"}
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div>
                  <span className="text-sm text-gray-500">Subscribed Events:</span>
                  <div className="flex flex-wrap gap-2 mt-2">
                    {endpoint.events.map((event) => (
                      <Badge key={event} variant="outline">
                        {event}
                      </Badge>
                    ))}
                  </div>
                </div>
                {endpoint.retryConfig && (
                  <div className="text-sm">
                    <span className="text-gray-500">Retry Config:</span> Max {endpoint.retryConfig.maxAttempts || 3}{" "}
                    attempts
                  </div>
                )}
                <div className="flex gap-2 pt-2">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => handleToggleActive(endpoint.id, endpoint.isActive)}
                  >
                    <Power className="h-4 w-4 mr-2" />
                    {endpoint.isActive ? "Disable" : "Enable"}
                  </Button>
                  <Button size="sm" variant="destructive" onClick={() => handleDelete(endpoint.id)}>
                    <Trash2 className="h-4 w-4 mr-2" />
                    Delete
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {endpoints.length === 0 && (
        <div className="text-center p-12 border-2 border-dashed rounded-lg">
          <Webhook className="h-12 w-12 mx-auto mb-4 text-gray-400" />
          <h3 className="text-lg font-semibold mb-2">No Webhook Endpoints</h3>
          <p className="text-gray-500 mb-4">Configure webhooks to receive real-time event notifications</p>
          <Button onClick={() => setDialogOpen(true)}>
            <Plus className="h-4 w-4 mr-2" />
            Add First Webhook
          </Button>
        </div>
      )}
    </div>
  );
}
