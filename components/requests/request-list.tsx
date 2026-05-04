"use client";

import { useCallback, useEffect, useMemo, useState, useTransition } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { format, formatDistanceToNow, isValid, parseISO } from "date-fns";
import { useDebounce } from "@/hooks/use-debounce";
import { ArrowDown, ArrowUp, ArrowUpDown } from "lucide-react";

interface Request {
  id: string;
  title: string;
  status: string;
  priority: string;
  created_at: string;
  due_date?: string;
  client: { company_name: string };
  assigned_user: { name: string; avatar?: string } | null;
}

type SortColumn = "title" | "status" | "priority" | "created_at" | "due_date";

const STATUS_OPTIONS = [
  { value: "pending", label: "Pending" },
  { value: "in_progress", label: "In progress" },
  { value: "completed", label: "Completed" },
  { value: "cancelled", label: "Cancelled" },
  { value: "on_hold", label: "On hold" },
  { value: "awaiting_approval", label: "Awaiting approval" },
  { value: "approved", label: "Approved" },
  { value: "rejected", label: "Rejected" },
] as const;

export function RequestList({
  initialData,
  canBulkDelete,
}: {
  initialData: Request[];
  canBulkDelete: boolean;
}) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [isPending, startTransition] = useTransition();

  const statusFilter = searchParams.get("status") ?? "all";
  const priorityFilter = searchParams.get("priority") ?? "all";
  const sortBy = (searchParams.get("sortBy") ?? "created_at") as SortColumn | string;
  const sortOrder = searchParams.get("sortOrder") === "asc" ? "asc" : "desc";

  const [searchInput, setSearchInput] = useState(searchParams.get("search") ?? "");
  const debouncedSearch = useDebounce(searchInput, 300);

  const [selected, setSelected] = useState<Set<string>>(new Set());
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [bulkError, setBulkError] = useState<string | null>(null);

  useEffect(() => {
    setSearchInput(searchParams.get("search") ?? "");
  }, [searchParams]);

  const mergeParams = useCallback(() => new URLSearchParams(searchParams.toString()), [searchParams]);

  useEffect(() => {
    const next = debouncedSearch.trim();
    const current = searchParams.get("search") ?? "";
    if (next === current) return;

    const params = mergeParams();
    if (next) params.set("search", next);
    else params.delete("search");

    startTransition(() => {
      router.replace(`?${params.toString()}`, { scroll: false });
    });
  }, [debouncedSearch, mergeParams, router, searchParams]);

  const setFilter = useCallback(
    (key: "status" | "priority", value: string, allToken: string) => {
      const params = mergeParams();
      if (!value || value === allToken) params.delete(key);
      else params.set(key, value);
      startTransition(() => router.replace(`?${params.toString()}`, { scroll: false }));
    },
    [mergeParams, router],
  );

  const toggleSort = useCallback(
    (column: SortColumn) => {
      const params = mergeParams();
      const currentCol = params.get("sortBy") || "created_at";
      const currentOrder = params.get("sortOrder") === "asc" ? "asc" : "desc";

      if (currentCol === column) {
        params.set("sortOrder", currentOrder === "asc" ? "desc" : "asc");
      } else {
        params.set("sortBy", column);
        params.set("sortOrder", "asc");
      }
      startTransition(() => router.replace(`?${params.toString()}`, { scroll: false }));
    },
    [mergeParams, router],
  );

  const idsOnPage = useMemo(() => initialData.map((r) => r.id), [initialData]);
  const allSelected = idsOnPage.length > 0 && idsOnPage.every((id) => selected.has(id));
  const someSelected = idsOnPage.some((id) => selected.has(id));

  const toggleAll = useCallback(() => {
    setSelected((prev) => {
      const next = new Set(prev);
      if (allSelected) {
        idsOnPage.forEach((id) => next.delete(id));
      } else {
        idsOnPage.forEach((id) => next.add(id));
      }
      return next;
    });
  }, [allSelected, idsOnPage]);

  const toggleRow = useCallback((id: string, checked: boolean) => {
    setSelected((prev) => {
      const next = new Set(prev);
      if (checked) next.add(id);
      else next.delete(id);
      return next;
    });
  }, []);

  const selectedIds = useMemo(() => Array.from(selected).filter((id) => idsOnPage.includes(id)), [selected, idsOnPage]);

  const runBulk = async (action: "close" | "delete") => {
    setBulkError(null);
    if (selectedIds.length === 0) return;

    const res = await fetch("/api/requests/bulk", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ids: selectedIds, action }),
    });

    const json = (await res.json().catch(() => ({}))) as { error?: string };

    if (!res.ok) {
      setBulkError(json.error || "Something went wrong");
      return;
    }

    setSelected(new Set());
    setDeleteOpen(false);
    startTransition(() => router.refresh());
  };

  const SortButton = ({ column, label }: { column: SortColumn; label: string }) => {
    const active = sortBy === column;
    return (
      <Button
        variant="ghost"
        className="-ml-3 h-8 px-3 data-[state=open]:bg-accent"
        onClick={() => toggleSort(column)}
      >
        <span>{label}</span>
        {active ? (
          sortOrder === "asc" ? (
            <ArrowUp className="ml-2 h-4 w-4" aria-hidden />
          ) : (
            <ArrowDown className="ml-2 h-4 w-4" aria-hidden />
          )
        ) : (
          <ArrowUpDown className="ml-2 h-4 w-4 opacity-40" aria-hidden />
        )}
      </Button>
    );
  };

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <Input
          placeholder="Search requests..."
          value={searchInput}
          onChange={(e) => setSearchInput(e.target.value)}
          className="max-w-sm"
          aria-label="Search requests"
        />
        <Select value={statusFilter} onValueChange={(v) => setFilter("status", v, "all")}>
          <SelectTrigger className="w-full sm:w-[200px]" aria-label="Filter by status">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            {STATUS_OPTIONS.map((s) => (
              <SelectItem key={s.value} value={s.value}>
                {s.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Select value={priorityFilter} onValueChange={(v) => setFilter("priority", v, "all")}>
          <SelectTrigger className="w-full sm:w-[180px]" aria-label="Filter by priority">
            <SelectValue placeholder="Priority" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All priorities</SelectItem>
            <SelectItem value="low">Low</SelectItem>
            <SelectItem value="medium">Medium</SelectItem>
            <SelectItem value="high">High</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {selectedIds.length > 0 && (
        <div
          className="flex flex-col gap-2 rounded-md border bg-muted/40 p-3 sm:flex-row sm:items-center sm:justify-between"
          role="status"
        >
          <span className="text-sm font-medium">
            {selectedIds.length} selected
            {isPending ? " · Updating…" : ""}
          </span>
          <div className="flex flex-wrap gap-2">
            <Button
              size="sm"
              variant="secondary"
              disabled={isPending}
              onClick={() => runBulk("close")}
            >
              Mark completed
            </Button>
            {canBulkDelete && (
              <Button size="sm" variant="destructive" disabled={isPending} onClick={() => setDeleteOpen(true)}>
                Delete
              </Button>
            )}
            <Button size="sm" variant="outline" disabled={isPending} onClick={() => setSelected(new Set())}>
              Clear selection
            </Button>
          </div>
        </div>
      )}

      {bulkError && (
        <p className="text-sm text-destructive" role="alert">
          {bulkError}
        </p>
      )}

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-10">
                <Checkbox
                  checked={allSelected ? true : someSelected ? "indeterminate" : false}
                  onCheckedChange={() => toggleAll()}
                  aria-label="Select all rows"
                />
              </TableHead>
              <TableHead>
                <SortButton column="title" label="Title" />
              </TableHead>
              <TableHead>
                <SortButton column="status" label="Status" />
              </TableHead>
              <TableHead>
                <SortButton column="priority" label="Priority" />
              </TableHead>
              <TableHead>Assigned to</TableHead>
              <TableHead>
                <SortButton column="due_date" label="Due" />
              </TableHead>
              <TableHead>
                <SortButton column="created_at" label="Created" />
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {initialData.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center text-muted-foreground">
                  No requests found
                </TableCell>
              </TableRow>
            ) : (
              initialData.map((request) => (
                <TableRow
                  key={request.id}
                  className="cursor-pointer hover:bg-muted/50"
                  onClick={() => router.push(`/requests/${request.id}`)}
                >
                  <TableCell onClick={(e) => e.stopPropagation()}>
                    <Checkbox
                      checked={selected.has(request.id)}
                      onCheckedChange={(checked) => toggleRow(request.id, checked === true)}
                      aria-label={`Select ${request.title}`}
                    />
                  </TableCell>
                  <TableCell className="font-medium">{request.title}</TableCell>
                  <TableCell>
                    <Badge variant={getStatusVariant(request.status)}>
                      {request.status.replace(/_/g, " ")}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant={getPriorityVariant(request.priority)}>{request.priority}</Badge>
                  </TableCell>
                  <TableCell>{request.assigned_user?.name || "Unassigned"}</TableCell>
                  <TableCell className="text-muted-foreground">{formatDue(request.due_date)}</TableCell>
                  <TableCell className="text-muted-foreground">
                    {formatDistanceToNow(new Date(request.created_at), {
                      addSuffix: true,
                    })}
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
        <DialogContent onClick={(e) => e.stopPropagation()}>
          <DialogHeader>
            <DialogTitle>Delete {selectedIds.length} request(s)?</DialogTitle>
            <DialogDescription>
              This removes the selected requests from the list (soft delete). You can restore them from the database if
              needed.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter className="gap-2 sm:gap-0">
            <Button variant="outline" onClick={() => setDeleteOpen(false)}>
              Cancel
            </Button>
            <Button variant="destructive" disabled={isPending} onClick={() => runBulk("delete")}>
              Delete
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function getStatusVariant(status: string): "default" | "secondary" | "destructive" | "outline" {
  const variants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
    pending: "secondary",
    in_progress: "default",
    completed: "outline",
    cancelled: "destructive",
    on_hold: "secondary",
    awaiting_approval: "secondary",
    approved: "outline",
    rejected: "destructive",
  };
  return variants[status] || "default";
}

function getPriorityVariant(priority: string): "default" | "secondary" | "destructive" {
  const variants: Record<string, "default" | "secondary" | "destructive"> = {
    low: "secondary",
    medium: "default",
    high: "destructive",
  };
  return variants[priority] || "default";
}

function formatDue(iso: string | undefined): string {
  if (!iso) return "—";
  const d = typeof iso === "string" ? parseISO(iso) : new Date(iso);
  if (!isValid(d)) return "—";
  return format(d, "MMM d, yyyy");
}
