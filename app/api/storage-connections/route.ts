import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { storageConnections } from "@/lib/db/schema/additional-features";
import { eq } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { users } from "@/lib/db/schema/users";
import { and } from "drizzle-orm";

function isAdminUser(user: any, dbUser: any) {
  const metadataRole = String(user?.user_metadata?.role || user?.user_metadata?.app_role || "").toLowerCase();
  return Boolean(
    dbUser?.isSuperAdmin ||
      user?.user_metadata?.is_super_admin === true ||
      metadataRole === "admin" ||
      metadataRole === "super_admin",
  );
}

/**
 * GET /api/storage-connections
 * Retrieve storage connections for a client
 */
export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const searchParams = request.nextUrl.searchParams;
    const clientId = searchParams.get("clientId");

    if (!clientId) {
      return NextResponse.json({ error: "Client ID is required" }, { status: 400 });
    }

    const [dbUser] = await db.select().from(users).where(eq(users.id, user.id)).limit(1);
    if (!dbUser) {
      return NextResponse.json({ error: "User not found" }, { status: 404 });
    }

    const isAdmin = isAdminUser(user, dbUser);
    if (!isAdmin && dbUser.clientId !== clientId) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const connections = await db.select().from(storageConnections).where(eq(storageConnections.clientId, clientId));

    // Exclude encrypted credentials from response
    const sanitized = connections.map((conn) => ({
      ...conn,
      credentialsEncrypted: undefined,
    }));

    return NextResponse.json(sanitized);
  } catch (error) {
    console.error("Error fetching storage connections:", error);
    return NextResponse.json({ error: "Failed to fetch storage connections" }, { status: 500 });
  }
}

/**
 * POST /api/storage-connections
 * Create a new storage connection
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const body = await request.json();
    const { clientId, provider, connectionName, credentials, syncEnabled, config } = body;

    if (!clientId || !provider || !connectionName || !credentials) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    const [dbUser] = await db.select().from(users).where(eq(users.id, user.id)).limit(1);
    if (!dbUser) {
      return NextResponse.json({ error: "User not found" }, { status: 404 });
    }

    const isAdmin = isAdminUser(user, dbUser);
    if (!isAdmin && dbUser.clientId !== clientId) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    // TODO: Implement encryption for credentials
    const credentialsEncrypted = Buffer.from(JSON.stringify(credentials)).toString("base64");

    const newConnection = await db
      .insert(storageConnections)
      .values({
        clientId,
        provider,
        connectionName,
        credentialsEncrypted,
        syncEnabled: syncEnabled ?? true,
        config,
      })
      .returning();

    return NextResponse.json(
      {
        ...newConnection[0],
        credentialsEncrypted: undefined,
      },
      { status: 201 },
    );
  } catch (error) {
    console.error("Error creating storage connection:", error);
    return NextResponse.json({ error: "Failed to create storage connection" }, { status: 500 });
  }
}

/**
 * DELETE /api/storage-connections
 * Delete a storage connection
 */
export async function DELETE(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const searchParams = request.nextUrl.searchParams;
    const id = searchParams.get("id");

    if (!id) {
      return NextResponse.json({ error: "Connection ID is required" }, { status: 400 });
    }

    const [dbUser] = await db.select().from(users).where(eq(users.id, user.id)).limit(1);
    if (!dbUser) {
      return NextResponse.json({ error: "User not found" }, { status: 404 });
    }

    const [connection] = await db.select().from(storageConnections).where(eq(storageConnections.id, id)).limit(1);
    if (!connection) {
      return NextResponse.json({ error: "Connection not found" }, { status: 404 });
    }

    const isAdmin = isAdminUser(user, dbUser);
    if (!isAdmin && dbUser.clientId !== connection.clientId) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    await db.delete(storageConnections).where(and(eq(storageConnections.id, id), eq(storageConnections.clientId, connection.clientId)));

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting storage connection:", error);
    return NextResponse.json({ error: "Failed to delete storage connection" }, { status: 500 });
  }
}
