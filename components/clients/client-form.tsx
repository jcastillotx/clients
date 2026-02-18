"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Loader2 } from "lucide-react";

const clientSchema = z.object({
  companyName: z.string().min(2, "Company name must be at least 2 characters"),
  domain: z.string().optional(),
  industry: z.string().optional(),
  status: z.enum(["active", "inactive", "pending", "suspended"]).default("active"),
  primaryContactId: z.preprocess(
    (value) => (typeof value === "string" && value.trim() === "" ? undefined : value),
    z.string().uuid().optional(),
  ),
  phone: z.string().optional(),
  address: z.string().optional(),
  city: z.string().optional(),
  state: z.string().optional(),
  zipCode: z.string().optional(),
  country: z.string().optional(),
});

type ClientFormInput = z.infer<typeof clientSchema>;

interface ClientFormProps {
  users: Array<{
    id: string;
    name: string;
    email: string;
  }>;
  initialData?: {
    id: string;
    company_name: string;
    domain?: string;
    industry?: string;
    status: string;
    primary_contact_id?: string;
    phone?: string;
    address?: string;
    city?: string;
    state?: string;
    zip_code?: string;
    country?: string;
  };
}

export function ClientForm({ users, initialData }: ClientFormProps) {
  const router = useRouter();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const isEditing = !!initialData;

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    watch,
  } = useForm<ClientFormInput>({
    resolver: zodResolver(clientSchema),
    defaultValues: {
      companyName: initialData?.company_name || "",
      domain: initialData?.domain || "",
      industry: initialData?.industry || "",
      status: (initialData?.status as any) || "active",
      primaryContactId: initialData?.primary_contact_id || undefined,
      phone: initialData?.phone || "",
      address: initialData?.address || "",
      city: initialData?.city || "",
      state: initialData?.state || "",
      zipCode: initialData?.zip_code || "",
      country: initialData?.country || "",
    },
  });

  const status = watch("status");
  const primaryContactId = watch("primaryContactId");

  const onSubmit = async (data: ClientFormInput) => {
    setIsSubmitting(true);
    setError(null);

    try {
      const clientData = {
        company_name: data.companyName,
        domain: data.domain || null,
        industry: data.industry || null,
        status: data.status,
        primary_contact_id: data.primaryContactId || null,
        phone: data.phone || null,
        address: data.address || null,
        city: data.city || null,
        state: data.state || null,
        zip_code: data.zipCode || null,
        country: data.country || null,
      };

      if (isEditing) {
        // Update existing client
        const response = await fetch(`/api/clients/${initialData.id}`, {
          method: "PATCH",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(clientData),
        });

        if (!response.ok) {
          const payload = await response.json().catch(() => ({ error: "Failed to update client" }));
          throw new Error(payload.error || "Failed to update client");
        }

        router.push(`/clients/${initialData.id}`);
      } else {
        // Create new client
        const response = await fetch("/api/clients", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(clientData),
        });

        if (!response.ok) {
          const payload = await response.json().catch(() => ({ error: "Failed to create client" }));
          throw new Error(payload.error || "Failed to create client");
        }

        const { client } = await response.json();

        router.push(`/clients/${client.id}`);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : `Failed to ${isEditing ? "update" : "create"} client`);
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      {error && <div className="rounded-md bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}

      {/* Company Information */}
      <Card>
        <CardHeader>
          <CardTitle>Company Information</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="companyName">
                Company Name <span className="text-destructive">*</span>
              </Label>
              <Input id="companyName" placeholder="Acme Corporation" {...register("companyName")} />
              {errors.companyName && <p className="text-sm text-destructive">{errors.companyName.message}</p>}
            </div>

            <div className="space-y-2">
              <Label htmlFor="domain">Website Domain</Label>
              <Input id="domain" placeholder="acme.com" {...register("domain")} />
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="industry">Industry</Label>
              <Input id="industry" placeholder="Technology, Healthcare, etc." {...register("industry")} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="phone">Phone</Label>
              <Input id="phone" type="tel" placeholder="+1 (555) 123-4567" {...register("phone")} />
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="status">Status</Label>
              <Select value={status} onValueChange={(value) => setValue("status", value as any)}>
                <SelectTrigger id="status">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="suspended">Suspended</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="primaryContactId">Primary Contact</Label>
              <Select
                value={primaryContactId || undefined}
                onValueChange={(value) => setValue("primaryContactId", value)}
              >
                <SelectTrigger id="primaryContactId">
                  <SelectValue placeholder="Select a contact" />
                </SelectTrigger>
                <SelectContent>
                  {users.map((user) => (
                    <SelectItem key={user.id} value={user.id}>
                      {user.name} ({user.email})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Address Information */}
      <Card>
        <CardHeader>
          <CardTitle>Address Information</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="address">Street Address</Label>
            <Textarea id="address" placeholder="123 Main Street, Suite 100" rows={2} {...register("address")} />
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="city">City</Label>
              <Input id="city" placeholder="San Francisco" {...register("city")} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="state">State/Province</Label>
              <Input id="state" placeholder="CA" {...register("state")} />
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="zipCode">ZIP/Postal Code</Label>
              <Input id="zipCode" placeholder="94105" {...register("zipCode")} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="country">Country</Label>
              <Input id="country" placeholder="United States" {...register("country")} />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Actions */}
      <div className="flex gap-4">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          {isEditing ? "Update Client" : "Create Client"}
        </Button>
        <Button type="button" variant="outline" onClick={() => router.back()}>
          Cancel
        </Button>
      </div>
    </form>
  );
}
