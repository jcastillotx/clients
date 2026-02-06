"use client";

import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Separator } from "@/components/ui/separator";
import { Badge } from "@/components/ui/badge";
import { ProposalSignature } from "./proposal-signature";
import { CheckCircle, XCircle, FileText, Clock } from "lucide-react";
import { format } from "date-fns";

interface ProposalPreviewProps {
  proposal: any;
  token?: string;
}

export function ProposalPreview({ proposal, token }: ProposalPreviewProps) {
  const [isTracked, setIsTracked] = useState(false);
  const [showSignature, setShowSignature] = useState(false);

  useEffect(() => {
    if (!isTracked && proposal.status !== "draft") {
      trackView();
      setIsTracked(true);
    }
  }, []);

  const trackView = async () => {
    try {
      await fetch(`/api/proposals/${proposal.id}/track-view`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ token }),
      });
    } catch (error) {
      console.error("Error tracking view:", error);
    }
  };

  const handleAccept = () => {
    setShowSignature(true);
  };

  const handleReject = async () => {
    if (!confirm("Are you sure you want to reject this proposal?")) return;

    try {
      const response = await fetch(`/api/proposals/${proposal.id}/sign`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "reject", token }),
      });

      if (!response.ok) throw new Error("Failed to reject proposal");

      window.location.reload();
    } catch (error) {
      console.error("Error rejecting proposal:", error);
      alert("Failed to reject proposal");
    }
  };

  const isExpired = proposal.valid_until && new Date(proposal.valid_until) < new Date();
  const canAccept = proposal.status === "sent" || proposal.status === "viewed";
  const isAccepted = proposal.status === "accepted";
  const isRejected = proposal.status === "rejected";

  return (
    <div className="max-w-5xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      {/* Header */}
      <div className="text-center mb-8">
        <div className="flex justify-center mb-4">
          <FileText className="h-16 w-16 text-primary" />
        </div>
        <h1 className="text-4xl font-bold mb-2">{proposal.title}</h1>
        <p className="text-muted-foreground">Proposal from {proposal.creator?.name || "KRE8IV"}</p>
      </div>

      {/* Status Banner */}
      {isExpired && (
        <div className="bg-yellow-500/10 border border-yellow-500 text-yellow-700 px-6 py-4 rounded-lg mb-6">
          <div className="flex items-center gap-2">
            <Clock className="h-5 w-5" />
            <span className="font-semibold">This proposal has expired</span>
          </div>
        </div>
      )}

      {isAccepted && (
        <div className="bg-green-500/10 border border-green-500 text-green-700 px-6 py-4 rounded-lg mb-6">
          <div className="flex items-center gap-2">
            <CheckCircle className="h-5 w-5" />
            <span className="font-semibold">
              This proposal was accepted on {format(new Date(proposal.accepted_at), "PPP")}
            </span>
          </div>
        </div>
      )}

      {isRejected && (
        <div className="bg-red-500/10 border border-red-500 text-red-700 px-6 py-4 rounded-lg mb-6">
          <div className="flex items-center gap-2">
            <XCircle className="h-5 w-5" />
            <span className="font-semibold">
              This proposal was rejected on {format(new Date(proposal.rejected_at), "PPP")}
            </span>
          </div>
        </div>
      )}

      {/* Client & Proposal Info */}
      <div className="grid md:grid-cols-2 gap-6 mb-8">
        <Card>
          <CardHeader>
            <CardTitle>For</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            <p className="font-semibold text-lg">{proposal.client?.company_name}</p>
            <p className="text-sm text-muted-foreground">{proposal.client?.email}</p>
            {proposal.client?.phone && <p className="text-sm text-muted-foreground">{proposal.client.phone}</p>}
            {proposal.client?.address && (
              <p className="text-sm text-muted-foreground">
                {proposal.client.address}
                {proposal.client.city && `, ${proposal.client.city}`}
                {proposal.client.state && `, ${proposal.client.state}`}
                {proposal.client.zip_code && ` ${proposal.client.zip_code}`}
              </p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Proposal Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            <div>
              <p className="text-sm text-muted-foreground">Total Amount</p>
              <p className="text-3xl font-bold">
                ${Number(proposal.total_amount).toLocaleString()} {proposal.currency}
              </p>
            </div>
            <Separator className="my-2" />
            {proposal.valid_until && (
              <div>
                <p className="text-sm text-muted-foreground">Valid Until</p>
                <p className="font-medium">{format(new Date(proposal.valid_until), "PPP")}</p>
              </div>
            )}
            <div>
              <p className="text-sm text-muted-foreground">Created</p>
              <p className="font-medium">{format(new Date(proposal.created_at), "PPP")}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Description */}
      {proposal.description && (
        <Card className="mb-8">
          <CardHeader>
            <CardTitle>Overview</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="whitespace-pre-wrap">{proposal.description}</p>
          </CardContent>
        </Card>
      )}

      {/* Line Items */}
      <Card className="mb-8">
        <CardHeader>
          <CardTitle>Proposal Details</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Description</TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead className="text-right">Quantity</TableHead>
                  <TableHead className="text-right">Unit Price</TableHead>
                  <TableHead className="text-right">Amount</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {proposal.line_items?.map((item: any, index: number) => (
                  <TableRow key={index}>
                    <TableCell className="font-medium">{item.description}</TableCell>
                    <TableCell>{item.category || "—"}</TableCell>
                    <TableCell className="text-right">{item.quantity}</TableCell>
                    <TableCell className="text-right">${Number(item.unitPrice).toFixed(2)}</TableCell>
                    <TableCell className="text-right">${Number(item.amount).toFixed(2)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>

          <Separator className="my-6" />

          <div className="flex justify-end">
            <div className="space-y-2 w-80">
              <div className="flex justify-between text-2xl font-bold">
                <span>Total:</span>
                <span>
                  ${Number(proposal.total_amount).toLocaleString()} {proposal.currency}
                </span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Terms & Conditions */}
      {proposal.terms && (
        <Card className="mb-8">
          <CardHeader>
            <CardTitle>Terms & Conditions</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="whitespace-pre-wrap text-sm">{proposal.terms}</p>
          </CardContent>
        </Card>
      )}

      {/* Client Notes */}
      {proposal.metadata?.notes && (
        <Card className="mb-8">
          <CardHeader>
            <CardTitle>Additional Notes</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="whitespace-pre-wrap">{proposal.metadata.notes}</p>
          </CardContent>
        </Card>
      )}

      {/* Signature Section */}
      {showSignature ? (
        <ProposalSignature proposalId={proposal.id} token={token} />
      ) : (
        canAccept &&
        !isExpired && (
          <Card className="border-2 border-primary">
            <CardContent className="pt-6">
              <div className="text-center space-y-4">
                <h3 className="text-xl font-semibold">Ready to move forward?</h3>
                <p className="text-muted-foreground">
                  Review the proposal details above and accept or reject this proposal.
                </p>
                <div className="flex justify-center gap-4 mt-6">
                  <Button variant="outline" onClick={handleReject} className="min-w-32">
                    <XCircle className="mr-2 h-4 w-4" />
                    Reject
                  </Button>
                  <Button onClick={handleAccept} className="min-w-32">
                    <CheckCircle className="mr-2 h-4 w-4" />
                    Accept Proposal
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        )
      )}

      {/* Accepted Signature Display */}
      {isAccepted && proposal.signature_data && (
        <Card>
          <CardHeader>
            <CardTitle>Signature</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="border rounded-lg p-4 bg-muted/30">
              <img src={proposal.signature_data.signatureImage} alt="Signature" className="max-w-md mx-auto" />
            </div>
            <div className="text-sm text-muted-foreground space-y-1">
              <p>
                <strong>Signed by:</strong> {proposal.signature_data.signedBy}
              </p>
              <p>
                <strong>Signed on:</strong> {format(new Date(proposal.signature_data.signedAt), "PPpp")}
              </p>
              <p>
                <strong>IP Address:</strong> {proposal.signature_data.ipAddress}
              </p>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
