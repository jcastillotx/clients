"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Switch } from "@/components/ui/switch";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { ArrowLeft, Save, AlertCircle } from "lucide-react";

type ClientOption = {
  id: string;
  company_name?: string;
  companyName?: string;
};

export default function NewMaintenancePlanPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [clients, setClients] = useState<ClientOption[]>([]);
  const [formData, setFormData] = useState({
    clientId: "",
    name: "",
    description: "",
    planType: "standard",
    startDate: new Date().toISOString().split("T")[0],
    endDate: "",
    billingCycle: "monthly",
    monthlyRate: "",
    currency: "USD",
    includedHours: "",
    hourlyRateOverage: "",
    autoRenew: true,
    rolloverEnabled: false,
    maxRolloverHours: "",
    overageBillingEnabled: true,
    overageApprovalRequired: false,
    overageNotificationThreshold: "90",
    autoRenewNotificationDays: "30",
    renewalTermMonths: "12",
  });

  useEffect(() => {
    fetchClients();
  }, []);

  const fetchClients = async () => {
    try {
      const response = await fetch("/api/clients");
      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.error || "Failed to load clients");
      }

      const list = Array.isArray(payload)
        ? payload
        : Array.isArray(payload?.data)
          ? payload.data
          : payload?.client
            ? [payload.client]
            : [];
      setClients(list);
      setError(null);
    } catch (error) {
      console.error("Error fetching clients:", error);
      setError("Unable to load clients for selection. Please refresh and try again.");
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    // Validation
    if (!formData.clientId) {
      setError("Please select a client");
      return;
    }
    if (!formData.name.trim()) {
      setError("Please enter a plan name");
      return;
    }
    if (!formData.monthlyRate || parseFloat(formData.monthlyRate) <= 0) {
      setError("Please enter a valid monthly rate");
      return;
    }
    if (!formData.includedHours || parseFloat(formData.includedHours) <= 0) {
      setError("Please enter valid included hours");
      return;
    }
    if (!formData.hourlyRateOverage || parseFloat(formData.hourlyRateOverage) <= 0) {
      setError("Please enter a valid overage rate");
      return;
    }

    try {
      setLoading(true);

      const response = await fetch("/api/maintenance-plans", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          ...formData,
          monthlyRate: parseFloat(formData.monthlyRate),
          includedHours: parseFloat(formData.includedHours),
          hourlyRateOverage: parseFloat(formData.hourlyRateOverage),
          maxRolloverHours: formData.maxRolloverHours ? parseFloat(formData.maxRolloverHours) : null,
          overageNotificationThreshold: parseFloat(formData.overageNotificationThreshold),
          autoRenewNotificationDays: parseInt(formData.autoRenewNotificationDays),
          renewalTermMonths: parseInt(formData.renewalTermMonths),
        }),
      });

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.error || "Failed to create maintenance plan");
      }

      router.push(`/maintenance-plans/${data.data.id}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : "An error occurred");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container max-w-4xl mx-auto py-8 space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-3xl font-bold">Create Maintenance Plan</h1>
          <p className="text-muted-foreground mt-1">Set up a new recurring maintenance plan for a client</p>
        </div>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Basic Information */}
        <Card>
          <CardHeader>
            <CardTitle>Basic Information</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="clientId">
                Client <span className="text-red-500">*</span>
              </Label>
              <Select
                value={formData.clientId}
                onValueChange={(value) => setFormData({ ...formData, clientId: value })}
              >
                <SelectTrigger id="clientId">
                  <SelectValue placeholder="Select a client..." />
                </SelectTrigger>
                <SelectContent>
                  {clients.length === 0 && (
                    <SelectItem value="__no_clients_available" disabled>
                      No clients available
                    </SelectItem>
                  )}
                  {clients.map((client) => (
                    <SelectItem key={client.id} value={client.id}>
                      {client.company_name || client.companyName || "Unnamed client"}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="name">
                Plan Name <span className="text-red-500">*</span>
              </Label>
              <Input
                id="name"
                placeholder="Standard Maintenance Package"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                placeholder="Describe what's included in this plan..."
                rows={3}
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="planType">Plan Type</Label>
                <Select
                  value={formData.planType}
                  onValueChange={(value) => setFormData({ ...formData, planType: value })}
                >
                  <SelectTrigger id="planType">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="standard">Standard</SelectItem>
                    <SelectItem value="premium">Premium</SelectItem>
                    <SelectItem value="enterprise">Enterprise</SelectItem>
                    <SelectItem value="custom">Custom</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="billingCycle">Billing Cycle</Label>
                <Select
                  value={formData.billingCycle}
                  onValueChange={(value) => setFormData({ ...formData, billingCycle: value })}
                >
                  <SelectTrigger id="billingCycle">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="monthly">Monthly</SelectItem>
                    <SelectItem value="quarterly">Quarterly</SelectItem>
                    <SelectItem value="semi_annual">Semi-Annual</SelectItem>
                    <SelectItem value="annual">Annual</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="startDate">
                  Start Date <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="startDate"
                  type="date"
                  value={formData.startDate}
                  onChange={(e) => setFormData({ ...formData, startDate: e.target.value })}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="endDate">End Date (Optional)</Label>
                <Input
                  id="endDate"
                  type="date"
                  value={formData.endDate}
                  onChange={(e) => setFormData({ ...formData, endDate: e.target.value })}
                />
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Billing & Hours */}
        <Card>
          <CardHeader>
            <CardTitle>Billing & Hours</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="monthlyRate">
                  Monthly Rate <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="monthlyRate"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="1500.00"
                  value={formData.monthlyRate}
                  onChange={(e) => setFormData({ ...formData, monthlyRate: e.target.value })}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="currency">Currency</Label>
                <Select
                  value={formData.currency}
                  onValueChange={(value) => setFormData({ ...formData, currency: value })}
                >
                  <SelectTrigger id="currency">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="USD">USD - US Dollar</SelectItem>
                    <SelectItem value="EUR">EUR - Euro</SelectItem>
                    <SelectItem value="GBP">GBP - British Pound</SelectItem>
                    <SelectItem value="CAD">CAD - Canadian Dollar</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="includedHours">
                  Included Hours <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="includedHours"
                  type="number"
                  step="0.5"
                  min="0"
                  placeholder="20"
                  value={formData.includedHours}
                  onChange={(e) => setFormData({ ...formData, includedHours: e.target.value })}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="hourlyRateOverage">
                  Overage Rate per Hour <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="hourlyRateOverage"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="150.00"
                  value={formData.hourlyRateOverage}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      hourlyRateOverage: e.target.value,
                    })
                  }
                  required
                />
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Settings */}
        <Card>
          <CardHeader>
            <CardTitle>Plan Settings</CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Auto-Renew */}
            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label>Auto-Renew</Label>
                <div className="text-sm text-muted-foreground">
                  Automatically renew this plan at the end of the term
                </div>
              </div>
              <Switch
                checked={formData.autoRenew}
                onCheckedChange={(checked) => setFormData({ ...formData, autoRenew: checked })}
              />
            </div>

            {/* Rollover Hours */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label>Hour Rollover</Label>
                  <div className="text-sm text-muted-foreground">Allow unused hours to rollover to next period</div>
                </div>
                <Switch
                  checked={formData.rolloverEnabled}
                  onCheckedChange={(checked) => setFormData({ ...formData, rolloverEnabled: checked })}
                />
              </div>

              {formData.rolloverEnabled && (
                <div className="space-y-2 ml-4">
                  <Label htmlFor="maxRolloverHours">Maximum Rollover Hours</Label>
                  <Input
                    id="maxRolloverHours"
                    type="number"
                    step="0.5"
                    min="0"
                    placeholder="10"
                    value={formData.maxRolloverHours}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        maxRolloverHours: e.target.value,
                      })
                    }
                  />
                </div>
              )}
            </div>

            {/* Overage Settings */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label>Overage Billing</Label>
                  <div className="text-sm text-muted-foreground">Bill for hours used beyond included amount</div>
                </div>
                <Switch
                  checked={formData.overageBillingEnabled}
                  onCheckedChange={(checked) => setFormData({ ...formData, overageBillingEnabled: checked })}
                />
              </div>

              {formData.overageBillingEnabled && (
                <div className="space-y-4 ml-4">
                  <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                      <Label>Require Approval for Overage</Label>
                      <div className="text-sm text-muted-foreground">
                        Hours beyond limit need approval before billing
                      </div>
                    </div>
                    <Switch
                      checked={formData.overageApprovalRequired}
                      onCheckedChange={(checked) =>
                        setFormData({
                          ...formData,
                          overageApprovalRequired: checked,
                        })
                      }
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="overageNotificationThreshold">Notification Threshold (%)</Label>
                    <Input
                      id="overageNotificationThreshold"
                      type="number"
                      min="50"
                      max="100"
                      value={formData.overageNotificationThreshold}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          overageNotificationThreshold: e.target.value,
                        })
                      }
                    />
                    <div className="text-xs text-muted-foreground">
                      Send notification when usage reaches this percentage
                    </div>
                  </div>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Form Actions */}
        <div className="flex justify-end gap-4">
          <Button type="button" variant="outline" onClick={() => router.back()} disabled={loading}>
            Cancel
          </Button>
          <Button type="submit" disabled={loading}>
            <Save className="mr-2 h-4 w-4" />
            {loading ? "Creating..." : "Create Plan"}
          </Button>
        </div>
      </form>
    </div>
  );
}
