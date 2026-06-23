"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { fetchApi } from "@/lib/api/client";

type PartnerOption = {
  id: string;
  company_name: string;
  code: string;
};

export function ReferralForm() {
  const router = useRouter();
  const [partners, setPartners] = useState<PartnerOption[]>([]);
  const [loadingPartners, setLoadingPartners] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({
    partnerId: "",
    referredName: "",
    referredEmail: "",
    referredPhone: "",
    status: "pending",
    notes: "",
  });

  useEffect(() => {
    void (async () => {
      try {
        const data = await fetchApi<PartnerOption[]>("/api/partners", undefined, {
          fallbackMessage: "Failed to load partners",
        });
        setPartners(data);
      } catch {
        setPartners([]);
      } finally {
        setLoadingPartners(false);
      }
    })();
  }, []);

  const update = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!form.partnerId) {
      setError("Select a partner");
      return;
    }

    setIsSubmitting(true);
    setError(null);

    try {
      await fetchApi(
        "/api/referrals",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            partnerId: form.partnerId,
            referredName: form.referredName,
            referredEmail: form.referredEmail || null,
            referredPhone: form.referredPhone || null,
            status: form.status,
            notes: form.notes || null,
          }),
        },
        { fallbackMessage: "Failed to create referral" },
      );
      router.push("/referrals");
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to create referral");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6 max-w-2xl">
      {error ? <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</div> : null}

      <div className="space-y-2">
        <Label htmlFor="partnerId">Partner *</Label>
        <Select value={form.partnerId} onValueChange={(value) => update("partnerId", value)} disabled={loadingPartners}>
          <SelectTrigger id="partnerId">
            <SelectValue placeholder={loadingPartners ? "Loading partners..." : "Select partner"} />
          </SelectTrigger>
          <SelectContent>
            {partners.map((partner) => (
              <SelectItem key={partner.id} value={partner.id}>
                {partner.company_name} ({partner.code})
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="referredName">Referred Name *</Label>
          <Input id="referredName" required value={form.referredName} onChange={(e) => update("referredName", e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="referredEmail">Referred Email</Label>
          <Input id="referredEmail" type="email" value={form.referredEmail} onChange={(e) => update("referredEmail", e.target.value)} />
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="referredPhone">Referred Phone</Label>
          <Input id="referredPhone" value={form.referredPhone} onChange={(e) => update("referredPhone", e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="status">Status</Label>
          <Select value={form.status} onValueChange={(value) => update("status", value)}>
            <SelectTrigger id="status">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="pending">Pending</SelectItem>
              <SelectItem value="contacted">Contacted</SelectItem>
              <SelectItem value="qualified">Qualified</SelectItem>
              <SelectItem value="converted">Converted</SelectItem>
              <SelectItem value="rejected">Rejected</SelectItem>
              <SelectItem value="lost">Lost</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="notes">Notes</Label>
        <Textarea id="notes" rows={3} value={form.notes} onChange={(e) => update("notes", e.target.value)} />
      </div>

      <div className="flex gap-3">
        <Button type="submit" disabled={isSubmitting || loadingPartners}>
          {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          Create Referral
        </Button>
        <Button type="button" variant="outline" onClick={() => router.push("/referrals")} disabled={isSubmitting}>
          Cancel
        </Button>
      </div>
    </form>
  );
}
