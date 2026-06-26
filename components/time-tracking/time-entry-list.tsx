"use client";

import { useState, useEffect } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Pencil, Trash2, Filter } from "lucide-react";
import { toast } from "sonner";
import { format } from "date-fns";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { fetchApi } from "@/lib/api/client";

interface TimeEntryListProps {
  refreshTrigger?: number;
  projectId?: string | null;
}

/** Shape returned by GET /api/time-tracking (subset used by this list). */
interface TimeEntryRow {
  id: string;
  startedAt?: string | null;
  description?: string | null;
  durationMinutes?: number | null;
  hourlyRate?: string | null;
  totalAmount?: string | null;
  isBillable?: boolean;
  status?: string;
  lockedAt?: string | null;
  client?: { name?: string | null };
  request?: { title?: string | null };
}

export function TimeEntryList({ refreshTrigger, projectId }: TimeEntryListProps) {
  const [entries, setEntries] = useState<TimeEntryRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [pendingDeleteId, setPendingDeleteId] = useState<string | null>(null);

  useEffect(() => {
    fetchEntries();
    // Only refetch when refreshTrigger/project changes; date/status filters use Apply.
    // eslint-disable-next-line react-hooks/exhaustive-deps -- intentional
  }, [refreshTrigger, projectId]);

  const fetchEntries = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (startDate) params.append("startDate", startDate);
      if (endDate) params.append("endDate", endDate);
      if (statusFilter) params.append("status", statusFilter);
      if (projectId) params.append("projectId", projectId);

      const rows = await fetchApi<TimeEntryRow[]>(
        `/api/time-tracking?${params}`,
        undefined,
        { fallbackMessage: "Failed to fetch time entries" },
      );
      setEntries(Array.isArray(rows) ? rows : []);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Failed to fetch time entries");
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteEntry = (id: string) => {
    setPendingDeleteId(id);
    setConfirmOpen(true);
  };

  const doDeleteEntry = async () => {
    if (!pendingDeleteId) return;
    try {
      await fetchApi(`/api/time-tracking?id=${pendingDeleteId}`, {
        method: "DELETE",
      }, { fallbackMessage: "Failed to delete entry" });

      toast.success("Entry deleted");
      fetchEntries();
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : "Failed to delete entry";
      toast.error(message);
    } finally {
      setPendingDeleteId(null);
    }
  };

  const formatDuration = (minutes: number | null): string => {
    if (!minutes) return "0h 0m";
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
      pending: "secondary",
      approved: "default",
      billed: "outline",
      rejected: "destructive",
    };
    return <Badge variant={variants[status] || "default"}>{status.charAt(0).toUpperCase() + status.slice(1)}</Badge>;
  };

  const getTotalHours = (): string => {
    const list = Array.isArray(entries) ? entries : [];
    const totalMinutes = list.reduce((sum, entry) => sum + (entry.durationMinutes || 0), 0);
    return formatDuration(totalMinutes);
  };

  const getTotalAmount = (): string => {
    const list = Array.isArray(entries) ? entries : [];
    const total = list.reduce((sum, entry) => sum + parseFloat(entry.totalAmount || "0"), 0);
    return `$${total.toFixed(2)}`;
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center justify-between">
          <span>Time Entries</span>
          <div className="text-sm font-normal text-muted-foreground">
            Total: {getTotalHours()} | {getTotalAmount()}
          </div>
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Filters */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="space-y-2">
            <Label htmlFor="startDate">Start Date</Label>
            <Input id="startDate" type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)} />
          </div>

          <div className="space-y-2">
            <Label htmlFor="endDate">End Date</Label>
            <Input id="endDate" type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)} />
          </div>

          <div className="space-y-2">
            <Label htmlFor="status">Status</Label>
            <select
              id="status"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="w-full h-10 px-3 rounded-md border border-input bg-background"
            >
              <option value="">All</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="billed">Billed</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>

          <div className="flex items-end gap-2">
            <Button onClick={fetchEntries} className="gap-2">
              <Filter className="h-4 w-4" />
              Apply Filters
            </Button>
          </div>
        </div>

        {/* Entries Table */}
        <div className="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Client/Request</TableHead>
                <TableHead>Duration</TableHead>
                <TableHead>Rate</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Billable</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {loading ? (
                <TableRow>
                  <TableCell colSpan={9} className="text-center py-8">
                    Loading entries...
                  </TableCell>
                </TableRow>
              ) : entries.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={9} className="text-center py-8">
                    No time entries found
                  </TableCell>
                </TableRow>
              ) : (
                entries.map((entry) => (
                  <TableRow key={entry.id}>
                    <TableCell>{entry.startedAt ? format(new Date(entry.startedAt), "MMM dd, yyyy") : "N/A"}</TableCell>
                    <TableCell className="max-w-xs truncate">{entry.description || "No description"}</TableCell>
                    <TableCell>
                      <div className="text-sm">
                        {entry.client?.name && <div className="font-medium">{entry.client.name}</div>}
                        {entry.request?.title && <div className="text-muted-foreground">{entry.request.title}</div>}
                      </div>
                    </TableCell>
                    <TableCell>{formatDuration(entry.durationMinutes ?? null)}</TableCell>
                    <TableCell>{entry.hourlyRate ? `$${parseFloat(entry.hourlyRate).toFixed(2)}` : "-"}</TableCell>
                    <TableCell>{entry.totalAmount ? `$${parseFloat(entry.totalAmount).toFixed(2)}` : "-"}</TableCell>
                    <TableCell>
                      {entry.isBillable ? <Badge variant="default">Yes</Badge> : <Badge variant="secondary">No</Badge>}
                    </TableCell>
                    <TableCell>{getStatusBadge(entry.status ?? "pending")}</TableCell>
                    <TableCell>
                      <div className="flex gap-2">
                        {!entry.lockedAt && (
                          <>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => toast.info("Edit functionality coming soon")}
                            >
                              <Pencil className="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="icon" onClick={() => handleDeleteEntry(entry.id)}>
                              <Trash2 className="h-4 w-4 text-destructive" />
                            </Button>
                          </>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>
      </CardContent>
      <ConfirmDialog
        open={confirmOpen}
        onOpenChange={setConfirmOpen}
        title="Delete time entry?"
        description="This will permanently delete this time entry."
        confirmLabel="Delete"
        onConfirm={doDeleteEntry}
      />
    </Card>
  );
}
