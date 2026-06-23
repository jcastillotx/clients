"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Plus, Users, Shield, Edit, Trash2 } from "lucide-react";
import { RoleDialog } from "./role-dialog";
import { ApiClientError, fetchApi } from "@/lib/api/client";

interface Role {
  id: string;
  name: string;
  description: string | null;
  is_system: boolean;
  role_permissions: Array<{
    permission: {
      id: string;
      name: string;
      description: string | null;
      resource: string;
      action: string;
    };
  }>;
  user_roles: Array<{ count: number }>;
}

interface Permission {
  id: string;
  name: string;
  description: string | null;
  resource: string;
  action: string;
}

interface RoleListProps {
  initialRoles: Role[];
  permissions: Permission[];
}

export function RoleList({ initialRoles, permissions }: RoleListProps) {
  const [roles, setRoles] = useState(initialRoles);
  const [selectedRole, setSelectedRole] = useState<Role | null>(null);
  const [showDialog, setShowDialog] = useState(false);

  const handleCreateRole = () => {
    setSelectedRole(null);
    setShowDialog(true);
  };

  const handleEditRole = (role: Role) => {
    setSelectedRole(role);
    setShowDialog(true);
  };

  const handleDeleteRole = async (roleId: string) => {
    if (!confirm("Are you sure you want to delete this role?")) return;

    try {
      await fetchApi(`/api/rbac/roles/${roleId}`, { method: "DELETE" }, {
        fallbackMessage: "Failed to delete role",
      });

      setRoles(roles.filter((r) => r.id !== roleId));
    } catch (error) {
      console.error("Error deleting role:", error);
      if (error instanceof ApiClientError && error.status === 403) {
        alert("You do not have permission to manage roles.");
        return;
      }
      alert(error instanceof Error ? error.message : "Failed to delete role");
    }
  };

  const handleRoleCreated = (newRole: Role) => {
    setRoles([...roles, newRole]);
    setShowDialog(false);
  };

  const handleRoleUpdated = (updatedRole: Role) => {
    setRoles(roles.map((r) => (r.id === updatedRole.id ? updatedRole : r)));
    setShowDialog(false);
  };

  const getUserCount = (role: Role) => {
    return role.user_roles?.[0]?.count || 0;
  };

  return (
    <>
      <div className="flex justify-end mb-6">
        <Button onClick={handleCreateRole}>
          <Plus className="mr-2 h-4 w-4" />
          Create Role
        </Button>
      </div>

      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {roles.map((role) => (
          <Card key={role.id}>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div className="flex items-center gap-2">
                  <Shield className="h-5 w-5 text-primary" />
                  <CardTitle className="text-lg">{role.name}</CardTitle>
                  {role.is_system && <Badge variant="secondary">System</Badge>}
                </div>
                <div className="flex gap-2">
                  <Button variant="ghost" size="sm" onClick={() => handleEditRole(role)}>
                    <Edit className="h-4 w-4" />
                  </Button>
                  {!role.is_system && (
                    <Button variant="ghost" size="sm" onClick={() => handleDeleteRole(role.id)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  )}
                </div>
              </div>
              <CardDescription>{role.description || "No description"}</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Users className="h-4 w-4" />
                  <span>{getUserCount(role)} users</span>
                </div>

                <div>
                  <p className="text-sm font-medium mb-2">Permissions ({role.role_permissions?.length || 0})</p>
                  <div className="flex flex-wrap gap-1">
                    {role.role_permissions?.slice(0, 5).map((rp) => (
                      <Badge key={rp.permission.id} variant="outline" className="text-xs">
                        {rp.permission.resource}.{rp.permission.action}
                      </Badge>
                    ))}
                    {(role.role_permissions?.length || 0) > 5 && (
                      <Badge variant="outline" className="text-xs">
                        +{(role.role_permissions?.length || 0) - 5} more
                      </Badge>
                    )}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <RoleDialog
        role={selectedRole}
        permissions={permissions}
        open={showDialog}
        onOpenChange={setShowDialog}
        onRoleCreated={handleRoleCreated}
        onRoleUpdated={handleRoleUpdated}
      />
    </>
  );
}
