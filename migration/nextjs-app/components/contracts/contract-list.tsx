"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import {
  FileText,
  Search,
  Plus,
  Download,
  Calendar,
  DollarSign,
  CheckCircle2,
  Clock,
  XCircle,
  AlertCircle,
} from "lucide-react";
import { formatDistanceToNow } from "date-fns";
import { CreateContractDialog } from "./create-contract-dialog";

interface Client {
  id: string;
  company_name: string;
}

interface Contract {
  id: string;
  title: string;
  description: string | null;
  contract_number: string;
  client_id: string;
  type: string;
  start_date: string;
  end_date: string | null;
  value: number | null;
  currency: string;
  status: string;
  billing_cycle: string | null;
  auto_renew: boolean;
  notice_required: number | null;
  signed_at: string | null;
  signed_by: string | null;
  created_at: string;
  client: {
    id: string;
    company_name: string;
  } | null;
  document: {
    id: string;
    name: string;
    file_name: string;
    storage_path: string;
  } | null;
}

interface ContractListProps {
  initialContracts: Contract[];
  clients: Client[];
  canCreate: boolean;
  initialClientId?: string;
  initialStatus?: string;
}

const statusConfig = {
  draft: { icon: Clock, color: "text-gray-500", bg: "bg-gray-100", label: "Draft" },
  pending_signature: { icon: AlertCircle, color: "text-yellow-600", bg: "bg-yellow-100", label: "Pending Signature" },
  signed: { icon: CheckCircle2, color: "text-green-600", bg: "bg-green-100", label: "Signed" },
  active: { icon: CheckCircle2, color: "text-blue-600", bg: "bg-blue-100", label: "Active" },
  expired: { icon: XCircle, color: "text-red-600", bg: "bg-red-100", label: "Expired" },
  terminated: { icon: XCircle, color: "text-gray-600", bg: "bg-gray-100", label: "Terminated" },
};

