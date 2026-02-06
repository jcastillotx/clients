"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Search, UserPlus, MoreVertical, Shield, Trash2, Edit } from "lucide-react";
import { formatDistanceToNow } from "date-fns";
import { UserDialog } from "./user-dialog";

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
  avatar: string | null;
  client_id: string | null;
  is_active: boolean;
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

interface UserManagementProps {
  initialUsers: User[];
  roles: Role[];
  clients: Client[];
}

export function UserManagement({ initialUsers, roles, clients }: UserManagementProps) {
  const [users, setUsers] = useState(initialUsers);
  const [searchQuery, setSearchQuery] = useState("");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);

  // Filter users
  const filteredUsers = users.filter(
    (user) =>
      user.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      user.email.toLowerCase().includes(searchQuery.toLowerCase()) ||
      user.client?.company_name?.toLowerCase().includes(searchQuery.toLowerCase()),
  );

  const handleCreateUser = () => {
    setEditingUser(null);
    setDialogOpen(true);
  };

  const handleEditUser = (user: User) => {
    setEditingUser(user);
    setDialogOpen(true);
  };

  const handleUserSuccess = (updatedUser: User) => {
    if (editingUser) {
      setUsers((prev) => prev.map((u) => (u.id === updatedUser.id ? updatedUser : u)));
    } else {
      setUsers((prev) => [updatedUser, ...prev]);
    }
    setDialogOpen(false);
    setEditingUser(null);
  };

  const handleDeleteUser = async (userId: string) => {
    if (!confirm("Are you sure you want to delete this user?")) return;

    try {
      const response = await fetch(`/api/admin/users/${userId}`, {
        method: "DELETE",
      });

      if (!response.ok) throw new Error("Failed to delete user");

      setUsers((prev) => prev.filter((u) => u.id !== userId));
    } catch (error) {
      console.error("Error deleting user:", error);
      alert("Failed to delete user");
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">User Management</h1>
          <p className="text-muted-foreground">Manage users, roles, and permissions</p>
        </div>
        <Button onClick={handleCreateUser}>
          <UserPlus className="mr-2 h-4 w-4" />
          Add User
        </Button>
      </div>

      {/* Search */}
      <div className="relative">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          placeholder="Search users..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="pl-9"
        />
      </div>

      {/* Users Table */}
      <div className="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Client</TableHead>
              <TableHead>Roles</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Last Login</TableHead>
              <TableHead className="w-[50px]"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {filteredUsers.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="h-24 text-center text-muted-foreground">
                  No users found
                </TableCell>
              </TableRow>
            ) : (
              filteredUsers.map((user) => (
                <TableRow key={user.id}>
                  <TableCell>
                    <div>
                      <p className="font-medium">{user.name}</p>
                      {user.phone && <p className="text-sm text-muted-foreground">{user.phone}</p>}
                    </div>
                  </TableCell>
                  <TableCell>{user.email}</TableCell>
                  <TableCell>
                    {user.client ? (
                      <span>{user.client.company_name}</span>
                    ) : (
                      <span className="text-muted-foreground">No client</span>
                    )}
                  </TableCell>
                  <TableCell>
                    {user.user_roles && user.user_roles.length > 0 ? (
                      <div className="flex flex-wrap gap-1">
                        {user.user_roles.map(({ role }) => (
                          <Badge key={role.id} variant="secondary" className="text-xs">
                            <Shield className="mr-1 h-3 w-3" />
                            {role.name}
                          </Badge>
                        ))}
                      </div>
                    ) : (
                      <span className="text-muted-foreground">No roles</span>
                    )}
                  </TableCell>
                  <TableCell>
                    {user.is_active ? (
                      <Badge variant="default" className="bg-green-100 text-green-800">
                        Active
                      </Badge>
                    ) : (
                      <Badge variant="secondary">Inactive</Badge>
                    )}
                  </TableCell>
                  <TableCell className="text-muted-foreground">
                    {user.last_login_at ? (
                      <span>
                        {formatDistanceToNow(new Date(user.last_login_at), {
                          addSuffix: true,
                        })}
                      </span>
                    ) : (
                      <span>Never</span>
                    )}
                  </TableCell>
                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon">
                          <MoreVertical className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => handleEditUser(user)}>
                          <Edit className="mr-2 h-4 w-4" />
                          Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => handleDeleteUser(user.id)} className="text-destructive">
                          <Trash2 className="mr-2 h-4 w-4" />
                          Delete
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {/* User Dialog */}
      <UserDialog
        open={dialogOpen}
        onOpenChange={setDialogOpen}
        user={editingUser}
        roles={roles}
        clients={clients}
        onSuccess={handleUserSuccess}
      />
    </div>
  );
}
