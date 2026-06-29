"use client";

import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Calendar, Edit, Trash2, Power, PowerOff } from "lucide-react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from "sonner";

interface ReportScheduleTemplate {
  id: string;
  name: string;
  report_type: string;
}

interface ReportSchedule {
  id: string;
  name: string;
  template: ReportScheduleTemplate | null;
  frequency: string;
  recipients: Array<{ email: string; name?: string; type: string }> | null;
  is_active: boolean;
  next_run_at: string | null;
  last_run_at: string | null;
}

interface ScheduledReportsListProps {
  schedules: ReportSchedule[];
}

const frequencyColors: Record<string, string> = {
  daily: "bg-blue-500",
  weekly: "bg-green-500",
  monthly: "bg-purple-500",
  quarterly: "bg-orange-500",
};

export function ScheduledReportsList({ schedules: initialSchedules }: ScheduledReportsListProps) {
  const [schedules, setSchedules] = useState(initialSchedules);
  const [toggling, setToggling] = useState<string | null>(null);
  const [deleting, setDeleting] = useState<string | null>(null);

  const handleToggle = async (id: string, currentActive: boolean) => {
    setToggling(id);
    try {
      const res = await fetch(`/api/reports/schedules/${id}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ isActive: !currentActive }),
      });
      if (!res.ok) throw new Error("Failed to update schedule");
      setSchedules((prev) =>
        prev.map((s) => (s.id === id ? { ...s, is_active: !currentActive } : s)),
      );
      toast.success(`Schedule ${!currentActive ? "enabled" : "disabled"}`);
    } catch {
      toast.error("Failed to update schedule");
    } finally {
      setToggling(null);
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm("Delete this schedule?")) return;
    setDeleting(id);
    try {
      const res = await fetch(`/api/reports/schedules/${id}`, { method: "DELETE" });
      if (!res.ok) throw new Error("Failed to delete schedule");
      setSchedules((prev) => prev.filter((s) => s.id !== id));
      toast.success("Schedule deleted");
    } catch {
      toast.error("Failed to delete schedule");
    } finally {
      setDeleting(null);
    }
  };

  return (
    <div className="space-y-4">
      {schedules.length === 0 ? (
        <div className="text-center py-12">
          <Calendar className="mx-auto h-12 w-12 text-muted-foreground" />
          <p className="mt-4 text-muted-foreground">No scheduled reports found.</p>
          <p className="text-sm text-muted-foreground">Schedule your first report to get started.</p>
        </div>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Schedule Name</TableHead>
              <TableHead>Template</TableHead>
              <TableHead>Frequency</TableHead>
              <TableHead>Recipients</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Next Run</TableHead>
              <TableHead>Last Run</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {schedules.map((schedule) => {
              const recipientCount = Array.isArray(schedule.recipients) ? schedule.recipients.length : 0;
              return (
                <TableRow key={schedule.id}>
                  <TableCell className="font-medium">{schedule.name}</TableCell>
                  <TableCell>{schedule.template?.name ?? "—"}</TableCell>
                  <TableCell>
                    <Badge className={frequencyColors[schedule.frequency] ?? "bg-gray-500"}>
                      {schedule.frequency}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="text-sm">
                      {recipientCount} recipient{recipientCount !== 1 ? "s" : ""}
                    </div>
                  </TableCell>
                  <TableCell>
                    {schedule.is_active ? (
                      <Badge className="bg-green-500">
                        <Power className="mr-1 h-3 w-3" />
                        Active
                      </Badge>
                    ) : (
                      <Badge variant="secondary">
                        <PowerOff className="mr-1 h-3 w-3" />
                        Inactive
                      </Badge>
                    )}
                  </TableCell>
                  <TableCell>
                    {schedule.next_run_at ? new Date(schedule.next_run_at).toLocaleString() : "N/A"}
                  </TableCell>
                  <TableCell>
                    {schedule.last_run_at ? new Date(schedule.last_run_at).toLocaleString() : "Never"}
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-2">
                      <Button
                        variant="ghost"
                        size="icon"
                        title={schedule.is_active ? "Disable" : "Enable"}
                        disabled={toggling === schedule.id}
                        onClick={() => handleToggle(schedule.id, schedule.is_active)}
                      >
                        {schedule.is_active ? (
                          <PowerOff className="h-4 w-4" />
                        ) : (
                          <Power className="h-4 w-4" />
                        )}
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        title="Delete"
                        disabled={deleting === schedule.id}
                        onClick={() => handleDelete(schedule.id)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody>
        </Table>
      )}
    </div>
  );
}
