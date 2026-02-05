# Multi-Role RBAC System

## Overview

The Next.js application now includes a comprehensive Role-Based Access Control (RBAC) system that supports multi-role assignments per user. This enables fine-grained permission management for different user types.

## Features

✅ **Multi-Role Support** - Users can have multiple roles simultaneously
✅ **Granular Permissions** - 30+ permissions across different resources
✅ **System Roles** - Pre-defined roles with protection against deletion
✅ **Custom Roles** - Create custom roles with specific permission sets
✅ **Permission Gates** - React components for conditional UI rendering
✅ **Database Functions** - Optimized permission checking via PostgreSQL functions
✅ **Row-Level Security** - RLS policies enforce access control at the database level
✅ **Admin UI** - Complete admin interface for role and permission management

## Database Schema

### Tables

**`roles`**

- Stores role definitions (admin, account_manager, staff, client, etc.)
- `is_system` flag prevents deletion of core roles

**`permissions`**

- Granular permissions (resource + action)
- Examples: `clients.create`, `invoices.read`, `users.assign_roles`

**`role_permissions`**

- Many-to-many relationship between roles and permissions

**`user_roles`**

- Many-to-many relationship between users and roles
- Supports multiple roles per user

### Helper Functions

```sql
-- Check if user has permission
user_has_permission(user_id UUID, permission_name TEXT) RETURNS BOOLEAN

-- Check if user has role
user_has_role(user_id UUID, role_name TEXT) RETURNS BOOLEAN

-- Get all user permissions
get_user_permissions(user_id UUID) RETURNS TABLE(permission_name, resource, action)

-- Get all user roles
get_user_roles(user_id UUID) RETURNS TABLE(role_id, role_name, role_description)
```

## System Roles

### Super Admin

- **Full system access**
- All permissions granted
- Cannot be deleted

### Admin

- **Management capabilities**
- Most permissions except system-critical ones
- Cannot update settings or delete roles

### Account Manager

- **Client and project management**
- Can create/update clients, invoices, requests
- Access to financial reports
- Can assign requests to staff

### Staff

- **Limited operational access**
- Read-only access to clients and invoices
- Can read and update assigned requests
- Read-only access to documents

### Client

- **Read-only for their own data**
- Can view their client profile
- Can pay invoices
- Can create and view their own requests

## Permissions List

### Clients (4)

- `clients.create` - Create new clients
- `clients.read` - View client information
- `clients.update` - Update client information
- `clients.delete` - Delete clients

### Invoices (6)

- `invoices.create` - Create new invoices
- `invoices.read` - View invoices
- `invoices.update` - Update invoices
- `invoices.delete` - Delete invoices
- `invoices.send` - Send invoices to clients
- `invoices.pay` - Process invoice payments

### Requests (5)

- `requests.create` - Create new requests
- `requests.read` - View requests
- `requests.update` - Update requests
- `requests.delete` - Delete requests
- `requests.assign` - Assign requests to staff

### Users (5)

- `users.create` - Create new users
- `users.read` - View user information
- `users.update` - Update user information
- `users.delete` - Delete users
- `users.assign_roles` - Assign roles to users

### Roles (4)

- `roles.create` - Create new roles
- `roles.read` - View roles
- `roles.update` - Update roles
- `roles.delete` - Delete roles

### Documents (4)

- `documents.create` - Upload documents
- `documents.read` - View documents
- `documents.update` - Update documents
- `documents.delete` - Delete documents

### Reports (3)

- `reports.financial` - Access financial reports
- `reports.analytics` - Access analytics reports
- `reports.export` - Export reports

### Settings (2)

- `settings.read` - View system settings
- `settings.update` - Update system settings

## Setup Instructions

### 1. Run Database Migration

```bash
# Connect to your Supabase project
psql "postgresql://postgres:password@db.xxx.supabase.co:5432/postgres"

# Run the migration
\i lib/db/migrations/001_create_rbac_tables.sql
```

This will:

- Create tables (roles, permissions, role_permissions, user_roles)
- Insert system roles
- Insert all permissions
- Assign permissions to system roles
- Create helper functions
- Enable Row-Level Security

### 2. Assign Initial Roles

After running the migration, assign a super admin role to yourself:

```sql
-- Get your user ID from Supabase Auth
SELECT id, email FROM auth.users;

-- Assign super_admin role
INSERT INTO user_roles (user_id, role_id)
SELECT 'your-user-id-here', id
FROM roles
WHERE name = 'super_admin';
```

## Usage in Code

### Server Components (Permission Checks)

```typescript
import { hasPermission, hasRole, Permissions, Roles } from "@/lib/rbac/permissions";

export default async function MyPage() {
  // Check specific permission
  const canCreate = await hasPermission(Permissions.CLIENTS_CREATE);
  if (!canCreate) {
    return <div>Access Denied</div>;
  }

  // Check role
  const isAdmin = await hasRole(Roles.ADMIN);

  // Check multiple permissions
  const permissions = await getUserPermissions();

  return <div>...</div>;
}
```

### Client Components (Permission Gates)

```typescript
import { PermissionGate, MultiPermissionGate } from "@/components/rbac/permission-gate";

export function MyComponent() {
  return (
    <>
      {/* Show button only if user can create invoices */}
      <PermissionGate permission="invoices.create">
        <Button>Create Invoice</Button>
      </PermissionGate>

      {/* Show section only if user has ANY of these permissions */}
      <MultiPermissionGate
        permissions={["invoices.create", "invoices.update"]}
        requireAll={false}
      >
        <InvoiceForm />
      </MultiPermissionGate>

      {/* Show only if user has ALL permissions */}
      <MultiPermissionGate
        permissions={["users.create", "users.assign_roles"]}
        requireAll={true}
      >
        <UserManagement />
      </MultiPermissionGate>
    </>
  );
}
```

