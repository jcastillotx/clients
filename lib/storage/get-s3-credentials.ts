import { db } from "@/lib/db";
import { storageConnections } from "@/lib/db/schema/additional-features";
import { eq, and } from "drizzle-orm";

export interface S3Credentials {
  accessKeyId: string;
  secretAccessKey: string;
  bucket: string;
  region: string;
}

function extractCreds(conn: typeof storageConnections.$inferSelect): S3Credentials | null {
  try {
    const raw = Buffer.from(conn.credentialsEncrypted, "base64").toString("utf-8");
    const creds = JSON.parse(raw) as Record<string, string>;
    const accessKeyId = creds.accessKeyId;
    const secretAccessKey = creds.secretAccessKey;
    const bucket = conn.config?.bucket;
    const region = conn.config?.region ?? "us-east-1";
    if (accessKeyId && secretAccessKey && bucket) {
      return { accessKeyId, secretAccessKey, bucket, region };
    }
  } catch {
    // ignore parse errors
  }
  return null;
}

/**
 * Load S3 credentials from the company storage connection stored in the DB.
 * Searches all company S3 connections (not scoped to a specific client) so
 * admin users without a clientId still get credentials.
 * Falls back to env vars if no DB connection is found.
 */
export async function getS3Credentials(_userId?: string): Promise<S3Credentials | null> {
  // Find any active company-level S3 connection across all clients
  const connections = await db
    .select()
    .from(storageConnections)
    .where(
      and(
        eq(storageConnections.provider, "s3"),
        eq(storageConnections.ownerType, "company"),
        eq(storageConnections.syncEnabled, true),
      ),
    )
    .limit(5);

  for (const conn of connections) {
    const creds = extractCreds(conn);
    if (creds) return creds;
  }

  // Fallback: env vars
  const accessKeyId = process.env.AWS_ACCESS_KEY_ID;
  const secretAccessKey = process.env.AWS_SECRET_ACCESS_KEY;
  const bucket = process.env.AWS_S3_BUCKET;
  const region = process.env.AWS_REGION ?? "us-east-1";

  if (accessKeyId && secretAccessKey && bucket) {
    return { accessKeyId, secretAccessKey, bucket, region };
  }

  return null;
}
