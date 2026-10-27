"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import {
  Elements,
  PaymentElement,
  useStripe,
  useElements,
} from "@stripe/react-stripe-js";
import { loadStripe } from "@stripe/stripe-js";
import { Loader2 } from "lucide-react";

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

function PaymentForm({
  invoice,
  onSuccess,
}: {
  invoice: PaymentModalProps["invoice"];
  onSuccess: () => void;
}) {
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
      const { error: submitError, paymentIntent } = await stripe.confirmPayment(
        {
          elements,
          confirmParams: {
            return_url: `${window.location.origin}/invoices/${invoice.id}?payment=success`,
          },
          redirect: "if_required",
        },
      );

      if (submitError) {
        setError(submitError.message || "Payment failed");
      } else if (
        paymentIntent?.status === "succeeded" ||
        paymentIntent?.status === "processing"
      ) {
        onSuccess();
      } else {
        setError(
          "Payment has not completed. Please finish authentication or try again.",
        );
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Payment failed");
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {error && (
        <div className="rounded-md bg-destructive/10 p-4 text-sm text-destructive">
          {error}
        </div>
      )}

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

export function PaymentModal({
  invoice,
  open,
  onOpenChange,
}: PaymentModalProps) {
  const router = useRouter();
  const [clientSecret, setClientSecret] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  const [publishableKey, setPublishableKey] = useState<string | null>(null);
  const [initializationError, setInitializationError] = useState<string | null>(
    null,
  );
  const stripePromise = useMemo(
    () => (publishableKey ? loadStripe(publishableKey) : null),
    [publishableKey],
  );

  useEffect(() => {
    if (!open) return;
    const controller = new AbortController();
    setClientSecret(null);
    setInitializationError(null);
    setIsLoading(true);
    void (async () => {
      try {
        const response = await fetch("/api/payments/create-intent", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ invoiceId: invoice.id }),
          signal: controller.signal,
        });
        const data = await response.json();
        if (!response.ok)
          throw new Error(
            data.error?.message || "Unable to initialize payment.",
          );
        const payment = data.data ?? data;
        if (!payment.clientSecret || !payment.publishableKey)
          throw new Error(
            "Online payments are not configured for this invoice.",
          );
        setPublishableKey(payment.publishableKey);
        setClientSecret(payment.clientSecret);
      } catch (error) {
        if (!controller.signal.aborted)
          setInitializationError(
            error instanceof Error
              ? error.message
              : "Unable to initialize payment.",
          );
      } finally {
        if (!controller.signal.aborted) setIsLoading(false);
      }
    })();
    return () => controller.abort();
  }, [open, invoice.id]);

  const handleSuccess = () => {
    onOpenChange(false);
    router.refresh();
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        className="max-h-[90dvh] overflow-y-auto sm:max-w-md"
        onInteractOutside={(event) => event.preventDefault()}
      >
        <DialogHeader>
          <DialogTitle>Pay Invoice</DialogTitle>
          <DialogDescription>
            Complete payment for invoice {invoice.invoice_number}
          </DialogDescription>
        </DialogHeader>

        {isLoading ? (
          <div className="flex items-center justify-center py-8">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
          </div>
        ) : clientSecret && stripePromise ? (
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
          <div className="py-8 text-center text-sm text-muted-foreground">
            {initializationError ||
              "Unable to initialize payment. Please try again."}
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
