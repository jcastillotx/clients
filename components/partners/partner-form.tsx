"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { fetchApi } from "@/lib/api/client";

export function PartnerForm() {
  const router = useRouter();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({
    companyName: "",
    contactName: "",
    email: "",
    phone: "",
    website: "",
    partnerType: "affiliate",
    status: "active",
    commissionRate: "10",
    code: "",
    notes: "",
  });

  const update = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setIsSubmitting(true);
    setError(null);

    try {
      await fetchApi(
        "/api/partners",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            companyName: form.companyName,
            contactName: form.contactName,
            email: form.email,
            phone: form.phone || null,
            website: form.website || null,
            partnerType: form.partnerType,
            status: form.status,
            commissionRate: Number(form.commissionRate),
            code: form.code.trim() || undefined,
            notes: form.notes || null,
          }),
        },
        { fallbackMessage: "Failed to create partner" },
      );
      router.push("/partners");
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to create partner");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6 max-w-2xl">
      {error ? <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</div> : null}

      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="companyName">Company Name *</Label>
          <Input id="companyName" required value={form.companyName} onChange={(e) => update("companyName", e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="contactName">Contact Name *</Label>
          <Input id="contactName" required value={form.contactName} onChange={(e) => update("contactName", e.target.value)} />
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="email">Email *</Label>
          <Input id="email" type="email" required value={form.email} onChange={(e) => update("email", e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="phone">Phone</Label>
          <Input id="phone" value={form.phone} onChange={(e) => update("phone", e.target.value)} />
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="partnerType">Partner Type</Label>
          <Select value={form.partnerType} onValueChange={(value) => update("partnerType", value)}>
            <SelectTrigger id="partnerType">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="agency">Agency</SelectItem>
              <SelectItem value="affiliate">Affiliate</SelectItem>
              <SelectItem value="reseller">Reseller</SelectItem>
              <SelectItem value="strategic">Strategic</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div className="space-y-2">
          <Label htmlFor="commissionRate">Commission Rate (%)</Label>
          <Input
            id="commissionRate"
            type="number"
            min="0"
            max="100"
            step="0.01"
            value={form.commissionRate}
            onChange={(e) => update("commissionRate", e.target.value)}
          />
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="website">Website</Label>
          <Input id="website" value={form.website} onChange={(e) => update("website", e.target.value)} placeholder="https://example.com" />
        </div>
        <div className="space-y-2">
          <Label htmlFor="code">Referral Code (optional)</Label>
          <Input id="code" value={form.code} onChange={(e) => update("code", e.target.value)} placeholder="Auto-generated if blank" />
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="notes">Notes</Label>
        <Textarea id="notes" rows={3} value={form.notes} onChange={(e) => update("notes", e.target.value)} />
      </div>

      <div className="flex gap-3">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          Create Partner
        </Button>
        <Button type="button" variant="outline" onClick={() => router.push("/partners")} disabled={isSubmitting}>
          Cancel
        </Button>
      </div>
    </form>
  );
}
