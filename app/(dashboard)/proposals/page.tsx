"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import {
  FileText,
  CheckCircle,
  XCircle,
  Clock,
  Eye,
  DollarSign,
  ArrowRight,
  MessageSquare,
  AlertCircle,
  Send,
  Ban,
} from "lucide-react";
import { formatDistanceToNow } from "date-fns";

interface ServiceTemplate {
  id: string;
  name: string;
  description: string | null;
  category: string | null;
  currency: string;
  totalAmount: string;
  lineItems: Array<{ description: string; quantity: number; unitPrice: number; amount: number }>;
  metadata: { features?: string[]; deliverables?: string[]; estimatedTimeline?: string } | null;
}

interface Proposal {
  id: string;
  title: string;
  description: string | null;
  status: string;
  total_amount: string;
  currency: string;
  valid_until: string | null;
  created_at: string;
  client_feedback: string | null;
  line_items: Array<{ description: string; quantity: number; unitPrice: number; amount: number }>;
  metadata: { isCustomRequest?: boolean; notes?: string } | null;
  client: { id: string; company_name: string } | null;
}

const statusConfig: Record<string, { label: string; color: string; icon: typeof FileText }> = {
  draft: { label: "Pending Review", color: "bg-gray-500", icon: FileText },
  sent: { label: "Ready for Review", color: "bg-blue-500", icon: Clock },
  viewed: { label: "Under Review", color: "bg-yellow-500", icon: Eye },
  accepted: { label: "Accepted", color: "bg-green-500", icon: CheckCircle },
  rejected: { label: "Rejected", color: "bg-red-500", icon: XCircle },
  cancelled: { label: "Cancelled", color: "bg-gray-400", icon: Ban },
  expired: { label: "Expired", color: "bg-gray-400", icon: Clock },
};

