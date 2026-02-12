import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";

// GET /api/rbac/permissions - List all permissions
export async function GET() {
  try {
    const supabase = await createClient();

    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    // RBAC read policy: allow users with either roles.read (RBAC scope)
    // or settings.read (broader settings visibility).
    const canReadRbac =
      (await hasPermission("roles.read")) || (await hasPermission("settings.read"));
    if (!canReadRbac) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const { data: permissions, error } = await supabase
      .from("permissions")
      .select("*")
      .order("resource")
      .order("action");

    if (error) throw error;

    // Group permissions by resource
    const groupedPermissions = permissions.reduce(
      (acc, perm) => {
        if (!acc[perm.resource]) {
          acc[perm.resource] = [];
        }
        acc[perm.resource].push(perm);
        return acc;
      },
      {} as Record<string, typeof permissions>,
    );

    return NextResponse.json({ permissions, groupedPermissions });
  } catch (error) {
    console.error("Error fetching permissions:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to fetch permissions" },
      { status: 500 },
    );
  }
}
