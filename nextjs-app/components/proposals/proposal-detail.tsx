"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Separator } from "@/components/ui/separator";
import { createClient } from "@/lib/supabase/client";
import { FileText, Send, Edit, Trash2, Eye, Download, CheckCircle, XCircle, Clock, MoreVertical } from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { format } from "date-fns";

interface ProposalDetailProps {
  proposal: any;
  clients: Array<{
    id: string;
    company_name: string;
    email: string;
  }>;
}

const statusConfig = {
  draft: { label: "Draft", color: "bg-gray-500", icon: FileText },
  sent: { label: "Sent", color: "bg-blue-500", icon: Clock },
  viewed: { label: "Viewed", color: "bg-yellow-500", icon: Eye },
  accepted: { label: "Accepted", color: "bg-green-500", icon: CheckCircle },
  rejected: { label: "Rejected", color: "bg-red-500", icon: XCircle },
  expired: { label: "Expired", color: "bg-gray-400", icon: Clock },
};

export function ProposalDetail({ proposal, clients }: ProposalDetailProps) {
  const router = useRouter();
  const [isDeleting, setIsDeleting] = useState(false);
  const [isSending, setIsSending] = useState(false);

  const StatusIcon = statusConfig[proposal.status as keyof typeof statusConfig]?.icon || FileText;

  const handleSend = async () => {
    setIsSending(true);
    try {
      const response = await fetch(`/api/proposals/${proposal.id}/send`, {
        method: "POST",
      });

      if (!response.ok) throw new Error("Failed to send proposal");

      router.refresh();
    } catch (error) {
      console.error("Error sending proposal:", error);
      alert("Failed to send proposal");
    } finally {
      setIsSending(false);
    }
  };

  const handleDelete = async () => {
    if (!confirm("Are you sure you want to delete this proposal?")) return;

    setIsDeleting(true);
    try {
      const supabase = createClient();
      const { error } = await supabase.from("proposals").delete().eq("id", proposal.id);

      if (error) throw error;

      router.push("/proposals");
    } catch (error) {
      console.error("Error deleting proposal:", error);
      alert("Failed to delete proposal");
      setIsDeleting(false);
    }
  };

  const handleDownloadPDF = async () => {
    try {
      const response = await fetch(`/api/proposals/${proposal.id}/pdf`);
      if (!response.ok) throw new Error("Failed to generate PDF");

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `proposal-${proposal.id}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error("Error downloading PDF:", error);
      alert("Failed to download PDF");
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="space-y-1">
          <div className="flex items-center gap-3">
            <h1 className="text-3xl font-bold tracking-tight">{proposal.title}</h1>
            <Badge
              variant="secondary"
              className={`${statusConfig[proposal.status as keyof typeof statusConfig]?.color} text-white`}
            >
              <StatusIcon className="mr-1 h-3 w-3" />
              {statusConfig[proposal.status as keyof typeof statusConfig]?.label}
            </Badge>
          </div>
          <p className="text-muted-foreground">Created {format(new Date(proposal.created_at), "PPP")}</p>
        </div>

        <div className="flex gap-2">
          <Button variant="outline" onClick={() => router.push(`/proposals/${proposal.id}/preview`)}>
            <Eye className="mr-2 h-4 w-4" />
            Preview
          </Button>

          {proposal.status === "draft" && (
            <Button onClick={handleSend} disabled={isSending}>
              <Send className="mr-2 h-4 w-4" />
              {isSending ? "Sending..." : "Send to Client"}
            </Button>
          )}

          <Button variant="outline" onClick={handleDownloadPDF}>
            <Download className="mr-2 h-4 w-4" />
            PDF
          </Button>

          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="outline" size="icon">
                <MoreVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => router.push(`/proposals/${proposal.id}/edit`)}>
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </DropdownMenuItem>
              <DropdownMenuItem onClick={handleDelete} disabled={isDeleting}>
                <Trash2 className="mr-2 h-4 w-4" />
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>

      {/* Content */}
      <Tabs defaultValue="details" className="space-y-6">
        <TabsList>
          <TabsTrigger value="details">Details</TabsTrigger>
          <TabsTrigger value="items">Line Items</TabsTrigger>
          <TabsTrigger value="terms">Terms</TabsTrigger>
          <TabsTrigger value="activity">Activity</TabsTrigger>
        </TabsList>

        {/* Details Tab */}
        <TabsContent value="details" className="space-y-6">
          <div className="grid gap-6 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Client Information</CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                <div>
                  <p className="text-sm text-muted-foreground">Company</p>
                  <p className="font-medium">{proposal.client?.company_name}</p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Email</p>
                  <p className="font-medium">{proposal.client?.email}</p>
                </div>
                {proposal.client?.phone && (
                  <div>
                    <p className="text-sm text-muted-foreground">Phone</p>
                    <p className="font-medium">{proposal.client.phone}</p>
                  </div>
                )}
                {proposal.client?.address && (
                  <div>
                    <p className="text-sm text-muted-foreground">Address</p>
                    <p className="font-medium">
                      {proposal.client.address}
                      {proposal.client.city && `, ${proposal.client.city}`}
                      {proposal.client.state && `, ${proposal.client.state}`}
                      {proposal.client.zip_code && ` ${proposal.client.zip_code}`}
                    </p>
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Proposal Information</CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                <div>
                  <p className="text-sm text-muted-foreground">Total Amount</p>
                  <p className="text-2xl font-bold">
                    ${Number(proposal.total_amount).toLocaleString()} {proposal.currency}
                  </p>
                </div>
                <Separator />
                <div>
                  <p className="text-sm text-muted-foreground">Valid Until</p>
                  <p className="font-medium">
                    {proposal.valid_until ? format(new Date(proposal.valid_until), "PPP") : "No expiration"}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Created By</p>
                  <p className="font-medium">{proposal.creator?.name}</p>
                </div>
                {proposal.sent_at && (
                  <div>
                    <p className="text-sm text-muted-foreground">Sent At</p>
                    <p className="font-medium">{format(new Date(proposal.sent_at), "PPp")}</p>
                  </div>
                )}
                {proposal.viewed_at && (
                  <div>
                    <p className="text-sm text-muted-foreground">First Viewed</p>
                    <p className="font-medium">{format(new Date(proposal.viewed_at), "PPp")}</p>
                  </div>
                )}
                {proposal.accepted_at && (
                  <div>
                    <p className="text-sm text-muted-foreground">Accepted At</p>
                    <p className="font-medium">{format(new Date(proposal.accepted_at), "PPp")}</p>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>

          {proposal.description && (
            <Card>
              <CardHeader>
                <CardTitle>Description</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="whitespace-pre-wrap">{proposal.description}</p>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* Line Items Tab */}
        <TabsContent value="items">
          <Card>
            <CardHeader>
              <CardTitle>Line Items</CardTitle>
              <CardDescription>Services and deliverables included in this proposal</CardDescription>
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

              <Separator className="my-4" />

              <div className="flex justify-end">
                <div className="space-y-2 w-64">
                  <div className="flex justify-between text-xl font-bold">
                    <span>Total:</span>
                    <span>
                      ${Number(proposal.total_amount).toLocaleString()} {proposal.currency}
                    </span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Terms Tab */}
        <TabsContent value="terms">
          <Card>
            <CardHeader>
              <CardTitle>Terms & Conditions</CardTitle>
            </CardHeader>
            <CardContent>
              {proposal.terms ? (
                <p className="whitespace-pre-wrap">{proposal.terms}</p>
              ) : (
                <p className="text-muted-foreground">No terms specified</p>
              )}
            </CardContent>
          </Card>

          {proposal.metadata?.notes && (
            <Card>
              <CardHeader>
                <CardTitle>Client Notes</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="whitespace-pre-wrap">{proposal.metadata.notes}</p>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* Activity Tab */}
        <TabsContent value="activity">
          <Card>
            <CardHeader>
              <CardTitle>Activity Log</CardTitle>
              <CardDescription>Track proposal views and interactions</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {proposal.views && proposal.views.length > 0 ? (
                  proposal.views.map((view: any, index: number) => (
                    <div key={index} className="flex items-start gap-4">
                      <Eye className="h-5 w-5 text-muted-foreground mt-0.5" />
                      <div>
                        <p className="font-medium">Proposal Viewed</p>
                        <p className="text-sm text-muted-foreground">
                          {format(new Date(view.viewed_at), "PPp")}
                          {view.viewed_by_ip && ` from ${view.viewed_by_ip}`}
                        </p>
                      </div>
                    </div>
                  ))
                ) : (
                  <p className="text-muted-foreground">No activity yet</p>
                )}
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
