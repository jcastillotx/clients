import { inngest } from "@/lib/inngest/client";
import { createClient } from "@/lib/supabase/server";
import { Roles } from "@/lib/rbac/permissions";
import { NextResponse } from "next/server";

export async function POST() {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    // Only super_admin or admin can trigger backfill
    const role = user.user_metadata?.role || user.user_metadata?.app_role;
    if (role !== Roles.SUPER_ADMIN && role !== Roles.ADMIN && !user.user_metadata?.is_super_admin) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    await inngest.send({
      name: "client.backfill",
      data: {},
    });

    return NextResponse.json({ success: true, message: "Backfill triggered" });
  } catch (error) {
    console.error("Error triggering backfill:", error);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
