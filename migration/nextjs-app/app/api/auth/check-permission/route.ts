import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";

export async function POST(request: Request) {
  try {
    const supabase = createClient();

    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ hasPermission: false }, { status: 401 });
    }

    const { permission } = await request.json();

    if (!permission) {
      return NextResponse.json({ error: "Permission name required" }, { status: 400 });
    }

    const { data, error } = await supabase.rpc("user_has_permission", {
      p_user_id: user.id,
      p_permission_name: permission,
    });

    if (error) {
      console.error("Error checking permission:", error);
      return NextResponse.json({ hasPermission: false }, { status: 500 });
    }

    return NextResponse.json({ hasPermission: data === true });
  } catch (error) {
    console.error("Error in check-permission route:", error);
    return NextResponse.json({ hasPermission: false }, { status: 500 });
  }
}
