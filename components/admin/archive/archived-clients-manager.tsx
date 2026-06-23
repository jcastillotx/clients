"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Archive, RotateCcw } from "lucide-react";
import { format } from "date-fns";
import { toast } from "sonner";
import { fetchApi } from "@/lib/api/client";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

export interface ArchivedClientRow {
  id: string;
  company_name: string;
  email: string;
  status: string;
  created_at: string;
  deleted_at: string;
}

interface ArchivedClientsManagerProps {
  clients: ArchivedClientRow[];
}

export function ArchivedClientsManager({
  clients,
}: ArchivedClientsManagerProps) {
  const router = useRouter();
  const [restoreClient, setRestoreClient] = useState<ArchivedClientRow | null>(
    null,
  );
  const [isRestoring, setIsRestoring] = useState(false);

  const handleRestore = async () => {
    if (!restoreClient) {
      return;
    }

    setIsRestoring(true);
    try {
      const data = await fetchApi<{
        restoredRecords?: Array<{ table: string; updated: number }>;
      }>(`/api/clients/${restoreClient.id}/archive`, { method: "DELETE" }, {
        fallbackMessage: "Failed to restore client",
      });

      const restoredCount =
        data.restoredRecords?.reduce((sum, row) => sum + row.updated, 0) ?? 0;

      toast.success("Client restored", {
        description: `${restoreClient.company_name} and ${restoredCount} associated records were restored.`,
      });
      setRestoreClient(null);
      router.refresh();
    } catch (error) {
      toast.error("Could not restore client", {
        description: error instanceof Error ? error.message : "Unknown error",
      });
    } finally {
      setIsRestoring(false);
    }
  };

  return (
    <>
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between gap-4">
            <CardTitle>Archived Clients</CardTitle>
            <Badge variant="secondary">{clients.length} archived</Badge>
          </div>
        </CardHeader>
        <CardContent>
          {clients.length === 0 ? (
            <div className="flex flex-col items-center justify-center gap-3 py-12 text-center">
              <Archive className="h-10 w-10 text-muted-foreground" />
              <div>
                <p className="font-medium">No archived clients</p>
                <p className="text-sm text-muted-foreground">
                  Archived clients will appear here and can be restored later.
                </p>
              </div>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Client</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Created</TableHead>
                  <TableHead>Archived</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {clients.map((client) => (
                  <TableRow key={client.id}>
                    <TableCell>
                      <div>
                        <p className="font-medium">{client.company_name}</p>
                        <p className="text-sm text-muted-foreground">
                          {client.email}
                        </p>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="secondary">{client.status}</Badge>
                    </TableCell>
                    <TableCell>
                      {format(new Date(client.created_at), "MMM d, yyyy")}
                    </TableCell>
                    <TableCell>
                      {format(new Date(client.deleted_at), "MMM d, yyyy")}
                    </TableCell>
                    <TableCell className="text-right">
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setRestoreClient(client)}
                      >
                        <RotateCcw className="mr-2 h-4 w-4" />
                        Restore
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <ConfirmDialog
        open={Boolean(restoreClient)}
        onOpenChange={(open) => {
          if (!open) {
            setRestoreClient(null);
          }
        }}
        title="Restore client?"
        description={
          restoreClient
            ? `Restore ${restoreClient.company_name} and records archived with it back into active workflows.`
            : "Restore this client."
        }
        confirmLabel="Restore client"
        loading={isRestoring}
        onConfirm={() => void handleRestore()}
      />
    </>
  );
}
