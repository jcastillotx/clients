import { NextResponse } from "next/server";
import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";

const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024; // 5MB
const ALLOWED_IMAGE_TYPES: Record<string, string> = {
  "image/jpeg": "jpg",
  "image/png": "png",
  "image/webp": "webp",
  "image/gif": "gif",
};

function asRecord(value: unknown): Record<string, unknown> {
  if (value && typeof value === "object") {
    return value as Record<string, unknown>;
  }
  return {};
}

export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Authentication required" }, { status: 401 });
    }

    const formData = await request.formData();
    const file = formData.get("file");

    if (!(file instanceof File)) {
      return NextResponse.json({ error: "No file provided" }, { status: 400 });
    }

    const extension = ALLOWED_IMAGE_TYPES[file.type];
    if (!extension) {
      return NextResponse.json({ error: "Only JPG, PNG, WEBP, and GIF files are allowed" }, { status: 400 });
    }

    if (file.size > MAX_FILE_SIZE_BYTES) {
      return NextResponse.json({ error: "File size must be 5MB or less" }, { status: 400 });
    }

    const adminClient = createAdminClientIfAvailable();
    const dbClient = adminClient ?? supabase;
    const filePath = `profiles/${user.id}/${Date.now()}-${crypto.randomUUID()}.${extension}`;

    const { data: uploadData, error: uploadError } = await dbClient.storage.from("avatars").upload(filePath, file, {
      upsert: true,
      contentType: file.type,
      cacheControl: "3600",
    });

    if (uploadError || !uploadData?.path) {
      return NextResponse.json({ error: uploadError?.message || "Failed to upload image" }, { status: 500 });
    }

    const { data: publicUrlData } = dbClient.storage.from("avatars").getPublicUrl(uploadData.path);
    const avatarUrl = publicUrlData?.publicUrl;

    if (!avatarUrl) {
      return NextResponse.json({ error: "Failed to resolve uploaded image URL" }, { status: 500 });
    }

    const { error: userUpdateError } = await dbClient
      .from("users")
      .update({
        avatar: avatarUrl,
        updated_at: new Date().toISOString(),
      })
      .eq("id", user.id);

    if (userUpdateError) {
      return NextResponse.json({ error: userUpdateError.message }, { status: 500 });
    }

    const mergedMetadata = {
      ...asRecord(user.user_metadata),
      avatar: avatarUrl,
    };

    if (adminClient) {
      const { error: authUpdateError } = await adminClient.auth.admin.updateUserById(user.id, {
        user_metadata: mergedMetadata,
      });
      if (authUpdateError) {
        return NextResponse.json({ error: authUpdateError.message }, { status: 500 });
      }
    } else {
      const { error: authUpdateError } = await supabase.auth.updateUser({
        data: mergedMetadata,
      });
      if (authUpdateError) {
        return NextResponse.json({ error: authUpdateError.message }, { status: 500 });
      }
    }

    return NextResponse.json({
      success: true,
      avatarUrl,
    });
  } catch (error) {
    console.error("Error uploading profile image:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to upload profile image",
      },
      { status: 500 },
    );
  }
}
