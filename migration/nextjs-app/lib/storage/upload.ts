import { createClient } from "@/lib/supabase/server";

export interface UploadOptions {
  bucket: string;
  path: string;
  file: File;
  upsert?: boolean;
  cacheControl?: string;
}

export interface UploadResult {
  path: string;
  publicUrl?: string;
  error?: string;
}

/**
 * Upload a file to Supabase Storage
 */
export async function uploadFile({
  bucket,
  path,
  file,
  upsert = false,
  cacheControl = "3600",
}: UploadOptions): Promise<UploadResult> {
  try {
    const supabase = createClient();

    // Upload the file
    const { data, error } = await supabase.storage.from(bucket).upload(path, file, {
      cacheControl,
      upsert,
    });

    if (error) {
      return { path: "", error: error.message };
    }

    // Get public URL if bucket is public
    const { data: urlData } = supabase.storage.from(bucket).getPublicUrl(data.path);

    return {
      path: data.path,
      publicUrl: urlData.publicUrl,
    };
  } catch (error) {
    return {
      path: "",
      error: error instanceof Error ? error.message : "Upload failed",
    };
  }
}

/**
 * Upload a file with resumable upload for large files
 */
export async function uploadLargeFile(
  options: UploadOptions,
  onProgress?: (progress: number) => void,
): Promise<UploadResult> {
  try {
    const { bucket, path, file } = options;
    const supabase = createClient();

    const chunkSize = 5 * 1024 * 1024; // 5MB chunks
    const totalChunks = Math.ceil(file.size / chunkSize);
    const uploadedChunks: string[] = [];

    for (let i = 0; i < totalChunks; i++) {
      const start = i * chunkSize;
      const end = Math.min(start + chunkSize, file.size);
      const chunk = file.slice(start, end);

      const chunkPath = `${path}.part${i}`;

      const { data, error } = await supabase.storage.from(bucket).upload(chunkPath, chunk, {
        upsert: true,
      });

      if (error) {
        // Clean up uploaded chunks on error
        for (const uploadedPath of uploadedChunks) {
          await supabase.storage.from(bucket).remove([uploadedPath]);
        }
        return { path: "", error: error.message };
      }

      uploadedChunks.push(data.path);

      if (onProgress) {
        onProgress((i + 1) / totalChunks);
      }
    }

    // Combine chunks (this would need to be implemented server-side)
    // For now, we'll use the regular upload for files under 50MB
    if (file.size < 50 * 1024 * 1024) {
      return uploadFile(options);
    }

    return {
      path: path,
      error: "Large file upload needs server-side chunk combining",
    };
  } catch (error) {
    return {
      path: "",
      error: error instanceof Error ? error.message : "Upload failed",
    };
  }
}

/**
 * Delete a file from Supabase Storage
 */
export async function deleteFile(bucket: string, path: string): Promise<{ error?: string }> {
  try {
    const supabase = createClient();

    const { error } = await supabase.storage.from(bucket).remove([path]);

    if (error) {
      return { error: error.message };
    }

    return {};
  } catch (error) {
    return {
      error: error instanceof Error ? error.message : "Delete failed",
    };
  }
}

/**
 * Get a signed URL for private file access
 */
export async function getSignedUrl(
  bucket: string,
  path: string,
  expiresIn = 3600,
): Promise<{ url?: string; error?: string }> {
  try {
    const supabase = createClient();

    const { data, error } = await supabase.storage.from(bucket).createSignedUrl(path, expiresIn);

    if (error) {
      return { error: error.message };
    }

    return { url: data.signedUrl };
  } catch (error) {
    return {
      error: error instanceof Error ? error.message : "Failed to get signed URL",
    };
  }
}

/**
 * Download a file from Supabase Storage
 */
export async function downloadFile(bucket: string, path: string): Promise<{ data?: Blob; error?: string }> {
  try {
    const supabase = createClient();

    const { data, error } = await supabase.storage.from(bucket).download(path);

    if (error) {
      return { error: error.message };
    }

    return { data };
  } catch (error) {
    return {
      error: error instanceof Error ? error.message : "Download failed",
    };
  }
}

/**
 * List files in a storage bucket path
 */
export async function listFiles(
  bucket: string,
  path?: string,
  options?: {
    limit?: number;
    offset?: number;
    sortBy?: { column: string; order: "asc" | "desc" };
  },
) {
  try {
    const supabase = createClient();

    const { data, error } = await supabase.storage.from(bucket).list(path, options);

    if (error) {
      return { data: [], error: error.message };
    }

    return { data, error: null };
  } catch (error) {
    return {
      data: [],
      error: error instanceof Error ? error.message : "Failed to list files",
    };
  }
}

/**
 * Storage bucket names
 */
export const StorageBuckets = {
  DOCUMENTS: "documents",
  CONTRACTS: "contracts",
  AVATARS: "avatars",
  INVOICES: "invoices",
} as const;

/**
 * Generate a unique file path
 */
export function generateFilePath(clientId: string, folder: string, fileName: string): string {
  const timestamp = Date.now();
  const randomString = Math.random().toString(36).substring(7);
  const sanitizedFileName = fileName.replace(/[^a-zA-Z0-9.-]/g, "_");

  return `${clientId}/${folder}/${timestamp}-${randomString}-${sanitizedFileName}`;
}

/**
 * Validate file type
 */
export function validateFileType(file: File, allowedTypes: string[]): boolean {
  return allowedTypes.some((type) => {
    if (type.endsWith("/*")) {
      const baseType = type.split("/")[0];
      return file.type.startsWith(baseType + "/");
    }
    return file.type === type;
  });
}

/**
 * Format file size for display
 */
export function formatFileSize(bytes: number): string {
  if (bytes === 0) return "0 Bytes";

  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
}