export function ContractList({
  initialContracts,
  clients,
  canCreate,
  initialClientId,
  initialStatus,
}: ContractListProps) {
  const [contracts, setContracts] = useState(initialContracts);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedClient, setSelectedClient] = useState(initialClientId || "all");
  const [selectedStatus, setSelectedStatus] = useState(initialStatus || "all");
  const [createDialogOpen, setCreateDialogOpen] = useState(false);

  // Filter contracts
  const filteredContracts = contracts.filter((contract) => {
    const matchesSearch =
      contract.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      contract.contract_number.toLowerCase().includes(searchQuery.toLowerCase()) ||
      contract.description?.toLowerCase().includes(searchQuery.toLowerCase());

    const matchesClient = selectedClient === "all" || contract.client_id === selectedClient;
    const matchesStatus = selectedStatus === "all" || contract.status === selectedStatus;

    return matchesSearch && matchesClient && matchesStatus;
  });

  const handleCreateSuccess = (newContract: Contract) => {
    setContracts((prev) => [newContract, ...prev]);
    setCreateDialogOpen(false);
  };

  const formatCurrency = (value: number | null, currency: string) => {
    if (!value) return "—";
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: currency || "USD",
    }).format(value);
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Contracts</h1>
          <p className="text-muted-foreground">Manage client contracts and agreements</p>
        </div>
        {canCreate && (
          <Button onClick={() => setCreateDialogOpen(true)}>
            <Plus className="mr-2 h-4 w-4" />
            New Contract
          </Button>
        )}
      </div>

      {/* Filters */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search contracts..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-9"
          />
        </div>
        <Select value={selectedClient} onValueChange={setSelectedClient}>
          <SelectTrigger className="w-full sm:w-[200px]">
            <SelectValue placeholder="All clients" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All clients</SelectItem>
            {clients.map((client) => (
              <SelectItem key={client.id} value={client.id}>
                {client.company_name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Select value={selectedStatus} onValueChange={setSelectedStatus}>
          <SelectTrigger className="w-full sm:w-[200px]">
            <SelectValue placeholder="All statuses" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            <SelectItem value="draft">Draft</SelectItem>
            <SelectItem value="pending_signature">Pending Signature</SelectItem>
            <SelectItem value="signed">Signed</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="expired">Expired</SelectItem>
            <SelectItem value="terminated">Terminated</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Contracts Grid */}
      {filteredContracts.length === 0 ? (
        <div className="flex min-h-[400px] flex-col items-center justify-center rounded-lg border border-dashed">
          <FileText className="mb-4 h-12 w-12 text-muted-foreground" />
          <h3 className="mb-2 text-lg font-semibold">No contracts found</h3>
          <p className="mb-4 text-sm text-muted-foreground">
            {searchQuery || selectedClient !== "all" || selectedStatus !== "all"
              ? "Try adjusting your filters"
              : "Create your first contract to get started"}
          </p>
          {canCreate && !searchQuery && selectedClient === "all" && selectedStatus === "all" && (
            <Button onClick={() => setCreateDialogOpen(true)}>
              <Plus className="mr-2 h-4 w-4" />
              New Contract
            </Button>
          )}
        </div>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {filteredContracts.map((contract) => {
            const config = statusConfig[contract.status as keyof typeof statusConfig] || statusConfig.draft;
            const StatusIcon = config.icon;

            return (
              <Card key={contract.id} className="overflow-hidden">
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <CardTitle className="line-clamp-1">{contract.title}</CardTitle>
                      <p className="mt-1 text-sm text-muted-foreground">{contract.contract_number}</p>
                    </div>
                    <Badge variant="secondary" className={`${config.bg} ${config.color}`}>
                      <StatusIcon className="mr-1 h-3 w-3" />
                      {config.label}
                    </Badge>
                  </div>
                </CardHeader>

                <CardContent className="space-y-3">
                  {contract.description && (
                    <p className="line-clamp-2 text-sm text-muted-foreground">{contract.description}</p>
                  )}

                  <div className="space-y-2 text-sm">
                    <div className="flex items-center text-muted-foreground">
                      <Calendar className="mr-2 h-4 w-4" />
                      <span>
                        {new Date(contract.start_date).toLocaleDateString()}
                        {contract.end_date && ` - ${new Date(contract.end_date).toLocaleDateString()}`}
                      </span>
                    </div>

                    {contract.value && (
                      <div className="flex items-center text-muted-foreground">
                        <DollarSign className="mr-2 h-4 w-4" />
                        <span>{formatCurrency(contract.value, contract.currency)}</span>
                        {contract.billing_cycle && <span className="ml-1">/ {contract.billing_cycle}</span>}
                      </div>
                    )}

                    <div className="flex items-center gap-2">
                      <Badge variant="outline">{contract.type}</Badge>
                      {contract.auto_renew && (
                        <Badge variant="outline" className="text-xs">
                          Auto-renew
                        </Badge>
                      )}
                    </div>
                  </div>

                  {contract.document && (
                    <Button variant="outline" size="sm" className="w-full">
                      <Download className="mr-2 h-4 w-4" />
                      Download Contract
                    </Button>
                  )}
                </CardContent>

                <CardFooter className="border-t bg-muted/50 text-xs text-muted-foreground">
                  <div className="flex w-full items-center justify-between">
                    <span>{contract.client?.company_name}</span>
                    <span title={new Date(contract.created_at).toLocaleString()}>
                      {formatDistanceToNow(new Date(contract.created_at), { addSuffix: true })}
                    </span>
                  </div>
                </CardFooter>
              </Card>
            );
          })}
        </div>
      )}

      {/* Create Dialog */}
      <CreateContractDialog
        open={createDialogOpen}
        onOpenChange={setCreateDialogOpen}
        clients={clients}
        onSuccess={handleCreateSuccess}
        defaultClientId={selectedClient !== "all" ? selectedClient : undefined}
      />
    </div>
  );
}
