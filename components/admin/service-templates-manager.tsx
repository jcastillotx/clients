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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Plus, Pencil, Trash2, AlertCircle, DollarSign, FileText } from "lucide-react";

interface ServiceTemplate {
  id: string;
  name: string;
  description: string | null;
  category: string | null;
  isActive: boolean;
  currency: string;
  lineItems: Array<{ id: string; description: string; quantity: number; unitPrice: number; amount: number; category?: string }>;
  totalAmount: string;
  terms: string | null;
  metadata: { features?: string[]; deliverables?: string[]; estimatedTimeline?: string; notes?: string } | null;
  createdAt: string;
}

interface LineItemInput {
  description: string;
  quantity: number;
  unitPrice: number;
  category: string;
}

const emptyForm = {
  name: "",
  description: "",
  category: "",
  currency: "USD",
  terms: "",
  isActive: true,
};

export function ServiceTemplatesManager() {
  const [templates, setTemplates] = useState<ServiceTemplate[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [deleteConfirmId, setDeleteConfirmId] = useState<string | null>(null);
  const [formData, setFormData] = useState(emptyForm);
  const [lineItems, setLineItems] = useState<LineItemInput[]>([
    { description: "", quantity: 1, unitPrice: 0, category: "" },
  ]);

  useEffect(() => {
    fetchTemplates();
  }, []);

  const fetchTemplates = async () => {
    try {
      setLoading(true);
      const response = await fetch("/api/admin/service-templates");
      const data = await response.json();
      if (response.ok && data.success) {
        setTemplates(Array.isArray(data.data) ? data.data : []);
        setError(null);
      } else {
        const detail =
          typeof data?.message === "string" && data.message.trim()
            ? `${data.error ?? "Error"}: ${data.message}`
            : (data?.error ?? "Failed to load templates");
        setError(detail);
      }
    } catch {
      setError("Unable to load service templates.");
    } finally {
      setLoading(false);
    }
  };

  const openCreateDialog = () => {
    setEditingId(null);
    setFormData(emptyForm);
    setLineItems([{ description: "", quantity: 1, unitPrice: 0, category: "" }]);
    setDialogOpen(true);
  };

  const openEditDialog = (template: ServiceTemplate) => {
    setEditingId(template.id);
    setFormData({
      name: template.name,
      description: template.description || "",
      category: template.category || "",
      currency: template.currency,
      terms: template.terms || "",
      isActive: template.isActive,
    });
    setLineItems(
      template.lineItems.map((item) => ({
        description: item.description,
        quantity: item.quantity,
        unitPrice: item.unitPrice,
        category: item.category || "",
      })),
    );
    setDialogOpen(true);
  };

  const addLineItem = () => {
    setLineItems([...lineItems, { description: "", quantity: 1, unitPrice: 0, category: "" }]);
  };

  const removeLineItem = (index: number) => {
    if (lineItems.length > 1) {
      setLineItems(lineItems.filter((_, i) => i !== index));
    }
  };

  const updateLineItem = (index: number, field: keyof LineItemInput, value: string | number) => {
    const updated = [...lineItems];
    (updated[index] as Record<keyof LineItemInput, string | number>)[field] = value;
    setLineItems(updated);
  };

  const totalAmount = lineItems.reduce((sum, item) => sum + item.quantity * item.unitPrice, 0);

  const handleSubmit = async () => {
    if (!formData.name.trim()) {
      setError("Service name is required");
      return;
    }
    if (lineItems.some((item) => !item.description.trim())) {
      setError("All line items need a description");
      return;
    }

    try {
      setSaving(true);
      setError(null);

      const url = editingId
        ? `/api/admin/service-templates/${editingId}`
        : "/api/admin/service-templates";

      const response = await fetch(url, {
        method: editingId ? "PATCH" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...formData, lineItems }),
      });

      const data = await response.json();
      if (!data.success) throw new Error(data.error);

      setDialogOpen(false);
      fetchTemplates();
    } catch (err) {
      setError(err instanceof Error ? err.message : "An error occurred");
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id: string) => {
    try {
      const response = await fetch(`/api/admin/service-templates/${id}`, { method: "DELETE" });
      const data = await response.json();
      if (!data.success) throw new Error(data.error);
      setDeleteConfirmId(null);
      fetchTemplates();
    } catch (err) {
      setError(err instanceof Error ? err.message : "An error occurred");
      setDeleteConfirmId(null);
    }
  };

  return (
    <div className="space-y-8">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Service Templates</h1>
          <p className="text-muted-foreground mt-1">
            Create service offerings that clients can choose from to generate proposals
          </p>
        </div>
        <Button onClick={openCreateDialog}>
          <Plus className="mr-2 h-4 w-4" />
          New Service
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
            <div key={i} className="h-48 bg-muted animate-pulse rounded-lg" />
          ))}
        </div>
      ) : templates.length === 0 ? (
        <div className="text-center py-12">
          <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
          <h3 className="mt-4 text-lg font-semibold">No service templates yet</h3>
          <p className="text-muted-foreground mt-1">Create service templates for clients to request.</p>
          <Button className="mt-4" onClick={openCreateDialog}>
            <Plus className="mr-2 h-4 w-4" />
            Create Service
          </Button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {templates.map((template) => (
            <Card key={template.id}>
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
                {template.category && (
                  <Badge variant="outline" className="w-fit mt-2">{template.category}</Badge>
                )}
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-1">
                  <div className="flex items-center gap-1.5 text-muted-foreground">
                    <DollarSign className="h-3.5 w-3.5" />
                    <span className="text-xs">Total Price</span>
                  </div>
                  <p className="text-2xl font-bold">
                    ${parseFloat(template.totalAmount).toLocaleString()} {template.currency}
                  </p>
                </div>

                <div className="text-sm text-muted-foreground">
                  {template.lineItems.length} line item{template.lineItems.length !== 1 ? "s" : ""}
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
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{editingId ? "Edit Service Template" : "Create Service Template"}</DialogTitle>
            <DialogDescription>
              {editingId ? "Update the service details." : "Create a new service offering for clients."}
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-6 py-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Service Name *</Label>
                <Input
                  placeholder="Website Redesign"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label>Category</Label>
                <Input
                  placeholder="e.g., Design, Development, Marketing"
                  value={formData.category}
                  onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label>Description</Label>
              <Textarea
                placeholder="Describe what this service includes..."
                rows={3}
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              />
            </div>

            <div className="space-y-2">
              <Label>Currency</Label>
              <Select value={formData.currency} onValueChange={(v) => setFormData({ ...formData, currency: v })}>
                <SelectTrigger className="w-[180px]"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="USD">USD ($)</SelectItem>
                  <SelectItem value="EUR">EUR</SelectItem>
                  <SelectItem value="GBP">GBP</SelectItem>
                  <SelectItem value="CAD">CAD</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {/* Line Items */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <Label>Line Items</Label>
                <Button type="button" variant="outline" size="sm" onClick={addLineItem}>
                  <Plus className="mr-1.5 h-3.5 w-3.5" />
                  Add Item
                </Button>
              </div>
              <div className="rounded-md border">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Description</TableHead>
                      <TableHead className="w-24">Qty</TableHead>
                      <TableHead className="w-32">Unit Price</TableHead>
                      <TableHead className="w-28">Amount</TableHead>
                      <TableHead className="w-12"></TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {lineItems.map((item, index) => (
                      <TableRow key={index}>
                        <TableCell>
                          <Input
                            placeholder="Service description"
                            value={item.description}
                            onChange={(e) => updateLineItem(index, "description", e.target.value)}
                          />
                        </TableCell>
                        <TableCell>
                          <Input
                            type="number"
                            min="1"
                            step="1"
                            value={item.quantity}
                            onChange={(e) => updateLineItem(index, "quantity", parseInt(e.target.value) || 1)}
                          />
                        </TableCell>
                        <TableCell>
                          <Input
                            type="number"
                            min="0"
                            step="0.01"
                            value={item.unitPrice}
                            onChange={(e) => updateLineItem(index, "unitPrice", parseFloat(e.target.value) || 0)}
                          />
                        </TableCell>
                        <TableCell className="font-medium">
                          ${(item.quantity * item.unitPrice).toFixed(2)}
                        </TableCell>
                        <TableCell>
                          {lineItems.length > 1 && (
                            <Button variant="ghost" size="sm" onClick={() => removeLineItem(index)}>
                              <Trash2 className="h-3.5 w-3.5" />
                            </Button>
                          )}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
              <div className="flex justify-end text-lg font-semibold">
                Total: ${totalAmount.toFixed(2)}
              </div>
            </div>

            <div className="space-y-2">
              <Label>Terms & Conditions</Label>
              <Textarea
                placeholder="Terms and conditions for this service..."
                rows={3}
                value={formData.terms}
                onChange={(e) => setFormData({ ...formData, terms: e.target.value })}
              />
            </div>

            <div className="flex items-center justify-between">
              <div>
                <Label>Active</Label>
                <p className="text-sm text-muted-foreground">Make this service available to clients</p>
              </div>
              <Switch
                checked={formData.isActive}
                onCheckedChange={(checked) => setFormData({ ...formData, isActive: checked })}
              />
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)} disabled={saving}>Cancel</Button>
            <Button onClick={handleSubmit} disabled={saving}>
              {saving ? "Saving..." : editingId ? "Update Service" : "Create Service"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation */}
      <Dialog open={!!deleteConfirmId} onOpenChange={() => setDeleteConfirmId(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Service Template</DialogTitle>
            <DialogDescription>
              Are you sure? This action cannot be undone. Existing proposals created from this template will not be affected.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteConfirmId(null)}>Cancel</Button>
            <Button variant="destructive" onClick={() => deleteConfirmId && handleDelete(deleteConfirmId)}>Delete</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
