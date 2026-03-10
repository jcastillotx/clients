import { NextRequest, NextResponse } from "next/server";
import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";

function asRecord(value: unknown): Record<string, unknown> {
  if (value && typeof value === "object") return value as Record<string, unknown>;
  return {};
}

/**
 * POST /api/settings/profile-image
 * Body: { key: string }  — S3 object key after the browser uploaded directly via presigned URL.
 * Saves the proxy avatar URL to the users table and auth metadata.
 */
export async function POST(req: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user }, error: authError } = await supabase.auth.getUser();
    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const { key } = await req.json();
    if (!key || typeof key !== "string") {
      return NextResponse.json({ error: "Missing S3 key" }, { status: 400 });
    }

    // Validate key belongs to this user
    if (!key.startsWith(`avatars/${user.id}/`)) {
      return NextResponse.json({ error: "Invalid key" }, { status: 400 });
    }

    // Store as proxy URL so the bucket stays private
    const avatarUrl = `/api/avatar?key=${encodeURIComponent(key)}`;

    const adminClient = createAdminClientIfAvailable();
    const dbClient = adminClient ?? supabase;

    const { error: userUpdateError } = await dbClient
      .from("users")
      .update({ avatar: avatarUrl, updated_at: new Date().toISOString() })
      .eq("id", user.id);

    if (userUpdateError) {
      return NextResponse.json({ error: userUpdateError.message }, { status: 500 });
    }

    const mergedMetadata = { ...asRecord(user.user_metadata), avatar: avatarUrl };

    if (adminClient) {
      const { error } = await adminClient.auth.admin.updateUserById(user.id, {
        user_metadata: mergedMetadata,
      });
      if (error) return NextResponse.json({ error: error.message }, { status: 500 });
    } else {
      const { error } = await supabase.auth.updateUser({ data: mergedMetadata });
      if (error) return NextResponse.json({ error: error.message }, { status: 500 });
    }

    return NextResponse.json({ success: true, avatarUrl });
  } catch (error) {
    console.error("Error saving profile image:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to save profile image" },
      { status: 500 },
    );
  }
}
