import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Edit, Trash2, Power, PowerOff } from "lucide-react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";

// Mock data - replace with actual API call
const mockRules = [
  {
    id: "1",
    name: "Welcome Email on New Request",
    trigger: "request.created",
    isActive: true,
    runCount: 42,
    lastRunAt: new Date("2024-02-05T10:30:00"),
    actionsCount: 2,
  },
  {
    id: "2",
    name: "Invoice Overdue Reminder",
    trigger: "invoice.overdue",
    isActive: true,
    runCount: 15,
    lastRunAt: new Date("2024-02-04T14:00:00"),
    actionsCount: 1,
  },
  {
    id: "3",
    name: "Project Status Update Notification",
    trigger: "project.status_changed",
    isActive: false,
    runCount: 8,
    lastRunAt: new Date("2024-01-28T09:15:00"),
    actionsCount: 3,
  },
];

export async function AutomationRulesList() {
  // const rules = await fetchAutomationRules();
  const rules = mockRules;

  return (
    <div className="space-y-4">
      {rules.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-muted-foreground">No automation rules found.</p>
          <p className="text-sm text-muted-foreground">Create your first rule to get started.</p>
        </div>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Rule Name</TableHead>
              <TableHead>Trigger</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Actions</TableHead>
              <TableHead>Runs</TableHead>
              <TableHead>Last Run</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {rules.map((rule) => (
              <TableRow key={rule.id}>
                <TableCell className="font-medium">{rule.name}</TableCell>
                <TableCell>
                  <Badge variant="outline">{rule.trigger}</Badge>
                </TableCell>
                <TableCell>
                  {rule.isActive ? (
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
                <TableCell>{rule.actionsCount}</TableCell>
                <TableCell>{rule.runCount}</TableCell>
                <TableCell>{rule.lastRunAt ? new Date(rule.lastRunAt).toLocaleDateString() : "Never"}</TableCell>
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
