"use client";

import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription } from "@/components/ui/alert";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Plus, Pencil, Trash2, AlertCircle, DollarSign, Clock, ShieldCheck } from "lucide-react";

interface PlanTemplate {
  id: string;
  name: string;
  description: string | null;
  planType: string;
  isActive: boolean;
  billingCycle: string;
  monthlyRate: string;
  currency: string;
  includedHours: string;
  hourlyRateOverage: string;
  autoRenew: boolean;
  rolloverEnabled: boolean;
  maxRolloverHours: string | null;
  overageBillingEnabled: boolean;
  overageApprovalRequired: boolean;
  overageNotificationThreshold: string;
  renewalTermMonths: number;
  servicesIncluded: Array<{ category: string; description: string; included: boolean }> | null;
  createdAt: string;
}

const emptyForm = {
  name: "",
  description: "",
  planType: "standard",
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
  renewalTermMonths: "12",
  isActive: true,
};

export default function AdminMaintenancePlansPage() {
  const [templates, setTemplates] = useState<PlanTemplate[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [dialogError, setDialogError] = useState<string | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [deleteConfirmId, setDeleteConfirmId] = useState<string | null>(null);
  const [formData, setFormData] = useState(emptyForm);

  useEffect(() => {
    fetchTemplates();
  }, []);

  const fetchTemplates = async () => {
    try {
      setLoading(true);
      const response = await fetch("/api/admin/maintenance-plan-templates");
      const data = await response.json();
      if (response.ok && data.success) {
        setTemplates(data.data ?? []);
        setError(null);
      } else {
        setError(data?.error || "Failed to load templates");
      }
    } catch {
      setError("Unable to load maintenance plan templates.");
    } finally {
      setLoading(false);
    }
  };

  const openCreateDialog = () => {
    setEditingId(null);
    setFormData(emptyForm);
    setDialogError(null);
    setDialogOpen(true);
  };

  const openEditDialog = (template: PlanTemplate) => {
    setEditingId(template.id);
    setFormData({
      name: template.name,
      description: template.description || "",
      planType: template.planType,
      billingCycle: template.billingCycle,
      monthlyRate: template.monthlyRate,
      currency: template.currency,
      includedHours: template.includedHours,
      hourlyRateOverage: template.hourlyRateOverage,
      autoRenew: template.autoRenew,
      rolloverEnabled: template.rolloverEnabled,
      maxRolloverHours: template.maxRolloverHours || "",
      overageBillingEnabled: template.overageBillingEnabled,
      overageApprovalRequired: template.overageApprovalRequired,
      overageNotificationThreshold: template.overageNotificationThreshold,
      renewalTermMonths: template.renewalTermMonths.toString(),
      isActive: template.isActive,
    });
    setDialogError(null);
    setDialogOpen(true);
  };

  const handleSubmit = async () => {
    const monthlyRate = Number(formData.monthlyRate);
    const includedHours = Number(formData.includedHours);
    const hourlyRateOverage = Number(formData.hourlyRateOverage);
    const maxRolloverHours = formData.maxRolloverHours === "" ? null : Number(formData.maxRolloverHours);
    const overageNotificationThreshold = Number(formData.overageNotificationThreshold);
    const renewalTermMonths = Number(formData.renewalTermMonths);

    if (!formData.name.trim()) {
      setDialogError("Please enter a plan name");
      return;
    }
    if (!Number.isFinite(monthlyRate) || monthlyRate <= 0) {
      setDialogError("Please enter a valid monthly rate");
      return;
    }
    if (!Number.isFinite(includedHours) || includedHours <= 0) {
      setDialogError("Please enter valid included hours");
      return;
    }
    if (!Number.isFinite(hourlyRateOverage) || hourlyRateOverage <= 0) {
      setDialogError("Please enter a valid overage rate");
      return;
    }
    if (maxRolloverHours !== null && (!Number.isFinite(maxRolloverHours) || maxRolloverHours < 0)) {
      setDialogError("Please enter a valid max rollover hours value");
      return;
    }
    if (!Number.isFinite(overageNotificationThreshold) || overageNotificationThreshold < 0) {
      setDialogError("Please enter a valid overage notification threshold");
      return;
    }
    if (!Number.isInteger(renewalTermMonths) || renewalTermMonths <= 0) {
      setDialogError("Please enter a valid renewal term in months");
      return;
    }

    try {
      setSaving(true);
      setDialogError(null);

      const payload = {
        ...formData,
        monthlyRate,
        includedHours,
        hourlyRateOverage,
        maxRolloverHours,
        overageNotificationThreshold,
        renewalTermMonths,
      };

      const url = editingId
        ? `/api/admin/maintenance-plan-templates/${editingId}`
        : "/api/admin/maintenance-plan-templates";

      const response = await fetch(url, {
        method: editingId ? "PATCH" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.error || "Failed to save template");
      }

      setDialogOpen(false);
      setDialogError(null);
      setFormData(emptyForm);
      fetchTemplates();
    } catch (err) {
      setDialogError(err instanceof Error ? err.message : "An error occurred");
    } finally {
      setSaving(false);
    }
  };

  const handleDialogOpenChange = (open: boolean) => {
    setDialogOpen(open);
    if (!open) {
      setDialogError(null);
      setSaving(false);
    }
  };

  const handleDelete = async (id: string) => {
    try {
      const response = await fetch(`/api/admin/maintenance-plan-templates/${id}`, { method: "DELETE" });
      const data = await response.json();
      if (!data.success) {
        throw new Error(data.error || "Failed to delete template");
      }
      setDeleteConfirmId(null);
      fetchTemplates();
    } catch (err) {
      setError(err instanceof Error ? err.message : "An error occurred");
      setDeleteConfirmId(null);
    }
  };

  const planTypeLabels: Record<string, string> = {
    standard: "Standard",
    premium: "Premium",
    enterprise: "Enterprise",
    custom: "Custom",
  };

  const billingCycleLabels: Record<string, string> = {
    monthly: "Monthly",
    quarterly: "Quarterly",
    semi_annual: "Semi-Annual",
    annual: "Annual",
  };

  return (
    <div className="container mx-auto py-8 space-y-8">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Maintenance Plan Templates</h1>
          <p className="text-muted-foreground mt-1">
            Create and manage maintenance plan templates that clients can subscribe to
          </p>
        </div>
        <Button onClick={openCreateDialog}>
          <Plus className="mr-2 h-4 w-4" />
          New Template
        </Button>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {loading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {[...Array(3)].map((_, i) => (
            <div key={i} className="h-64 bg-muted animate-pulse rounded-lg" />
          ))}
        </div>
      ) : templates.length === 0 ? (
        <div className="text-center py-12">
          <ShieldCheck className="mx-auto h-12 w-12 text-muted-foreground" />
          <h3 className="mt-4 text-lg font-semibold">No templates yet</h3>
          <p className="text-muted-foreground mt-1">Create your first maintenance plan template for clients to choose from.</p>
          <Button className="mt-4" onClick={openCreateDialog}>
            <Plus className="mr-2 h-4 w-4" />
            Create Template
          </Button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {templates.map((template) => (
            <Card key={template.id} className="relative">
              <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <CardTitle className="text-lg truncate">{template.name}</CardTitle>
                    {template.description && (
                      <CardDescription className="mt-1 line-clamp-2">{template.description}</CardDescription>
                    )}
                  </div>
                  <Badge variant={template.isActive ? "default" : "secondary"} className="ml-2 flex-shrink-0">
                    {template.isActive ? "Active" : "Inactive"}
                  </Badge>
                </div>
                <div className="flex gap-2 mt-2">
                  <Badge variant="outline">{planTypeLabels[template.planType]}</Badge>
                  <Badge variant="outline">{billingCycleLabels[template.billingCycle]}</Badge>
                </div>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-1">
                    <div className="flex items-center gap-1.5 text-muted-foreground">
                      <DollarSign className="h-3.5 w-3.5" />
                      <span className="text-xs">Monthly Rate</span>
                    </div>
                    <p className="font-semibold">${parseFloat(template.monthlyRate).toLocaleString()}</p>
                  </div>
                  <div className="space-y-1">
                    <div className="flex items-center gap-1.5 text-muted-foreground">
                      <Clock className="h-3.5 w-3.5" />
                      <span className="text-xs">Included Hours</span>
                    </div>
                    <p className="font-semibold">{parseFloat(template.includedHours)}h</p>
                  </div>
                </div>

                <div className="text-sm text-muted-foreground">
                  Overage: ${parseFloat(template.hourlyRateOverage)}/hr
                  {template.rolloverEnabled && " · Rollover enabled"}
                </div>

                <div className="flex gap-2 pt-2 border-t">
                  <Button variant="outline" size="sm" className="flex-1" onClick={() => openEditDialog(template)}>
                    <Pencil className="mr-1.5 h-3.5 w-3.5" />
                    Edit
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    className="text-destructive hover:text-destructive"
                    onClick={() => setDeleteConfirmId(template.id)}
                  >
                    <Trash2 className="h-3.5 w-3.5" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Create/Edit Dialog */}
      <Dialog open={dialogOpen} onOpenChange={handleDialogOpenChange}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{editingId ? "Edit Template" : "Create Maintenance Plan Template"}</DialogTitle>
            <DialogDescription>
              {editingId
                ? "Update the plan template details."
                : "Create a new maintenance plan template that clients can subscribe to."}
            </DialogDescription>
          </DialogHeader>

          {dialogError && (
            <Alert variant="destructive">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>{dialogError}</AlertDescription>
            </Alert>
          )}

          <div className="space-y-6 py-4">
            {/* Basic Info */}
            <div className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="tmpl-name">Plan Name *</Label>
                <Input
                  id="tmpl-name"
                  placeholder="Standard Maintenance Plan"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="tmpl-description">Description</Label>
                <Textarea
                  id="tmpl-description"
                  placeholder="Describe what's included in this plan..."
                  rows={3}
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Plan Type</Label>
                  <Select value={formData.planType} onValueChange={(v) => setFormData({ ...formData, planType: v })}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="standard">Standard</SelectItem>
                      <SelectItem value="premium">Premium</SelectItem>
                      <SelectItem value="enterprise">Enterprise</SelectItem>
                      <SelectItem value="custom">Custom</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Billing Cycle</Label>
                  <Select value={formData.billingCycle} onValueChange={(v) => setFormData({ ...formData, billingCycle: v })}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="monthly">Monthly</SelectItem>
                      <SelectItem value="quarterly">Quarterly</SelectItem>
                      <SelectItem value="semi_annual">Semi-Annual</SelectItem>
                      <SelectItem value="annual">Annual</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>

            {/* Billing */}
            <div className="space-y-4">
              <h4 className="font-medium text-sm text-muted-foreground uppercase tracking-wide">Billing & Hours</h4>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Monthly Rate *</Label>
                  <Input
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="1500.00"
                    value={formData.monthlyRate}
                    onChange={(e) => setFormData({ ...formData, monthlyRate: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Currency</Label>
                  <Select value={formData.currency} onValueChange={(v) => setFormData({ ...formData, currency: v })}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="USD">USD</SelectItem>
                      <SelectItem value="EUR">EUR</SelectItem>
                      <SelectItem value="GBP">GBP</SelectItem>
                      <SelectItem value="CAD">CAD</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Included Hours *</Label>
                  <Input
                    type="number"
                    step="0.5"
                    min="0"
                    placeholder="20"
                    value={formData.includedHours}
                    onChange={(e) => setFormData({ ...formData, includedHours: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Overage Rate/Hr *</Label>
                  <Input
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="150.00"
                    value={formData.hourlyRateOverage}
                    onChange={(e) => setFormData({ ...formData, hourlyRateOverage: e.target.value })}
                  />
                </div>
              </div>
            </div>

            {/* Settings */}
            <div className="space-y-4">
              <h4 className="font-medium text-sm text-muted-foreground uppercase tracking-wide">Settings</h4>

              <div className="flex items-center justify-between">
                <div>
                  <Label>Active</Label>
                  <p className="text-sm text-muted-foreground">Make this template available to clients</p>
                </div>
                <Switch
                  checked={formData.isActive}
                  onCheckedChange={(checked) => setFormData({ ...formData, isActive: checked })}
                />
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <Label>Auto-Renew</Label>
                  <p className="text-sm text-muted-foreground">Automatically renew at end of term</p>
                </div>
                <Switch
                  checked={formData.autoRenew}
                  onCheckedChange={(checked) => setFormData({ ...formData, autoRenew: checked })}
                />
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <Label>Hour Rollover</Label>
                  <p className="text-sm text-muted-foreground">Allow unused hours to roll over</p>
                </div>
                <Switch
                  checked={formData.rolloverEnabled}
                  onCheckedChange={(checked) => setFormData({ ...formData, rolloverEnabled: checked })}
                />
              </div>

              {formData.rolloverEnabled && (
                <div className="ml-4 space-y-2">
                  <Label>Max Rollover Hours</Label>
                  <Input
                    type="number"
                    step="0.5"
                    min="0"
                    placeholder="10"
                    value={formData.maxRolloverHours}
                    onChange={(e) => setFormData({ ...formData, maxRolloverHours: e.target.value })}
                  />
                </div>
              )}

              <div className="flex items-center justify-between">
                <div>
                  <Label>Overage Billing</Label>
                  <p className="text-sm text-muted-foreground">Bill for hours beyond included amount</p>
                </div>
                <Switch
                  checked={formData.overageBillingEnabled}
                  onCheckedChange={(checked) => setFormData({ ...formData, overageBillingEnabled: checked })}
                />
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => handleDialogOpenChange(false)} disabled={saving}>
              Cancel
            </Button>
            <Button onClick={handleSubmit} disabled={saving}>
              {saving ? "Saving..." : editingId ? "Update Template" : "Create Template"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <Dialog open={!!deleteConfirmId} onOpenChange={() => setDeleteConfirmId(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Template</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete this template? This action cannot be undone. Existing client subscriptions
              will not be affected.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteConfirmId(null)}>
              Cancel
            </Button>
            <Button variant="destructive" onClick={() => deleteConfirmId && handleDelete(deleteConfirmId)}>
              Delete
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
