import {
  putS3Object,
  deleteS3Object as removeS3Object,
} from "@/lib/storage/s3";
import { NextRequest } from "next/server";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

import {
  createAdminClientIfAvailable,
  createClient,
} from "@/lib/supabase/server";
import { getS3Credentials } from "@/lib/storage/get-s3-credentials";

const ALLOWED_TYPES: Record<string, string> = {
  "image/jpeg": "jpg",
  "image/png": "png",
  "image/webp": "webp",
  "image/gif": "gif",
};
const MAX_BYTES = 5 * 1024 * 1024;

function asRecord(value: unknown): Record<string, unknown> {
  if (value && typeof value === "object")
    return value as Record<string, unknown>;
  return {};
}

// ---------------------------------------------------------------------------
// POST /api/settings/profile-image
// Body: FormData with "file" field
// Returns: { avatarUrl }
// ---------------------------------------------------------------------------

export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();
    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const formData = await request.formData();
    const file = formData.get("file") as File | null;
    if (!file) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "No file provided",
      });
    }

    const extension = ALLOWED_TYPES[file.type];
    if (!extension) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Only JPG, PNG, WEBP, and GIF are allowed",
      });
    }
    if (file.size > MAX_BYTES) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "File must be 5MB or less",
      });
    }

    const s3 = await getS3Credentials(user.id);
    if (!s3) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message:
          "No S3 storage connection configured. Add a platform S3 connection in Storage settings.",
      });
    }
    const key = `avatars/${user.id}/${Date.now()}.${extension}`;
    const upload = await putS3Object({
      credentials: s3,
      key,
      body: await file.arrayBuffer(),
      contentType: file.type,
    });
    if (upload.error) return apiInternalError(request, upload.error);

    // Store as proxy URL so the bucket stays private
    const avatarUrl = `/api/avatar?key=${encodeURIComponent(key)}`;

    const adminClient = createAdminClientIfAvailable();

    // Update users table — use admin client to bypass RLS (admins have no client_id in JWT)
    // Non-fatal: auth metadata update below is the authoritative source
    const dbClient = adminClient ?? supabase;
    const { error: userUpdateError } = await dbClient
      .from("users")
      .update({ avatar: avatarUrl, updated_at: new Date().toISOString() })
      .eq("id", user.id);

    if (userUpdateError) {
      console.warn(
        "Avatar DB update failed (non-fatal):",
        userUpdateError.message,
      );
    }

    // Update auth user metadata — this always works regardless of RLS
    const mergedMetadata = {
      ...asRecord(user.user_metadata),
      avatar: avatarUrl,
    };

    if (adminClient) {
      const { error } = await adminClient.auth.admin.updateUserById(user.id, {
        user_metadata: mergedMetadata,
      });
      if (error) {
        console.warn("Auth metadata update via admin failed:", error.message);
        // Fall back to user-scoped update
        const { error: fallbackError } = await supabase.auth.updateUser({
          data: mergedMetadata,
        });
        if (fallbackError)
          return apiInternalError(request, fallbackError.message);
      }
    } else {
      const { error } = await supabase.auth.updateUser({
        data: mergedMetadata,
      });
      if (error) return apiInternalError(request, error.message);
    }

    return apiSuccess(
      request,
      { avatarUrl },
      { extra: { success: true, avatarUrl } },
    );
  } catch (error) {
    console.error("Error saving profile image:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to save profile image",
    );
  }
}

// ---------------------------------------------------------------------------
// DELETE /api/settings/profile-image
// Removes the avatar from S3, the users table, and auth metadata.
// ---------------------------------------------------------------------------

export async function DELETE(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();
    if (authError || !user) {
      return apiUnauthorized(request);
    }

    // Extract S3 key from stored avatar URL: /api/avatar?key=avatars%2F...
    const currentAvatar =
      (user.user_metadata?.avatar as string | undefined) ?? "";
    const keyParam = currentAvatar.startsWith("/api/avatar?key=")
      ? decodeURIComponent(currentAvatar.replace("/api/avatar?key=", ""))
      : null;

    // Delete from S3 if there's a stored object
    if (keyParam && keyParam.startsWith(`avatars/${user.id}/`)) {
      const s3 = await getS3Credentials(user.id);
      if (s3) {
        await removeS3Object({ credentials: s3, key: keyParam }).catch(
          (err) => {
            console.warn("S3 delete failed (non-fatal):", err);
          },
        );
      }
    }

    // Clear avatar in users table
    const adminClient = createAdminClientIfAvailable();
    const dbClient = adminClient ?? supabase;
    const { error: dbError } = await dbClient
      .from("users")
      .update({ avatar: null, updated_at: new Date().toISOString() })
      .eq("id", user.id);
    if (dbError) {
      console.warn("Avatar DB clear failed (non-fatal):", dbError.message);
    }

    // Clear avatar in auth metadata
    // GoTrue merges metadata updates; omitting the key preserves the old image.
    const metaWithoutAvatar = { ...asRecord(user.user_metadata), avatar: null };
    if (adminClient) {
      const { error } = await adminClient.auth.admin.updateUserById(user.id, {
        user_metadata: metaWithoutAvatar,
      });
      if (error) {
        const { error: fallbackError } = await supabase.auth.updateUser({
          data: metaWithoutAvatar,
        });
        if (fallbackError)
          return apiInternalError(request, fallbackError.message);
      }
    } else {
      const { error } = await supabase.auth.updateUser({
        data: metaWithoutAvatar,
      });
      if (error) return apiInternalError(request, error.message);
    }

    return apiSuccess(request, { deleted: true }, { extra: { success: true } });
  } catch (error) {
    console.error("Error deleting profile image:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to delete profile image",
    );
  }
}
