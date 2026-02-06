"use client";

import { useState, useEffect } from "react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { X } from "lucide-react";

interface Role {
  id: string;
  name: string;
  description: string | null;
}

interface Client {
  id: string;
  company_name: string;
}

interface User {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  client_id: string | null;
  is_active: boolean;
  // properties required by parent component
  avatar: string | null; 
  status: string;
  last_login_at: string | null;
  created_at: string;
  client?: {
    id: string;
    company_name: string;
  } | null;
  user_roles?: Array<{
    role: {
      id: string;
      name: string;
      description: string | null;
    };
  }>;
}

interface UserDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  user: User | null;
  roles: Role[];
  clients: Client[];
  onSuccess: (user: User) => void;
}

export function UserDialog({ open, onOpenChange, user, roles, clients, onSuccess }: UserDialogProps) {
  const [isLoading, setIsLoading] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    client_id: "",
    is_active: true,
    password: "",
  });
  const [selectedRoles, setSelectedRoles] = useState<string[]>([]);

  // Initialize form when user changes
  useEffect(() => {
    if (user) {
      setFormData({
        name: user.name,
        email: user.email,
        phone: user.phone || "",
        client_id: user.client_id || "",
        is_active: user.is_active,
        password: "",
      });
      setSelectedRoles(user.user_roles?.map((ur) => ur.role.id) || []);
    } else {
      setFormData({
        name: "",
        email: "",
        phone: "",
        client_id: "",
        is_active: true,
        password: "",
      });
      setSelectedRoles([]);
    }
  }, [user]);

  const handleAddRole = (roleId: string) => {
    if (!selectedRoles.includes(roleId)) {
      setSelectedRoles([...selectedRoles, roleId]);
    }
  };

  const handleRemoveRole = (roleId: string) => {
    setSelectedRoles(selectedRoles.filter((id) => id !== roleId));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.name || !formData.email) {
      alert("Name and email are required");
      return;
    }

    if (!user && !formData.password) {
      alert("Password is required for new users");
      return;
    }

    setIsLoading(true);

    try {
      const payload = {
        ...formData,
        client_id: formData.client_id || null,
        phone: formData.phone || null,
        roles: selectedRoles,
      };

      const url = user ? `/api/admin/users/${user.id}` : "/api/admin/users";
      const method = user ? "PATCH" : "POST";

      const response = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || "Failed to save user");
      }

      const { user: savedUser } = await response.json();
      onSuccess(savedUser);
    } catch (error) {
      console.error("Error saving user:", error);
      alert(error instanceof Error ? error.message : "Failed to save user");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[600px]">
        <form onSubmit={handleSubmit}>
          <DialogHeader>
            <DialogTitle>{user ? "Edit User" : "Create New User"}</DialogTitle>
            <DialogDescription>
              {user
                ? "Update user information and role assignments"
                : "Create a new user account with roles and permissions"}
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4 py-4">
            {/* Name */}
            <div className="space-y-2">
              <Label htmlFor="name">Name *</Label>
              <Input
                id="name"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                placeholder="John Doe"
                disabled={isLoading}
                required
              />
            </div>

            {/* Email */}
            <div className="space-y-2">
              <Label htmlFor="email">Email *</Label>
              <Input
                id="email"
                type="email"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                placeholder="john@example.com"
                disabled={isLoading}
                required
              />
            </div>

            {/* Phone */}
            <div className="space-y-2">
              <Label htmlFor="phone">Phone</Label>
              <Input
                id="phone"
                type="tel"
                value={formData.phone}
                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                placeholder="+1 (555) 123-4567"
                disabled={isLoading}
              />
            </div>

            {/* Password (only for new users or when changing) */}
            <div className="space-y-2">
              <Label htmlFor="password">
                Password {!user && "*"}
                {user && " (leave blank to keep current)"}
              </Label>
              <Input
                id="password"
                type="password"
                value={formData.password}
                onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                placeholder="••••••••"
                disabled={isLoading}
                required={!user}
              />
            </div>

            {/* Client */}
            <div className="space-y-2">
              <Label htmlFor="client">Client Organization</Label>
              <Select
                value={formData.client_id}
                onValueChange={(value) => setFormData({ ...formData, client_id: value })}
                disabled={isLoading}
              >
                <SelectTrigger id="client">
                  <SelectValue placeholder="No client (staff member)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">No client (staff member)</SelectItem>
                  {clients.map((client) => (
                    <SelectItem key={client.id} value={client.id}>
                      {client.company_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Roles */}
            <div className="space-y-2">
              <Label>Roles</Label>
              <div className="space-y-2">
                <Select onValueChange={handleAddRole} disabled={isLoading}>
                  <SelectTrigger>
                    <SelectValue placeholder="Add role..." />
                  </SelectTrigger>
                  <SelectContent>
                    {roles
                      .filter((role) => !selectedRoles.includes(role.id))
                      .map((role) => (
                        <SelectItem key={role.id} value={role.id}>
                          {role.name}
                          {role.description && (
                            <span className="text-xs text-muted-foreground"> - {role.description}</span>
                          )}
                        </SelectItem>
                      ))}
                  </SelectContent>
                </Select>

                {selectedRoles.length > 0 && (
                  <div className="flex flex-wrap gap-2">
                    {selectedRoles.map((roleId) => {
                      const role = roles.find((r) => r.id === roleId);
                      if (!role) return null;
                      return (
                        <Badge key={roleId} variant="secondary">
                          {role.name}
                          <button
                            type="button"
                            onClick={() => handleRemoveRole(roleId)}
                            className="ml-1 rounded-full hover:bg-muted"
                          >
                            <X className="h-3 w-3" />
                          </button>
                        </Badge>
                      );
                    })}
                  </div>
                )}
              </div>
            </div>

            {/* Active Status */}
            <div className="flex items-center justify-between rounded-lg border p-4">
              <div className="space-y-0.5">
                <Label htmlFor="is_active">Active Status</Label>
                <p className="text-sm text-muted-foreground">Inactive users cannot log in</p>
              </div>
              <Switch
                id="is_active"
                checked={formData.is_active}
                onCheckedChange={(checked) => setFormData({ ...formData, is_active: checked })}
                disabled={isLoading}
              />
            </div>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={isLoading}>
              Cancel
            </Button>
            <Button type="submit" disabled={isLoading}>
              {isLoading ? "Saving..." : user ? "Update User" : "Create User"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
