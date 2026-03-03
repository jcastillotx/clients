import { PublicInvoicePaymentForm } from "@/components/payments/public-invoice-payment-form";

export const metadata = {
  title: "Pay Invoice",
  description: "Public invoice payment form powered by Stripe",
};

export default function PayInvoicePage() {
  return (
    <main className="min-h-screen bg-gradient-to-br from-background via-secondary/20 to-background px-4 py-10">
      <div className="mx-auto w-full max-w-3xl space-y-2 text-center">
        <h1 className="text-3xl font-bold tracking-tight">Invoice Payment</h1>
        <p className="text-sm text-muted-foreground">
          Enter your invoice details below to pay securely with Stripe.
        </p>
      </div>
      <PublicInvoicePaymentForm />
    </main>
  );
}
