import { db } from "@/lib/db";
import { storageConnections } from "@/lib/db/schema/additional-features";
import { users } from "@/lib/db/schema/users";
import { eq, and } from "drizzle-orm";

export interface S3Credentials {
  accessKeyId: string;
  secretAccessKey: string;
  bucket: string;
  region: string;
}

/**
 * Load S3 credentials from the company storage connection stored in the DB.
 * Looks up the user's clientId, then finds an active company-level S3 connection.
 * Falls back to env vars if no DB connection is found.
 */
export async function getS3Credentials(userId: string): Promise<S3Credentials | null> {
  // 1. Get the user's clientId
  const [dbUser] = await db.select({ clientId: users.clientId }).from(users).where(eq(users.id, userId)).limit(1);
  const clientId = dbUser?.clientId;

  if (clientId) {
    // 2. Find an active company S3 connection for this client
    const [conn] = await db
      .select()
      .from(storageConnections)
      .where(
        and(
          eq(storageConnections.clientId, clientId),
          eq(storageConnections.provider, "s3"),
          eq(storageConnections.ownerType, "company"),
          eq(storageConnections.syncEnabled, true),
        ),
      )
      .limit(1);

    if (conn) {
      try {
        // Credentials stored as base64-encoded JSON
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
        // Fall through to env vars
      }
    }
  }

  // 3. Fallback: env vars
  const accessKeyId = process.env.AWS_ACCESS_KEY_ID;
  const secretAccessKey = process.env.AWS_SECRET_ACCESS_KEY;
  const bucket = process.env.AWS_S3_BUCKET;
  const region = process.env.AWS_REGION ?? "us-east-1";

  if (accessKeyId && secretAccessKey && bucket) {
    return { accessKeyId, secretAccessKey, bucket, region };
  }

  return null;
}
