"use client";

import { useState, useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Loader2 } from "lucide-react";
import { ApiClientError, fetchApi } from "@/lib/api/client";

const roleSchema = z.object({
  name: z.string().min(2, "Name must be at least 2 characters"),
  description: z.string().optional(),
});

type RoleFormInput = z.infer<typeof roleSchema>;

interface Permission {
  id: string;
  name: string;
  description: string | null;
  resource: string;
  action: string;
}

interface Role {
  id: string;
  name: string;
  description: string | null;
  is_system: boolean;
  role_permissions: Array<{
    permission: Permission;
  }>;
}

interface RoleDialogProps {
  role: Role | null;
  permissions: Permission[];
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onRoleCreated: (role: any) => void;
  onRoleUpdated: (role: any) => void;
}

export function RoleDialog({ role, permissions, open, onOpenChange, onRoleCreated, onRoleUpdated }: RoleDialogProps) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [selectedPermissions, setSelectedPermissions] = useState<Set<string>>(new Set());

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
  } = useForm<RoleFormInput>({
    resolver: zodResolver(roleSchema),
  });

  useEffect(() => {
    if (role) {
      reset({
        name: role.name,
        description: role.description || "",
      });
      setSelectedPermissions(new Set(role.role_permissions.map((rp) => rp.permission.id)));
    } else {
      reset({ name: "", description: "" });
      setSelectedPermissions(new Set());
    }
    setError(null);
  }, [role, reset]);

  const handlePermissionToggle = (permissionId: string) => {
    const newSelected = new Set(selectedPermissions);
    if (newSelected.has(permissionId)) {
      newSelected.delete(permissionId);
    } else {
      newSelected.add(permissionId);
    }
    setSelectedPermissions(newSelected);
  };

  const onSubmit = async (data: RoleFormInput) => {
    setIsSubmitting(true);
    setError(null);

    try {
      const url = role ? `/api/rbac/roles/${role.id}` : "/api/rbac/roles";
      const method = role ? "PATCH" : "POST";

      const savedRole = await fetchApi(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: data.name,
          description: data.description,
          permissionIds: Array.from(selectedPermissions),
        }),
      }, { fallbackMessage: "Failed to save role" });

      if (role) {
        onRoleUpdated(savedRole);
      } else {
        onRoleCreated(savedRole);
      }
    } catch (err) {
      if (err instanceof ApiClientError && err.status === 403) {
        setError("You do not have permission to manage roles.");
        return;
      }
      setError(err instanceof Error ? err.message : "Failed to save role");
    } finally {
      setIsSubmitting(false);
    }
  };

  // Group permissions by resource
  const groupedPermissions = permissions.reduce(
    (acc, perm) => {
      if (!acc[perm.resource]) {
        acc[perm.resource] = [];
      }
      acc[perm.resource].push(perm);
      return acc;
    },
    {} as Record<string, Permission[]>,
  );

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{role ? "Edit Role" : "Create Role"}</DialogTitle>
          <DialogDescription>
            {role ? "Update role details and permissions" : "Create a new role with specific permissions"}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          {error && <div className="rounded-md bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}

          <div className="space-y-2">
            <Label htmlFor="name">
              Role Name <span className="text-destructive">*</span>
            </Label>
            <Input id="name" placeholder="account_manager" {...register("name")} disabled={role?.is_system} />
            {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
            {role?.is_system && <p className="text-xs text-muted-foreground">System roles cannot be renamed</p>}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea id="description" placeholder="Describe this role..." rows={3} {...register("description")} />
          </div>

          <div className="space-y-4">
            <Label>Permissions</Label>
            <div className="border rounded-lg p-4 space-y-4 max-h-96 overflow-y-auto">
              {Object.entries(groupedPermissions).map(([resource, perms]) => (
                <div key={resource} className="space-y-2">
                  <h4 className="font-medium text-sm capitalize">{resource}</h4>
                  <div className="grid grid-cols-2 gap-2">
                    {perms.map((perm) => (
                      <label
                        key={perm.id}
                        className="flex items-center space-x-2 text-sm cursor-pointer hover:bg-muted p-2 rounded"
                      >
                        <input
                          type="checkbox"
                          checked={selectedPermissions.has(perm.id)}
                          onChange={() => handlePermissionToggle(perm.id)}
                          className="rounded border-gray-300"
                        />
                        <span className="flex-1">
                          <span className="font-medium">{perm.action}</span>
                          {perm.description && <span className="text-muted-foreground ml-2">- {perm.description}</span>}
                        </span>
                      </label>
                    ))}
                  </div>
                </div>
              ))}
            </div>
            <p className="text-xs text-muted-foreground">
              Selected {selectedPermissions.size} of {permissions.length} permissions
            </p>
          </div>

          <div className="flex justify-end gap-3">
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {role ? "Update Role" : "Create Role"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
