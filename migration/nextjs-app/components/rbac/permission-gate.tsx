"use client";

import { useEffect, useState } from "react";
import { checkPermissionClient } from "@/lib/rbac/permissions";

interface PermissionGateProps {
  permission: string;
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

/**
 * Permission Gate Component
 * Shows children only if user has the required permission
 *
 * Usage:
 * <PermissionGate permission="invoices.create">
 *   <Button>Create Invoice</Button>
 * </PermissionGate>
 */
export function PermissionGate({ permission, children, fallback = null }: PermissionGateProps) {
  const [hasPermission, setHasPermission] = useState<boolean | null>(null);

  useEffect(() => {
    checkPermissionClient(permission).then(setHasPermission);
  }, [permission]);

  if (hasPermission === null) {
    // Loading state - could show a skeleton
    return null;
  }

  if (!hasPermission) {
    return <>{fallback}</>;
  }

  return <>{children}</>;
}

interface MultiPermissionGateProps {
  permissions: string[];
  requireAll?: boolean; // If true, requires all permissions. If false, requires any permission.
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

/**
 * Multi-Permission Gate Component
 * Shows children only if user has required permissions
 *
 * Usage:
 * <MultiPermissionGate permissions={["invoices.create", "invoices.update"]} requireAll>
 *   <Button>Edit Invoice</Button>
 * </MultiPermissionGate>
 */
export function MultiPermissionGate({
  permissions,
  requireAll = false,
  children,
  fallback = null,
}: MultiPermissionGateProps) {
  const [hasAccess, setHasAccess] = useState<boolean | null>(null);

  useEffect(() => {
    const checkPermissions = async () => {
      const results = await Promise.all(permissions.map(checkPermissionClient));

      if (requireAll) {
        setHasAccess(results.every((result) => result === true));
      } else {
        setHasAccess(results.some((result) => result === true));
      }
    };

    checkPermissions();
  }, [permissions, requireAll]);

  if (hasAccess === null) {
    return null;
  }

  if (!hasAccess) {
    return <>{fallback}</>;
  }

  return <>{children}</>;
}
