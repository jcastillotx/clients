"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Elements, PaymentElement, useStripe, useElements } from "@stripe/react-stripe-js";
import { loadStripe } from "@stripe/stripe-js";
import { Loader2 } from "lucide-react";

const stripePromise = loadStripe(process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY!);

interface PaymentModalProps {
  invoice: {
    id: string;
    invoice_number: string;
    amount: number;
    status: string;
  };
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

function PaymentForm({ invoice, onSuccess }: { invoice: PaymentModalProps["invoice"]; onSuccess: () => void }) {
  const stripe = useStripe();
  const elements = useElements();
  const [isProcessing, setIsProcessing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!stripe || !elements) {
      return;
    }

    setIsProcessing(true);
    setError(null);

    try {
      const { error: submitError } = await stripe.confirmPayment({
        elements,
        confirmParams: {
          return_url: `${window.location.origin}/invoices/${invoice.id}?payment=success`,
        },
        redirect: "if_required",
      });

      if (submitError) {
        setError(submitError.message || "Payment failed");
      } else {
        onSuccess();
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Payment failed");
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {error && <div className="rounded-md bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}

      <PaymentElement />

      <div className="flex justify-end gap-3">
        <Button type="submit" disabled={!stripe || isProcessing}>
          {isProcessing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          Pay ${invoice.amount.toFixed(2)}
        </Button>
      </div>
    </form>
  );
}

export function PaymentModal({ invoice, open, onOpenChange }: PaymentModalProps) {
  const router = useRouter();
  const [clientSecret, setClientSecret] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  const handleOpenChange = async (newOpen: boolean) => {
    onOpenChange(newOpen);

    if (newOpen && !clientSecret) {
      setIsLoading(true);
      try {
        const response = await fetch("/api/payments/create-intent", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ invoiceId: invoice.id }),
        });

        if (!response.ok) {
          throw new Error("Failed to create payment intent");
        }

        const data = await response.json();
        setClientSecret(data.clientSecret);
      } catch (error) {
        console.error("Error creating payment intent:", error);
      } finally {
        setIsLoading(false);
      }
    }
  };

  const handleSuccess = () => {
    onOpenChange(false);
    router.refresh();
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Pay Invoice</DialogTitle>
          <DialogDescription>Complete payment for invoice {invoice.invoice_number}</DialogDescription>
        </DialogHeader>

        {isLoading ? (
          <div className="flex items-center justify-center py-8">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
          </div>
        ) : clientSecret ? (
          <Elements
            stripe={stripePromise}
            options={{
              clientSecret,
              appearance: {
                theme: "stripe",
              },
            }}
          >
            <PaymentForm invoice={invoice} onSuccess={handleSuccess} />
          </Elements>
        ) : (
          <div className="py-8 text-center text-sm text-muted-foreground">Click to start payment process</div>
        )}
      </DialogContent>
    </Dialog>
  );
}
