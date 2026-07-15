"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Loader2, Plus, Trash2, Users } from "lucide-react";
import { fetchApi } from "@/lib/api/client";
import { toast } from "sonner";

const ROLE_OPTIONS = [
  { value: "account_manager", label: "Account Manager" },
  { value: "designer", label: "Designer" },
  { value: "developer", label: "Developer" },
  { value: "project_manager", label: "Project Manager" },
  { value: "member", label: "Team Member" },
] as const;

interface StaffAssignment {
  id: string;
  role: string;
  user: {
    id: string;
    name: string;
    email: string;
    avatar?: string | null;
  };
}

interface StaffAssignmentsManagerProps {
  clientId: string;
  initialAssignments: StaffAssignment[];
  availableUsers: Array<{
    id: string;
    name: string;
    email: string;
  }>;
}

export function StaffAssignmentsManager({
  clientId,
  initialAssignments,
  availableUsers,
}: StaffAssignmentsManagerProps) {
  const [assignments, setAssignments] =
    useState<StaffAssignment[]>(initialAssignments);
  const [selectedUserId, setSelectedUserId] = useState<string>("");
  const [selectedRole, setSelectedRole] = useState<string>("member");
  const [isAdding, setIsAdding] = useState(false);
  const [removingId, setRemovingId] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState("");

  // Filter out users already assigned
  const assignedUserIds = new Set(assignments.map((a) => a.user.id));
  const unassignedUsers = availableUsers.filter(
    (u) => !assignedUserIds.has(u.id),
  );
  const filteredUsers = unassignedUsers.filter(
    (u) =>
      u.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      u.email.toLowerCase().includes(searchTerm.toLowerCase()),
  );

  const handleAdd = async () => {
    if (!selectedUserId) return;
    setIsAdding(true);

    try {
      await fetchApi(`/api/clients/${clientId}/staff`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ userId: selectedUserId, role: selectedRole }),
      });

      // Find the user details to add to local state
      const addedUser = availableUsers.find((u) => u.id === selectedUserId);
      if (addedUser) {
        setAssignments((prev) => [
          ...prev,
          {
            id: `temp-${Date.now()}`,
            role: selectedRole,
            user: addedUser,
          },
        ]);
      }

      setSelectedUserId("");
      setSelectedRole("member");
      setSearchTerm("");
      toast.success("Staff member assigned");
    } catch (e) {
      toast.error("Failed to assign staff", {
        description: e instanceof Error ? e.message : "Unknown error",
      });
    } finally {
      setIsAdding(false);
    }
  };

  const handleRemove = async (userId: string) => {
    setRemovingId(userId);

    try {
      await fetchApi(`/api/clients/${clientId}/staff`, {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ userId }),
      });

      setAssignments((prev) => prev.filter((a) => a.user.id !== userId));
      toast.success("Staff member removed");
    } catch (e) {
      toast.error("Failed to remove staff", {
        description: e instanceof Error ? e.message : "Unknown error",
      });
    } finally {
      setRemovingId(null);
    }
  };

  const getRoleLabel = (role: string) => {
    const found = ROLE_OPTIONS.find((r) => r.value === role);
    return found ? found.label : role;
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Users className="h-5 w-5" />
          Staff Assignments ({assignments.length})
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Add staff form */}
        <div className="rounded-md border p-4 space-y-3">
          <Label className="text-sm font-medium">Assign Staff Member</Label>
          <div className="grid gap-3 sm:grid-cols-[1fr_auto_auto]">
            <Select
              value={selectedUserId}
              onValueChange={setSelectedUserId}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select a team member" />
              </SelectTrigger>
              <SelectContent>
                <div className="p-2">
                  <Input
                    placeholder="Search users..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="mb-2"
                    onKeyDown={(e) => e.stopPropagation()}
                  />
                </div>
                {filteredUsers.length === 0 ? (
                  <p className="p-2 text-sm text-muted-foreground">
                    {unassignedUsers.length === 0
                      ? "All users are already assigned"
                      : "No matching users"}
                  </p>
                ) : (
                  filteredUsers.map((user) => (
                    <SelectItem key={user.id} value={user.id}>
                      {user.name} ({user.email})
                    </SelectItem>
                  ))
                )}
              </SelectContent>
            </Select>

            <Select value={selectedRole} onValueChange={setSelectedRole}>
              <SelectTrigger className="w-[180px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {ROLE_OPTIONS.map((role) => (
                  <SelectItem key={role.value} value={role.value}>
                    {role.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            <Button
              type="button"
              onClick={handleAdd}
              disabled={!selectedUserId || isAdding}
              size="default"
            >
              {isAdding ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : (
                <Plus className="mr-2 h-4 w-4" />
              )}
              Assign
            </Button>
          </div>
        </div>

        {/* Current assignments */}
        {assignments.length === 0 ? (
          <p className="text-sm text-muted-foreground py-4 text-center">
            No staff assigned to this client yet.
          </p>
        ) : (
          <div className="space-y-2">
            {assignments.map((assignment) => (
              <div
                key={assignment.id}
                className="flex items-center justify-between rounded-md border p-3"
              >
                <div className="flex items-center gap-3">
                  <Avatar className="h-9 w-9">
                    <AvatarImage src={assignment.user.avatar ?? undefined} />
                    <AvatarFallback>
                      {assignment.user.name
                        .split(" ")
                        .map((n) => n[0])
                        .join("")}
                    </AvatarFallback>
                  </Avatar>
                  <div>
                    <p className="text-sm font-medium">
                      {assignment.user.name}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      {assignment.user.email}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant="secondary">
                    {getRoleLabel(assignment.role)}
                  </Badge>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8 text-muted-foreground hover:text-destructive"
                    onClick={() => handleRemove(assignment.user.id)}
                    disabled={removingId === assignment.user.id}
                    aria-label={`Remove ${assignment.user.name}`}
                  >
                    {removingId === assignment.user.id ? (
                      <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                      <Trash2 className="h-4 w-4" />
                    )}
                  </Button>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