export default function ProposalsPage() {
  const router = useRouter();
  const [proposals, setProposals] = useState<Proposal[]>([]);
  const [availableServices, setAvailableServices] = useState<ServiceTemplate[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [clientId, setClientId] = useState<string | null>(null);
  const [statusFilter, setStatusFilter] = useState("all");

  // Request service dialog
  const [requestTemplate, setRequestTemplate] = useState<ServiceTemplate | null>(null);
  const [requesting, setRequesting] = useState(false);

  // Custom request dialog
  const [customDialogOpen, setCustomDialogOpen] = useState(false);
  const [customTitle, setCustomTitle] = useState("");
  const [customDescription, setCustomDescription] = useState("");

  // Feedback dialog
  const [feedbackProposalId, setFeedbackProposalId] = useState<string | null>(null);
  const [feedbackText, setFeedbackText] = useState("");
  const [sendingFeedback, setSendingFeedback] = useState(false);

  // Cancel dialog
  const [cancelProposalId, setCancelProposalId] = useState<string | null>(null);

  // Accept dialog
  const [acceptProposalId, setAcceptProposalId] = useState<string | null>(null);

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      setLoading(true);

      const [proposalsRes, templatesRes, clientsRes] = await Promise.all([
        fetch("/api/proposals"),
        fetch("/api/admin/service-templates"),
        fetch("/api/clients"),
      ]);

      const proposalsData = await proposalsRes.json();
      if (Array.isArray(proposalsData)) {
        setProposals(proposalsData);
      }

      const templatesData = await templatesRes.json();
      if (templatesRes.ok && templatesData.success) {
        setAvailableServices((templatesData.data ?? []).filter((t: any) => t.isActive));
      }

      const clientsData = await clientsRes.json();
      const clients = Array.isArray(clientsData)
        ? clientsData
        : Array.isArray(clientsData?.data)
          ? clientsData.data
          : clientsData?.client
            ? [clientsData.client]
            : [];
      if (clients.length > 0) {
        setClientId(clients[0].id);
      }

      setError(null);
    } catch {
      setError("Unable to load proposals.");
    } finally {
      setLoading(false);
    }
  };

  const handleRequestService = async () => {
    if (!requestTemplate || !clientId) return;
    try {
      setRequesting(true);
      const response = await fetch("/api/proposals/request", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ clientId, serviceTemplateId: requestTemplate.id }),
      });
      const data = await response.json();
      if (!data.success) throw new Error(data.error);
      setRequestTemplate(null);
      fetchData();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to request service");
    } finally {
      setRequesting(false);
    }
  };

  const handleCustomRequest = async () => {
    if (!clientId || !customTitle.trim() || !customDescription.trim()) {
      setError("Please provide a title and description for your request");
      return;
    }
    try {
      setRequesting(true);
      const response = await fetch("/api/proposals/request", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ clientId, title: customTitle, description: customDescription }),
      });
      const data = await response.json();
      if (!data.success) throw new Error(data.error);
      setCustomDialogOpen(false);
      setCustomTitle("");
      setCustomDescription("");
      fetchData();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to submit request");
    } finally {
      setRequesting(false);
    }
  };

  const handleSendFeedback = async () => {
    if (!feedbackProposalId || !feedbackText.trim()) return;
    try {
      setSendingFeedback(true);
      const response = await fetch(`/api/proposals/${feedbackProposalId}/feedback`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ feedback: feedbackText }),
      });
      const data = await response.json();
      if (!data.success) throw new Error(data.error);
      setFeedbackProposalId(null);
      setFeedbackText("");
      fetchData();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to send feedback");
    } finally {
      setSendingFeedback(false);
    }
  };

  const handleCancel = async () => {
    if (!cancelProposalId) return;
    try {
      const response = await fetch(`/api/proposals/${cancelProposalId}/cancel`, { method: "POST" });
      const data = await response.json();
      if (!data.success) throw new Error(data.error);
      setCancelProposalId(null);
      fetchData();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to cancel proposal");
    }
  };

  const handleAccept = async () => {
    if (!acceptProposalId) return;
    try {
      const response = await fetch(`/api/proposals/${acceptProposalId}/sign`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "accept", signerName: "Client", signerEmail: "" }),
      });
      const data = await response.json();
      if (!data.success && !response.ok) throw new Error(data.error || "Failed to accept");
      setAcceptProposalId(null);
      fetchData();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to accept proposal");
    }
  };

  const filteredProposals = proposals.filter((p) => {
    if (statusFilter === "all") return true;
    return p.status === statusFilter;
  });

  if (loading) {
    return (
      <div className="container mx-auto py-8 space-y-8">
        <div>
          <h1 className="text-3xl font-bold">Proposals</h1>
          <p className="text-muted-foreground mt-1">Request services and review proposals</p>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {[...Array(3)].map((_, i) => (
            <div key={i} className="h-48 bg-muted animate-pulse rounded-lg" />
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto py-8 space-y-8">
      <div>
        <h1 className="text-3xl font-bold">Proposals</h1>
        <p className="text-muted-foreground mt-1">Request services and review proposals</p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <Tabs defaultValue="proposals">
        <TabsList>
          <TabsTrigger value="proposals">My Proposals</TabsTrigger>
          <TabsTrigger value="services">Available Services</TabsTrigger>
        </TabsList>

        {/* My Proposals Tab */}
        <TabsContent value="proposals" className="space-y-4">
          <div className="flex items-center justify-between">
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[200px]">
                <SelectValue placeholder="Filter by status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Proposals</SelectItem>
                <SelectItem value="draft">Pending Review</SelectItem>
                <SelectItem value="sent">Ready for Review</SelectItem>
                <SelectItem value="accepted">Accepted</SelectItem>
                <SelectItem value="rejected">Rejected</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
              </SelectContent>
            </Select>
            <Button variant="outline" onClick={() => setCustomDialogOpen(true)}>
              <Send className="mr-2 h-4 w-4" />
              Request Custom Service
            </Button>
          </div>

          {filteredProposals.length === 0 ? (
            <div className="text-center py-12">
              <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
              <h3 className="mt-4 text-lg font-semibold">No proposals yet</h3>
              <p className="text-muted-foreground mt-1">
                Browse available services or submit a custom request to get started.
              </p>
            </div>
          ) : (
            <div className="space-y-4">
              {filteredProposals.map((proposal) => {
                const config = statusConfig[proposal.status] || statusConfig.draft;
                const StatusIcon = config.icon;
                const canAct = proposal.status === "sent" || proposal.status === "viewed";
                const canCancel = !["accepted", "cancelled", "expired"].includes(proposal.status);
                const amount = parseFloat(proposal.total_amount);

                return (
                  <Card key={proposal.id}>
                    <CardContent className="p-6">
                      <div className="flex items-start justify-between gap-4">
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-3 flex-wrap">
                            <h3 className="text-lg font-semibold truncate">{proposal.title}</h3>
                            <Badge className={`${config.color} text-white flex-shrink-0`}>
                              <StatusIcon className="mr-1 h-3 w-3" />
                              {config.label}
                            </Badge>
                            {proposal.metadata?.isCustomRequest && (
                              <Badge variant="outline" className="flex-shrink-0">Custom Request</Badge>
                            )}
                          </div>
                          {proposal.description && (
                            <p className="text-muted-foreground mt-1 line-clamp-2">{proposal.description}</p>
                          )}
                          <div className="flex items-center gap-4 mt-2 text-sm text-muted-foreground flex-wrap">
                            {amount > 0 && (
                              <span className="flex items-center gap-1">
                                <DollarSign className="h-3.5 w-3.5" />
                                ${amount.toLocaleString()} {proposal.currency}
                              </span>
                            )}
                            <span>
                              {formatDistanceToNow(new Date(proposal.created_at), { addSuffix: true })}
                            </span>
                            {proposal.valid_until && (
                              <span>
                                Valid until {new Date(proposal.valid_until).toLocaleDateString()}
                              </span>
                            )}
                          </div>
                          {proposal.client_feedback && (
                            <div className="mt-3 bg-muted rounded-md p-3">
                              <p className="text-sm font-medium">Your feedback:</p>
                              <p className="text-sm text-muted-foreground">{proposal.client_feedback}</p>
                            </div>
                          )}
                        </div>

                        <div className="flex gap-2 flex-shrink-0 flex-wrap justify-end">
                          {proposal.line_items?.length > 0 && (
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => router.push(`/proposals/${proposal.id}/preview`)}
                            >
                              <Eye className="mr-1.5 h-3.5 w-3.5" />
                              View
                            </Button>
                          )}
                          {canAct && (
                            <>
                              <Button size="sm" onClick={() => setAcceptProposalId(proposal.id)}>
                                <CheckCircle className="mr-1.5 h-3.5 w-3.5" />
                                Accept
                              </Button>
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                  setFeedbackProposalId(proposal.id);
                                  setFeedbackText(proposal.client_feedback || "");
                                }}
                              >
                                <MessageSquare className="mr-1.5 h-3.5 w-3.5" />
                                Feedback
                              </Button>
                            </>
                          )}
                          {canCancel && (
                            <Button
                              variant="outline"
                              size="sm"
                              className="text-destructive hover:text-destructive"
                              onClick={() => setCancelProposalId(proposal.id)}
                            >
                              <Ban className="h-3.5 w-3.5" />
                            </Button>
                          )}
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                );
              })}
            </div>
          )}
        </TabsContent>

        {/* Available Services Tab */}
        <TabsContent value="services" className="space-y-4">
          <div className="flex items-center justify-between">
            <p className="text-muted-foreground">Choose a service to generate a proposal, or request a custom service.</p>
            <Button variant="outline" onClick={() => setCustomDialogOpen(true)}>
              <Send className="mr-2 h-4 w-4" />
              Request Custom Service
            </Button>
          </div>

          {availableServices.length === 0 ? (
            <div className="text-center py-12">
              <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
              <h3 className="mt-4 text-lg font-semibold">No services available</h3>
              <p className="text-muted-foreground mt-1">Check back later or submit a custom request.</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {availableServices.map((service) => (
                <Card key={service.id} className="transition-all hover:shadow-lg">
                  <CardHeader className="pb-3">
                    <div className="flex items-start justify-between">
                      <CardTitle className="text-lg">{service.name}</CardTitle>
                      {service.category && (
                        <Badge variant="outline" className="flex-shrink-0">{service.category}</Badge>
                      )}
                    </div>
                    {service.description && (
                      <CardDescription className="mt-1 line-clamp-3">{service.description}</CardDescription>
                    )}
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="space-y-1">
                      <div className="flex items-center gap-1.5 text-muted-foreground">
                        <DollarSign className="h-3.5 w-3.5" />
                        <span className="text-xs">Starting at</span>
                      </div>
                      <p className="text-2xl font-bold">
                        ${parseFloat(service.totalAmount).toLocaleString()}
                      </p>
                    </div>

                    {service.lineItems.length > 0 && (
                      <div className="space-y-1">
                        <p className="text-sm font-medium">Includes:</p>
                        <ul className="text-sm text-muted-foreground space-y-1">
                          {service.lineItems.slice(0, 4).map((item, i) => (
                            <li key={i} className="flex items-center gap-2">
                              <CheckCircle className="h-3.5 w-3.5 text-green-600 flex-shrink-0" />
                              <span className="truncate">{item.description}</span>
                            </li>
                          ))}
                          {service.lineItems.length > 4 && (
                            <li className="text-xs ml-5">+{service.lineItems.length - 4} more items</li>
                          )}
                        </ul>
                      </div>
                    )}

                    <Button className="w-full" onClick={() => setRequestTemplate(service)}>
                      Request This Service
                      <ArrowRight className="ml-2 h-4 w-4" />
                    </Button>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </TabsContent>
      </Tabs>

      {/* Request Service Confirmation */}
      <Dialog open={!!requestTemplate} onOpenChange={() => setRequestTemplate(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Request: {requestTemplate?.name}</DialogTitle>
            <DialogDescription>
              A proposal will be created for your review with the following details.
            </DialogDescription>
          </DialogHeader>
          {requestTemplate && (
            <div className="space-y-4 py-4">
              <div className="bg-muted rounded-lg p-4 space-y-3">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Service</span>
                  <span className="font-medium">{requestTemplate.name}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Price</span>
                  <span className="font-medium">
                    ${parseFloat(requestTemplate.totalAmount).toLocaleString()} {requestTemplate.currency}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Items</span>
                  <span className="font-medium">{requestTemplate.lineItems.length} deliverables</span>
                </div>
              </div>
              <p className="text-sm text-muted-foreground">
                After requesting, you can review the full proposal, accept it, provide feedback, or cancel.
              </p>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setRequestTemplate(null)} disabled={requesting}>Cancel</Button>
            <Button onClick={handleRequestService} disabled={requesting}>
              {requesting ? "Requesting..." : "Confirm Request"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Custom Service Request */}
      <Dialog open={customDialogOpen} onOpenChange={setCustomDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Request Custom Service</DialogTitle>
            <DialogDescription>
              Describe what you need and we will create a proposal for you.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label>Service Title *</Label>
              <Input
                placeholder="e.g., Custom Website Feature"
                value={customTitle}
                onChange={(e) => setCustomTitle(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Description *</Label>
              <Textarea
                placeholder="Describe what you need in detail..."
                rows={5}
                value={customDescription}
                onChange={(e) => setCustomDescription(e.target.value)}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCustomDialogOpen(false)} disabled={requesting}>Cancel</Button>
            <Button onClick={handleCustomRequest} disabled={requesting}>
              {requesting ? "Submitting..." : "Submit Request"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Feedback Dialog */}
      <Dialog open={!!feedbackProposalId} onOpenChange={() => setFeedbackProposalId(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Send Feedback</DialogTitle>
            <DialogDescription>
              Share your feedback, questions, or requested changes for this proposal.
            </DialogDescription>
          </DialogHeader>
          <div className="py-4">
            <Textarea
              placeholder="Your feedback..."
              rows={5}
              value={feedbackText}
              onChange={(e) => setFeedbackText(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setFeedbackProposalId(null)} disabled={sendingFeedback}>Cancel</Button>
            <Button onClick={handleSendFeedback} disabled={sendingFeedback || !feedbackText.trim()}>
              {sendingFeedback ? "Sending..." : "Send Feedback"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Cancel Confirmation */}
      <Dialog open={!!cancelProposalId} onOpenChange={() => setCancelProposalId(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Cancel Proposal</DialogTitle>
            <DialogDescription>Are you sure you want to cancel this proposal?</DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCancelProposalId(null)}>No, Keep It</Button>
            <Button variant="destructive" onClick={handleCancel}>Yes, Cancel</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Accept Confirmation */}
      <Dialog open={!!acceptProposalId} onOpenChange={() => setAcceptProposalId(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Accept Proposal</DialogTitle>
            <DialogDescription>
              By accepting, you agree to the terms and pricing outlined in this proposal.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setAcceptProposalId(null)}>Cancel</Button>
            <Button onClick={handleAccept}>Accept Proposal</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
