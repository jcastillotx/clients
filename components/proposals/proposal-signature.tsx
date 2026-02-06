"use client";

import { useState, useRef } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import SignatureCanvas from "react-signature-canvas";
import { Loader2, CheckCircle } from "lucide-react";

interface ProposalSignatureProps {
  proposalId: string;
  token?: string;
}

export function ProposalSignature({ proposalId, token }: ProposalSignatureProps) {
  const [signerName, setSignerName] = useState("");
  const [signerEmail, setSignerEmail] = useState("");
  const [agreed, setAgreed] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const signatureRef = useRef<SignatureCanvas>(null);

  const handleClearSignature = () => {
    signatureRef.current?.clear();
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!signerName || !signerEmail) {
      setError("Please provide your name and email");
      return;
    }

    if (!agreed) {
      setError("You must agree to the terms and conditions");
      return;
    }

    if (signatureRef.current?.isEmpty()) {
      setError("Please provide your signature");
      return;
    }

    setIsSubmitting(true);
    setError(null);

    try {
      const signatureData = signatureRef.current?.toDataURL();

      const response = await fetch(`/api/proposals/${proposalId}/sign`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "accept",
          signatureData,
          signerName,
          signerEmail,
          token,
        }),
      });

      if (!response.ok) {
        const data = await response.json();
        throw new Error(data.error || "Failed to submit signature");
      }

      window.location.reload();
    } catch (err) {
      console.error("Error submitting signature:", err);
      setError(err instanceof Error ? err.message : "Failed to submit signature");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className="border-2 border-primary">
      <CardHeader>
        <CardTitle>Sign Proposal</CardTitle>
        <CardDescription>
          By signing this proposal, you agree to the terms and conditions outlined above.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-6">
          {error && <div className="bg-destructive/15 text-destructive px-4 py-3 rounded-lg">{error}</div>}

          <div className="grid md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="signerName">Full Name *</Label>
              <Input
                id="signerName"
                value={signerName}
                onChange={(e) => setSignerName(e.target.value)}
                placeholder="John Doe"
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="signerEmail">Email Address *</Label>
              <Input
                id="signerEmail"
                type="email"
                value={signerEmail}
                onChange={(e) => setSignerEmail(e.target.value)}
                placeholder="john@example.com"
                required
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label>Signature *</Label>
            <div className="border-2 border-dashed rounded-lg bg-white">
              <SignatureCanvas
                ref={signatureRef}
                canvasProps={{
                  className: "w-full h-40",
                }}
              />
            </div>
            <div className="flex justify-end">
              <Button type="button" variant="ghost" size="sm" onClick={handleClearSignature}>
                Clear Signature
              </Button>
            </div>
          </div>

          <div className="flex items-start space-x-3">
            <Checkbox id="terms" checked={agreed} onCheckedChange={(checked) => setAgreed(checked as boolean)} />
            <div className="grid gap-1.5 leading-none">
              <label
                htmlFor="terms"
                className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
              >
                I agree to the terms and conditions
              </label>
              <p className="text-sm text-muted-foreground">
                By checking this box, you agree to all terms and conditions outlined in this proposal.
              </p>
            </div>
          </div>

          <div className="flex justify-end">
            <Button type="submit" disabled={isSubmitting} size="lg" className="min-w-48">
              {isSubmitting ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Submitting...
                </>
              ) : (
                <>
                  <CheckCircle className="mr-2 h-4 w-4" />
                  Accept & Sign Proposal
                </>
              )}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
