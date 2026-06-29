"use client";

import { useState, useEffect, useRef } from "react";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { Loader2, Trash2, UserPlus } from "lucide-react";
import { toast } from "sonner";
import { formatCurrency } from "@/lib/utils";

interface TeamMember {
  userId: string;
  name: string;
  role: string;
  hourlyRate?: number;
}

interface StaffUser {
  id: string;
  name: string;
  email: string;
  avatar?: string | null;
  is_super_admin?: boolean | null;
}

interface ProjectTeamSectionProps {
  projectId: string;
  initialTeamMembers: Array<{ userId: string; name: string; role: string; hourlyRate?: number }>;
  currency: string;
}

export function ProjectTeamSection({
  projectId,
  initialTeamMembers,
  currency,
}: ProjectTeamSectionProps) {
  const [teamMembers, setTeamMembers] = useState<TeamMember[]>(initialTeamMembers);
  const [saving, setSaving] = useState(false);
  const [dialogOpen, setDialogOpen] = useState(false);

  // Add member form state
  const [searchQuery, setSearchQuery] = useState("");
  const [searchResults, setSearchResults] = useState<StaffUser[]>([]);
  const [selectedUser, setSelectedUser] = useState<StaffUser | null>(null);
  const [showDropdown, setShowDropdown] = useState(false);
  const [role, setRole] = useState("");
  const [hourlyRate, setHourlyRate] = useState("");

  const searchDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Debounced search
  useEffect(() => {
    if (selectedUser || !searchQuery.trim()) {
      setSearchResults([]);
      setShowDropdown(false);
      return;
    }

    if (searchDebounceRef.current) {
      clearTimeout(searchDebounceRef.current);
    }

    searchDebounceRef.current = setTimeout(async () => {
      try {
        const res = await fetch(`/api/users/staff?search=${encodeURIComponent(searchQuery)}`);
        if (!res.ok) return;
        const json = await res.json();
        setSearchResults(json.data ?? []);
        setShowDropdown(true);
      } catch {
        // silently ignore search errors
      }
    }, 300);

    return () => {
      if (searchDebounceRef.current) {
        clearTimeout(searchDebounceRef.current);
      }
    };
  }, [searchQuery, selectedUser]);

  // Close dropdown on outside click
  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setShowDropdown(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  function resetForm() {
    setSearchQuery("");
    setSearchResults([]);
    setSelectedUser(null);
    setShowDropdown(false);
    setRole("");
    setHourlyRate("");
  }

  function handleSelectUser(user: StaffUser) {
    setSelectedUser(user);
    setSearchQuery(user.name);
    setShowDropdown(false);
    setSearchResults([]);
  }

  async function handleAddMember() {
    if (!selectedUser || !role.trim()) return;

    const parsed = hourlyRate ? parseFloat(hourlyRate) : undefined;
    const newMember: TeamMember = {
      userId: selectedUser.id,
      name: selectedUser.name,
      role: role.trim(),
      ...(parsed !== undefined && !isNaN(parsed) ? { hourlyRate: parsed } : {}),
    };
    const newTeamMembers = [...teamMembers, newMember];

    setSaving(true);
    try {
      const res = await fetch(`/api/projects/${projectId}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ teamMembers: newTeamMembers }),
      });
      if (!res.ok) throw new Error("Failed to add team member");
      const json = await res.json();
      const updated: TeamMember[] = json.data?.teamMembers ?? newTeamMembers;
      setTeamMembers(updated);
      toast.success("Team member added");
      setDialogOpen(false);
      resetForm();
    } catch {
      toast.error("Failed to add team member");
    } finally {
      setSaving(false);
    }
  }

  async function handleRemoveMember(userId: string) {
    const newTeamMembers = teamMembers.filter((m) => m.userId !== userId);

    setSaving(true);
    try {
      const res = await fetch(`/api/projects/${projectId}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ teamMembers: newTeamMembers }),
      });
      if (!res.ok) throw new Error("Failed to remove team member");
      const json = await res.json();
      const updated: TeamMember[] = json.data?.teamMembers ?? newTeamMembers;
      setTeamMembers(updated);
      toast.success("Team member removed");
    } catch {
      toast.error("Failed to remove team member");
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <Card>
        <CardHeader className="flex flex-row items-start justify-between space-y-0">
          <div>
            <CardTitle>Team Members</CardTitle>
            <CardDescription>People working on this project</CardDescription>
          </div>
          <Button
            size="sm"
            onClick={() => {
              resetForm();
              setDialogOpen(true);
            }}
          >
            <UserPlus className="h-4 w-4 mr-2" />
            Add Member
          </Button>
        </CardHeader>
        <CardContent>
          {teamMembers.length > 0 ? (
            <div className="space-y-3">
              {teamMembers.map((member) => (
                <div key={member.userId} className="flex items-center justify-between p-3 rounded-lg border">
                  <div className="flex items-center gap-3">
                    <div>
                      <div className="font-medium">{member.name}</div>
                      <Badge variant="secondary" className="mt-1">
                        {member.role}
                      </Badge>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    {member.hourlyRate !== undefined && member.hourlyRate !== null && (
                      <div className="text-sm text-muted-foreground">
                        {formatCurrency(member.hourlyRate, currency)}/hr
                      </div>
                    )}
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleRemoveMember(member.userId)}
                      disabled={saving}
                      aria-label={`Remove ${member.name}`}
                    >
                      <Trash2 className="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              No team members assigned yet
            </div>
          )}
        </CardContent>
      </Card>

      <Dialog
        open={dialogOpen}
        onOpenChange={(open) => {
          setDialogOpen(open);
          if (!open) resetForm();
        }}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Team Member</DialogTitle>
          </DialogHeader>

          <div className="space-y-4 py-2">
            {/* User search */}
            <div className="space-y-2">
              <Label htmlFor="user-search">Search Staff</Label>
              <div className="relative" ref={dropdownRef}>
                <Input
                  id="user-search"
                  placeholder="Type name or email..."
                  value={searchQuery}
                  onChange={(e) => {
                    setSearchQuery(e.target.value);
                    if (selectedUser) setSelectedUser(null);
                  }}
                  autoComplete="off"
                />
                {showDropdown && searchResults.length > 0 && (
                  <div className="absolute z-50 w-full mt-1 rounded-md border bg-popover shadow-md max-h-48 overflow-y-auto">
                    {searchResults.map((u) => (
                      <div
                        key={u.id}
                        className="px-3 py-2 cursor-pointer hover:bg-accent text-sm"
                        onMouseDown={(e) => {
                          e.preventDefault();
                          handleSelectUser(u);
                        }}
                      >
                        <div className="font-medium">{u.name}</div>
                        <div className="text-muted-foreground text-xs">{u.email}</div>
                      </div>
                    ))}
                  </div>
                )}
                {showDropdown && searchResults.length === 0 && searchQuery.trim() && (
                  <div className="absolute z-50 w-full mt-1 rounded-md border bg-popover shadow-md">
                    <div className="px-3 py-2 text-sm text-muted-foreground">No staff found</div>
                  </div>
                )}
              </div>
            </div>

            {/* Role */}
            <div className="space-y-2">
              <Label htmlFor="member-role">Role</Label>
              <Input
                id="member-role"
                placeholder="e.g. Developer, Designer"
                value={role}
                onChange={(e) => setRole(e.target.value)}
              />
            </div>

            {/* Hourly Rate (optional) */}
            <div className="space-y-2">
              <Label htmlFor="member-rate">Hourly Rate (optional)</Label>
              <Input
                id="member-rate"
                type="number"
                placeholder="0.00"
                min="0"
                step="0.01"
                value={hourlyRate}
                onChange={(e) => setHourlyRate(e.target.value)}
              />
            </div>
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setDialogOpen(false);
                resetForm();
              }}
              disabled={saving}
            >
              Cancel
            </Button>
            <Button
              onClick={handleAddMember}
              disabled={!selectedUser || !role.trim() || saving}
            >
              {saving ? (
                <>
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                  Saving...
                </>
              ) : (
                "Save"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
