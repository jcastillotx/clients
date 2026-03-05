"use client";

import { FormEvent, useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
import { AlertCircle, CreditCard, Loader2 } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

type FormState = {
  invoiceNumber: string;
  paymentAmount: string;
  email: string;
  businessName: string;
  contactName: string;
  phone: string;
  businessInfo: string;
};

const initialFormState: FormState = {
  invoiceNumber: "",
  paymentAmount: "",
  email: "",
  businessName: "",
  contactName: "",
  phone: "",
  businessInfo: "",
};

export function PublicInvoicePaymentForm() {
  const searchParams = useSearchParams();
  const [formState, setFormState] = useState<FormState>({
    ...initialFormState,
    invoiceNumber: searchParams.get("invoice") || "",
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const status = searchParams.get("status");
  const invoiceFromQuery = searchParams.get("invoice");

  const statusMessage = useMemo(() => {
    if (status === "success") {
      return {
        variant: "success" as const,
        text: invoiceFromQuery
          ? `Payment completed successfully for invoice ${invoiceFromQuery}.`
          : "Payment completed successfully.",
      };
    }
    if (status === "cancelled") {
      return {
        variant: "warning" as const,
        text: "Payment was cancelled. You can retry anytime.",
      };
    }
    return null;
  }, [invoiceFromQuery, status]);

  const updateField = (field: keyof FormState, value: string) => {
    setFormState((previous) => ({
      ...previous,
      [field]: value,
    }));
  };

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      const response = await fetch("/api/public/invoices/create-checkout-session", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          invoiceNumber: formState.invoiceNumber,
          paymentAmount: formState.paymentAmount,
          email: formState.email,
          businessName: formState.businessName,
          contactName: formState.contactName,
          phone: formState.phone || null,
          businessInfo: formState.businessInfo || null,
        }),
      });

      const payload = await response.json();
      if (!response.ok) {
        const mismatchAmount =
          typeof payload?.invoiceAmount === "number"
            ? ` Invoice total is $${Number(payload.invoiceAmount).toFixed(2)}.`
            : "";
        throw new Error((payload?.error || "Unable to start payment.") + mismatchAmount);
      }

      if (!payload?.checkoutUrl) {
        throw new Error("Checkout session response is missing redirect URL.");
      }

      window.location.assign(payload.checkoutUrl as string);
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : "Unable to process payment.");
      setIsSubmitting(false);
      return;
    }
  };

  return (
    <div className="mx-auto w-full max-w-2xl py-8">
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <CreditCard className="h-5 w-5" />
            Pay Invoice
          </CardTitle>
          <CardDescription>
            Complete your invoice payment securely through Stripe. No account login is required.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-5">
          {statusMessage ? (
            <div
              className={
                statusMessage.variant === "success"
                  ? "rounded-md bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700"
                  : "rounded-md bg-amber-500/10 px-3 py-2 text-sm text-amber-700"
              }
            >
              {statusMessage.text}
            </div>
          ) : null}

          {error ? (
            <div className="flex items-start gap-2 rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
              <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
              <span>{error}</span>
            </div>
          ) : null}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="invoice-number">Invoice Number</Label>
                <Input
                  id="invoice-number"
                  required
                  value={formState.invoiceNumber}
                  onChange={(event) => updateField("invoiceNumber", event.target.value)}
                  placeholder="INV-1001"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="payment-amount">Payment Amount (USD)</Label>
                <Input
                  id="payment-amount"
                  type="number"
                  step="0.01"
                  min="0.01"
                  required
                  value={formState.paymentAmount}
                  onChange={(event) => updateField("paymentAmount", event.target.value)}
                  placeholder="0.00"
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                required
                value={formState.email}
                onChange={(event) => updateField("email", event.target.value)}
                placeholder="billing@yourcompany.com"
              />
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="business-name">Business Name</Label>
                <Input
                  id="business-name"
                  required
                  value={formState.businessName}
                  onChange={(event) => updateField("businessName", event.target.value)}
                  placeholder="Acme, Inc."
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="contact-name">Contact Name</Label>
                <Input
                  id="contact-name"
                  required
                  value={formState.contactName}
                  onChange={(event) => updateField("contactName", event.target.value)}
                  placeholder="Jane Doe"
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="phone">Phone (optional)</Label>
              <Input
                id="phone"
                value={formState.phone}
                onChange={(event) => updateField("phone", event.target.value)}
                placeholder="+1 555-123-4567"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="business-info">Business Information (optional)</Label>
              <Textarea
                id="business-info"
                value={formState.businessInfo}
                onChange={(event) => updateField("businessInfo", event.target.value)}
                placeholder="Billing address, department, PO reference, or other details."
                rows={3}
              />
            </div>

            <div className="flex items-center justify-end">
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Redirecting to Stripe...
                  </>
                ) : (
                  "Continue to Secure Checkout"
                )}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
