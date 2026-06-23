"use client";

import { useState } from "react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { fetchApi } from "@/lib/api/client";

interface Client {
  id: string;
  company_name: string;
}

interface CreateContractDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  clients: Client[];
  onSuccess: (contract: any) => void;
  defaultClientId?: string;
}

export function CreateContractDialog({
  open,
  onOpenChange,
  clients,
  onSuccess,
  defaultClientId,
}: CreateContractDialogProps) {
  const [isLoading, setIsLoading] = useState(false);
  const [formData, setFormData] = useState({
    title: "",
    description: "",
    clientId: defaultClientId || "",
    type: "service_agreement",
    startDate: "",
    endDate: "",
    value: "",
    currency: "USD",
    billingCycle: "monthly",
    terms: "",
    autoRenew: false,
    noticeRequired: "30",
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.title || !formData.clientId || !formData.startDate) {
      alert("Please fill in all required fields");
      return;
    }

    setIsLoading(true);

    try {
      const contract = await fetchApi(
        "/api/contracts",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            title: formData.title,
            description: formData.description || null,
            clientId: formData.clientId,
            type: formData.type,
            startDate: formData.startDate,
            endDate: formData.endDate || null,
            value: formData.value ? parseFloat(formData.value) : null,
            currency: formData.currency,
            billingCycle: formData.billingCycle || null,
            terms: formData.terms || null,
            autoRenew: formData.autoRenew,
            noticeRequired: formData.noticeRequired ? parseInt(formData.noticeRequired) : null,
          }),
        },
        { fallbackMessage: "Failed to create contract" },
      );

      // Reset form
      setFormData({
        title: "",
        description: "",
        clientId: defaultClientId || "",
        type: "service_agreement",
        startDate: "",
        endDate: "",
        value: "",
        currency: "USD",
        billingCycle: "monthly",
        terms: "",
        autoRenew: false,
        noticeRequired: "30",
      });

      onSuccess(contract);
    } catch (error) {
      console.error("Error creating contract:", error);
      alert(error instanceof Error ? error.message : "Failed to create contract");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[700px]">
        <form onSubmit={handleSubmit}>
          <DialogHeader>
            <DialogTitle>Create New Contract</DialogTitle>
            <DialogDescription>Create a new contract for a client. Fill in the details below.</DialogDescription>
          </DialogHeader>

          <Tabs defaultValue="basic" className="mt-4">
            <TabsList className="grid w-full grid-cols-3">
              <TabsTrigger value="basic">Basic Info</TabsTrigger>
              <TabsTrigger value="financial">Financial</TabsTrigger>
              <TabsTrigger value="terms">Terms</TabsTrigger>
            </TabsList>

            {/* Basic Info Tab */}
            <TabsContent value="basic" className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="title">Contract Title *</Label>
                <Input
                  id="title"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  placeholder="e.g., Website Development Agreement"
                  disabled={isLoading}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="client">Client *</Label>
                <Select
                  value={formData.clientId}
                  onValueChange={(value) => setFormData({ ...formData, clientId: value })}
                  disabled={isLoading}
                  required
                >
                  <SelectTrigger id="client">
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
              </div>

              <div className="space-y-2">
                <Label htmlFor="type">Contract Type</Label>
                <Select
                  value={formData.type}
                  onValueChange={(value) => setFormData({ ...formData, type: value })}
                  disabled={isLoading}
                >
                  <SelectTrigger id="type">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="service_agreement">Service Agreement</SelectItem>
                    <SelectItem value="msa">Master Service Agreement</SelectItem>
                    <SelectItem value="sow">Statement of Work</SelectItem>
                    <SelectItem value="nda">Non-Disclosure Agreement</SelectItem>
                    <SelectItem value="license">License Agreement</SelectItem>
                    <SelectItem value="other">Other</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  placeholder="Brief description of the contract"
                  rows={3}
                  disabled={isLoading}
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="startDate">Start Date *</Label>
                  <Input
                    id="startDate"
                    type="date"
                    value={formData.startDate}
                    onChange={(e) => setFormData({ ...formData, startDate: e.target.value })}
                    disabled={isLoading}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="endDate">End Date</Label>
                  <Input
                    id="endDate"
                    type="date"
                    value={formData.endDate}
                    onChange={(e) => setFormData({ ...formData, endDate: e.target.value })}
                    disabled={isLoading}
                  />
                </div>
              </div>
            </TabsContent>

            {/* Financial Tab */}
            <TabsContent value="financial" className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="value">Contract Value</Label>
                  <Input
                    id="value"
                    type="number"
                    step="0.01"
                    value={formData.value}
                    onChange={(e) => setFormData({ ...formData, value: e.target.value })}
                    placeholder="10000.00"
                    disabled={isLoading}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="currency">Currency</Label>
                  <Select
                    value={formData.currency}
                    onValueChange={(value) => setFormData({ ...formData, currency: value })}
                    disabled={isLoading}
                  >
                    <SelectTrigger id="currency">
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

              <div className="space-y-2">
                <Label htmlFor="billingCycle">Billing Cycle</Label>
                <Select
                  value={formData.billingCycle}
                  onValueChange={(value) => setFormData({ ...formData, billingCycle: value })}
                  disabled={isLoading}
                >
                  <SelectTrigger id="billingCycle">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="one_time">One-time</SelectItem>
                    <SelectItem value="monthly">Monthly</SelectItem>
                    <SelectItem value="quarterly">Quarterly</SelectItem>
                    <SelectItem value="annually">Annually</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </TabsContent>

            {/* Terms Tab */}
            <TabsContent value="terms" className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="terms">Terms & Conditions</Label>
                <Textarea
                  id="terms"
                  value={formData.terms}
                  onChange={(e) => setFormData({ ...formData, terms: e.target.value })}
                  placeholder="Enter contract terms and conditions"
                  rows={8}
                  disabled={isLoading}
                  className="font-mono text-sm"
                />
              </div>

              <div className="flex items-center justify-between rounded-lg border p-4">
                <div className="space-y-0.5">
                  <Label htmlFor="autoRenew">Auto-Renewal</Label>
                  <p className="text-sm text-muted-foreground">Automatically renew this contract when it expires</p>
                </div>
                <Switch
                  id="autoRenew"
                  checked={formData.autoRenew}
                  onCheckedChange={(checked) => setFormData({ ...formData, autoRenew: checked })}
                  disabled={isLoading}
                />
              </div>

              {formData.autoRenew && (
                <div className="space-y-2">
                  <Label htmlFor="noticeRequired">Notice Period (days)</Label>
                  <Input
                    id="noticeRequired"
                    type="number"
                    value={formData.noticeRequired}
                    onChange={(e) => setFormData({ ...formData, noticeRequired: e.target.value })}
                    placeholder="30"
                    disabled={isLoading}
                  />
                  <p className="text-xs text-muted-foreground">Days of notice required to cancel auto-renewal</p>
                </div>
              )}
            </TabsContent>
          </Tabs>

          <DialogFooter className="mt-6">
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={isLoading}>
              Cancel
            </Button>
            <Button type="submit" disabled={isLoading}>
              {isLoading ? "Creating..." : "Create Contract"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
