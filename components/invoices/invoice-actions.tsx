"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Send, Download, CheckCircle2, XCircle, Loader2, CreditCard, Link as LinkIcon } from "lucide-react";
import { createClient } from "@/lib/supabase/client";
import { useRouter } from "next/navigation";
import { PaymentModal } from "./payment-modal";
import { toast } from "sonner";

interface InvoiceActionsProps {
  invoice: {
    id: string;
    status: string;
    invoice_number: string;
    amount: number;
  };
  canManageInvoices: boolean;
}

export function InvoiceActions({ invoice, canManageInvoices }: InvoiceActionsProps) {
  const router = useRouter();
  const [isUpdating, setIsUpdating] = useState(false);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [copied, setCopied] = useState(false);

  const handleSendInvoice = async () => {
    setIsUpdating(true);
    try {
      const response = await fetch(`/api/invoices/${invoice.id}/send`, { method: "POST" });
      const payload = (await response.json().catch(() => ({}))) as { error?: string; sentTo?: string };

      if (!response.ok) {
        throw new Error(payload.error || "Failed to send invoice");
      }

      toast.success("Invoice sent", {
        description: payload.sentTo ? `Email delivered to ${payload.sentTo}` : undefined,
      });
      router.refresh();
    } catch (error) {
      console.error("Failed to send invoice:", error);
      toast.error("Could not send invoice", {
        description: error instanceof Error ? error.message : "Unknown error",
      });
    } finally {
      setIsUpdating(false);
    }
  };

  const handleStatusUpdate = async (newStatus: string) => {
    setIsUpdating(true);
    try {
      const supabase = createClient();
      const updateData: { status: string; paid_at?: string } = { status: newStatus };

      if (newStatus === "paid") {
        updateData.paid_at = new Date().toISOString();
      }

      const { error } = await supabase.from("invoices").update(updateData).eq("id", invoice.id);

      if (error) throw error;

      router.refresh();
    } catch (error) {
      console.error("Failed to update invoice:", error);
    } finally {
      setIsUpdating(false);
    }
  };

  const handleDownloadPDF = async () => {
    try {
      const response = await fetch(`/api/invoices/${invoice.id}/pdf`);
      if (!response.ok) throw new Error("Failed to download PDF");

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `invoice-${invoice.invoice_number}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error("Failed to download PDF:", error);
    }
  };

  const handleCopyPublicPaymentLink = async () => {
    try {
      const publicUrl = `${window.location.origin}/pay-invoice?invoice=${encodeURIComponent(invoice.invoice_number)}`;
      await navigator.clipboard.writeText(publicUrl);
      setCopied(true);
      window.setTimeout(() => setCopied(false), 2000);
    } catch (error) {
      console.error("Failed to copy payment link:", error);
    }
  };

  return (
    <>
      <Card>
        <CardHeader>
          <CardTitle>Actions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-3">
            {canManageInvoices && invoice.status === "draft" && (
              <Button onClick={handleSendInvoice} disabled={isUpdating}>
                {isUpdating ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Send className="mr-2 h-4 w-4" />}
                Send Invoice
              </Button>
            )}

            {invoice.status === "sent" && (
              <>
                <Button onClick={() => setShowPaymentModal(true)} disabled={isUpdating}>
                  <CreditCard className="mr-2 h-4 w-4" />
                  Pay Invoice
                </Button>

                {canManageInvoices && (
                  <Button variant="outline" onClick={() => handleStatusUpdate("paid")} disabled={isUpdating}>
                    {isUpdating ? (
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    ) : (
                      <CheckCircle2 className="mr-2 h-4 w-4" />
                    )}
                    Mark as Paid
                  </Button>
                )}
              </>
            )}

            {canManageInvoices && ["draft", "sent"].includes(invoice.status) && (
              <Button variant="outline" onClick={() => handleStatusUpdate("cancelled")} disabled={isUpdating}>
                {isUpdating ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <XCircle className="mr-2 h-4 w-4" />}
                Cancel Invoice
              </Button>
            )}

            <Button variant="outline" onClick={handleDownloadPDF}>
              <Download className="mr-2 h-4 w-4" />
              Download PDF
            </Button>

            <Button variant="outline" onClick={handleCopyPublicPaymentLink}>
              <LinkIcon className="mr-2 h-4 w-4" />
              {copied ? "Payment Link Copied" : "Copy Public Payment Link"}
            </Button>
          </div>

          {invoice.status === "paid" && (
            <p className="text-sm text-muted-foreground mt-4">This invoice has been paid.</p>
          )}

          {invoice.status === "cancelled" && (
            <p className="text-sm text-muted-foreground mt-4">This invoice has been cancelled.</p>
          )}
        </CardContent>
      </Card>

      <PaymentModal invoice={invoice} open={showPaymentModal} onOpenChange={setShowPaymentModal} />
    </>
  );
}
