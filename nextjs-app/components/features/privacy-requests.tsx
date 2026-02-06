"use client";

import { useState, useEffect } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Shield, Plus, Download, CheckCircle, Clock, XCircle } from "lucide-react";
import { toast } from "sonner";

interface PrivacyRequest {
  id: string;
  requestType: "export" | "delete" | "rectify" | "restrict" | "object";
  status: "pending" | "processing" | "completed" | "rejected";
  requestedAt: string;
  completedAt: string | null;
  dataExportUrl: string | null;
  notes: string | null;
}

interface PrivacyRequestsProps {
  clientId: string;
}

export function PrivacyRequests({ clientId }: PrivacyRequestsProps) {
  const [requests, setRequests] = useState<PrivacyRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [formData, setFormData] = useState({
    requestType: "",
    notes: "",
  });

  useEffect(() => {
    fetchRequests();
  }, [clientId]);

  const fetchRequests = async () => {
    try {
      setLoading(true);
      const response = await fetch(`/api/privacy-requests?clientId=${clientId}`);

      if (!response.ok) throw new Error("Failed to fetch requests");

      const data = await response.json();
      setRequests(data);
    } catch (error) {
      toast.error("Failed to load privacy requests");
    } finally {
      setLoading(false);
    }
  };

  const handleCreate = async () => {
    try {
      const response = await fetch("/api/privacy-requests", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          clientId,
          requestType: formData.requestType,
          notes: formData.notes,
        }),
      });

      if (!response.ok) throw new Error("Failed to create request");

      toast.success("Privacy request submitted");
      setDialogOpen(false);
      fetchRequests();
      setFormData({ requestType: "", notes: "" });
    } catch (error) {
      toast.error("Failed to create request");
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case "completed":
        return <CheckCircle className="h-5 w-5 text-green-600" />;
      case "processing":
        return <Clock className="h-5 w-5 text-blue-600" />;
      case "rejected":
        return <XCircle className="h-5 w-5 text-red-600" />;
      default:
        return <Clock className="h-5 w-5 text-gray-600" />;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "completed":
        return <Badge className="bg-green-600">Completed</Badge>;
      case "processing":
        return <Badge className="bg-blue-600">Processing</Badge>;
      case "rejected":
        return <Badge variant="destructive">Rejected</Badge>;
      default:
        return <Badge variant="secondary">Pending</Badge>;
    }
  };

  const getRequestTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
      export: "Data Export",
      delete: "Data Deletion",
      rectify: "Data Rectification",
      restrict: "Restrict Processing",
      object: "Object to Processing",
    };
    return labels[type] || type;
  };

  if (loading) {
    return <div className="text-center p-8">Loading privacy requests...</div>;
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold">Data Privacy Requests</h2>
          <p className="text-gray-500">GDPR & CCPA compliance management</p>
        </div>
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              New Request
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Submit Privacy Request</DialogTitle>
              <DialogDescription>Submit a new data privacy request (GDPR/CCPA)</DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              <div>
                <Label>Request Type</Label>
                <Select
                  value={formData.requestType}
                  onValueChange={(value) => setFormData({ ...formData, requestType: value })}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select request type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="export">Data Export</SelectItem>
                    <SelectItem value="delete">Data Deletion</SelectItem>
                    <SelectItem value="rectify">Data Rectification</SelectItem>
                    <SelectItem value="restrict">Restrict Processing</SelectItem>
                    <SelectItem value="object">Object to Processing</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Notes (Optional)</Label>
                <Textarea
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  placeholder="Additional details about your request..."
                  rows={4}
                />
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setDialogOpen(false)}>
                Cancel
              </Button>
              <Button onClick={handleCreate}>Submit Request</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <div className="space-y-4">
        {requests.map((request) => (
          <Card key={request.id}>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div className="flex items-center gap-3">
                  {getStatusIcon(request.status)}
                  <div>
                    <CardTitle className="text-lg">{getRequestTypeLabel(request.requestType)}</CardTitle>
                    <CardDescription>Requested: {new Date(request.requestedAt).toLocaleString()}</CardDescription>
                  </div>
                </div>
                {getStatusBadge(request.status)}
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-2 text-sm">
                {request.notes && (
                  <div>
                    <span className="text-gray-500">Notes:</span> <span>{request.notes}</span>
                  </div>
                )}
                {request.completedAt && (
                  <div>
                    <span className="text-gray-500">Completed:</span>{" "}
                    <span>{new Date(request.completedAt).toLocaleString()}</span>
                  </div>
                )}
                {request.dataExportUrl && (
                  <div className="pt-2">
                    <Button size="sm" variant="outline" onClick={() => window.open(request.dataExportUrl!, "_blank")}>
                      <Download className="h-4 w-4 mr-2" />
                      Download Data Export
                    </Button>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {requests.length === 0 && (
        <div className="text-center p-12 border-2 border-dashed rounded-lg">
          <Shield className="h-12 w-12 mx-auto mb-4 text-gray-400" />
          <h3 className="text-lg font-semibold mb-2">No Privacy Requests</h3>
          <p className="text-gray-500 mb-4">Submit requests for data export, deletion, or rectification</p>
          <Button onClick={() => setDialogOpen(true)}>
            <Plus className="h-4 w-4 mr-2" />
            Submit First Request
          </Button>
        </div>
      )}
    </div>
  );
}
