"use client";

import { useEffect, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Edit, Trash2, Power, PowerOff, Loader2 } from "lucide-react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from "sonner";

interface AutomationRule {
  id: string;
  name: string;
  trigger: string;
  is_active: boolean;
  run_count: number;
  last_run_at: string | null;
  actions: Array<{ type: string; config: Record<string, unknown> }> | null;
}

export function AutomationRulesList() {
  const [rules, setRules] = useState<AutomationRule[]>([]);
  const [loading, setLoading] = useState(true);
  const [toggling, setToggling] = useState<string | null>(null);
  const [deleting, setDeleting] = useState<string | null>(null);

  useEffect(() => {
    const fetchRules = async () => {
      try {
        const res = await fetch("/api/automation/rules");
        if (!res.ok) throw new Error("Failed to fetch rules");
        const json = await res.json();
        setRules(json.data ?? json.rules ?? []);
      } catch {
        toast.error("Failed to load automation rules");
      } finally {
        setLoading(false);
      }
    };
    fetchRules();
  }, []);

  const handleToggle = async (id: string, currentActive: boolean) => {
    setToggling(id);
    try {
      const res = await fetch(`/api/automation/rules/${id}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ isActive: !currentActive }),
      });
      if (!res.ok) throw new Error("Failed to update rule");
      setRules((prev) => prev.map((r) => (r.id === id ? { ...r, is_active: !currentActive } : r)));
      toast.success(`Rule ${!currentActive ? "enabled" : "disabled"}`);
    } catch {
      toast.error("Failed to update rule");
    } finally {
      setToggling(null);
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm("Delete this automation rule?")) return;
    setDeleting(id);
    try {
      const res = await fetch(`/api/automation/rules/${id}`, { method: "DELETE" });
      if (!res.ok) throw new Error("Failed to delete rule");
      setRules((prev) => prev.filter((r) => r.id !== id));
      toast.success("Rule deleted");
    } catch {
      toast.error("Failed to delete rule");
    } finally {
      setDeleting(null);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

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
              <TableHead className="text-right">Manage</TableHead>
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
                  {rule.is_active ? (
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
                <TableCell>{Array.isArray(rule.actions) ? rule.actions.length : 0}</TableCell>
                <TableCell>{rule.run_count}</TableCell>
                <TableCell>
                  {rule.last_run_at ? new Date(rule.last_run_at).toLocaleDateString() : "Never"}
                </TableCell>
                <TableCell className="text-right">
                  <div className="flex justify-end gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      title={rule.is_active ? "Disable" : "Enable"}
                      disabled={toggling === rule.id}
                      onClick={() => handleToggle(rule.id, rule.is_active)}
                    >
                      {rule.is_active ? <PowerOff className="h-4 w-4" /> : <Power className="h-4 w-4" />}
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      title="Delete"
                      disabled={deleting === rule.id}
                      onClick={() => handleDelete(rule.id)}
                    >
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
