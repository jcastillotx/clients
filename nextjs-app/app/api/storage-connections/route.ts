import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { storageConnections } from "@/lib/db/schema/additional-features";
import { eq } from "drizzle-orm";

/**
 * GET /api/storage-connections
 * Retrieve storage connections for a client
 */
export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;
    const clientId = searchParams.get("clientId");

    if (!clientId) {
      return NextResponse.json({ error: "Client ID is required" }, { status: 400 });
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
    const body = await request.json();
    const { clientId, provider, connectionName, credentials, syncEnabled, config } = body;

    if (!clientId || !provider || !connectionName || !credentials) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
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
    const searchParams = request.nextUrl.searchParams;
    const id = searchParams.get("id");

    if (!id) {
      return NextResponse.json({ error: "Connection ID is required" }, { status: 400 });
    }

    await db.delete(storageConnections).where(eq(storageConnections.id, id));

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting storage connection:", error);
    return NextResponse.json({ error: "Failed to delete storage connection" }, { status: 500 });
  }
}