### API Routes (Permission Enforcement)

```typescript
import { hasPermission } from "@/lib/rbac/permissions";

export async function POST(request: Request) {
  // Check permission
  const canCreate = await hasPermission("clients.create");
  if (!canCreate) {
    return NextResponse.json({ error: "Permission denied" }, { status: 403 });
  }

  // Proceed with operation
  // ...
}
```

## Admin Interface

### Access Roles Management

Navigate to `/admin/roles` to:

- View all roles with assigned permissions
- Create new custom roles
- Edit existing roles (except system roles)
- Delete custom roles
- Assign permissions to roles
- See user count per role

### Assign Roles to Users

1. Navigate to user management
2. Select a user
3. Click "Manage Roles"
4. Select multiple roles to assign
5. Save changes

Users will immediately gain permissions from all assigned roles.

## Row-Level Security (RLS)

RLS policies automatically enforce permission checks at the database level:

```sql
-- Example: Only users with permission can manage roles
CREATE POLICY "Only admins can manage roles" ON roles FOR ALL
  USING (user_has_permission(auth.uid(), 'roles.update'));

-- Users can view their own roles
CREATE POLICY "Users can view their own roles" ON user_roles FOR SELECT
  USING (user_id = auth.uid() OR user_has_permission(auth.uid(), 'users.read'));
```

## Best Practices

### 1. Use Permission Constants

```typescript
// Good
import { Permissions } from "@/lib/rbac/permissions";
hasPermission(Permissions.CLIENTS_CREATE);

// Bad
hasPermission("clients.create"); // Typos can cause bugs
```

### 2. Check Permissions on Server

```typescript
// Server Component - Checked once on load
export default async function Page() {
  const canCreate = await hasPermission("invoices.create");
  return <MyForm canCreate={canCreate} />;
}

// Client Component - Uses Permission Gate
export function MyForm({ canCreate }: { canCreate: boolean }) {
  return canCreate ? <CreateButton /> : null;
}
```

### 3. Protect API Routes

Always check permissions in API routes, even if UI is gated:

```typescript
export async function POST(request: Request) {
  // UI might be hidden, but API must verify
  const canCreate = await hasPermission("invoices.create");
  if (!canCreate) {
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });
  }
  // ...
}
```

### 4. Use Multi-Role Assignments

Assign multiple roles to users when they need combined permissions:

```typescript
// Example: User is both Account Manager and has custom reporting role
INSERT INTO user_roles (user_id, role_id) VALUES
  ('user-id', (SELECT id FROM roles WHERE name = 'account_manager')),
  ('user-id', (SELECT id FROM roles WHERE name = 'advanced_reporter'));
```

## Adding New Permissions

### 1. Add to Database

```sql
INSERT INTO permissions (name, description, resource, action) VALUES
  ('custom_feature.manage', 'Manage custom feature', 'custom_feature', 'manage');
```

### 2. Assign to Roles

```sql
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'admin' AND p.name = 'custom_feature.manage';
```

### 3. Add to Constants

```typescript
// lib/rbac/permissions.ts
export const Permissions = {
  // ... existing
  CUSTOM_FEATURE_MANAGE: "custom_feature.manage",
} as const;
```

### 4. Use in Code

```typescript
const canManage = await hasPermission(Permissions.CUSTOM_FEATURE_MANAGE);
```

## Troubleshooting

### Permission Check Always Returns False

1. Verify user has roles assigned:

```sql
SELECT * FROM user_roles WHERE user_id = 'your-user-id';
```

2. Verify role has permissions:

```sql
SELECT r.name, p.name
FROM user_roles ur
JOIN role_permissions rp ON ur.role_id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
JOIN roles r ON ur.role_id = r.id
WHERE ur.user_id = 'your-user-id';
```

3. Check RLS policies are not blocking:

```sql
-- Disable RLS temporarily for debugging
ALTER TABLE user_roles DISABLE ROW LEVEL SECURITY;
```

### Cannot Delete System Role

System roles have `is_system = true` and cannot be deleted. This is intentional to protect core roles.

### User Has Permission But Still Denied

Check that:

1. Permission name matches exactly (case-sensitive)
2. RLS policies allow the operation
3. API route checks the correct permission
4. User's session is valid and fresh

## Migration from Laravel Spatie Permission

The new system maintains similar concepts but with multi-role support:

| Laravel Spatie       | Next.js RBAC                          |
| -------------------- | ------------------------------------- |
| `hasRole()`          | `hasRole()`                           |
| `hasPermissionTo()`  | `hasPermission()`                     |
| `assignRole()`       | API: `POST /api/rbac/users/:id/roles` |
| `givePermissionTo()` | Assign via role permissions           |
| Direct permissions   | Not supported (use roles)             |

**Key Difference**: Users don't have direct permissions. All permissions come through roles. This simplifies management.

## Future Enhancements

- [ ] Permission inheritance (role hierarchies)
- [ ] Time-based role assignments (expiring roles)
- [ ] Audit logging for role changes
- [ ] Permission templates for quick role creation
- [ ] Conditional permissions (e.g., owner-only actions)
