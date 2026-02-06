import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Calendar, Edit, Trash2, Power, PowerOff } from "lucide-react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";

// Mock data - replace with actual API call
const mockSchedules = [
  {
    id: "1",
    name: "Weekly Revenue Report",
    templateName: "Monthly Revenue Report",
    frequency: "weekly",
    recipients: ["admin@example.com", "finance@example.com"],
    isActive: true,
    nextRunAt: new Date("2024-02-12T09:00:00"),
    lastRunAt: new Date("2024-02-05T09:00:00"),
  },
  {
    id: "2",
    name: "Monthly SEO Summary",
    templateName: "SEO Performance Dashboard",
    frequency: "monthly",
    recipients: ["marketing@example.com"],
    isActive: true,
    nextRunAt: new Date("2024-03-01T08:00:00"),
    lastRunAt: new Date("2024-02-01T08:00:00"),
  },
  {
    id: "3",
    name: "Daily Project Updates",
    templateName: "Project Status Summary",
    frequency: "daily",
    recipients: ["team@example.com"],
    isActive: false,
    nextRunAt: null,
    lastRunAt: new Date("2024-01-30T10:00:00"),
  },
];

const frequencyColors: Record<string, string> = {
  daily: "bg-blue-500",
  weekly: "bg-green-500",
  monthly: "bg-purple-500",
  quarterly: "bg-orange-500",
};

export async function ScheduledReportsList() {
  // const schedules = await fetchReportSchedules();
  const schedules = mockSchedules;

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
            {schedules.map((schedule) => (
              <TableRow key={schedule.id}>
                <TableCell className="font-medium">{schedule.name}</TableCell>
                <TableCell>{schedule.templateName}</TableCell>
                <TableCell>
                  <Badge className={frequencyColors[schedule.frequency]}>{schedule.frequency}</Badge>
                </TableCell>
                <TableCell>
                  <div className="text-sm">
                    {schedule.recipients.length} recipient
                    {schedule.recipients.length !== 1 ? "s" : ""}
                  </div>
                </TableCell>
                <TableCell>
                  {schedule.isActive ? (
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
                <TableCell>{schedule.nextRunAt ? new Date(schedule.nextRunAt).toLocaleString() : "N/A"}</TableCell>
                <TableCell>{schedule.lastRunAt ? new Date(schedule.lastRunAt).toLocaleString() : "Never"}</TableCell>
                <TableCell className="text-right">
                  <div className="flex justify-end gap-2">
                    <Button variant="ghost" size="icon">
                      <Edit className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon">
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  );
}
