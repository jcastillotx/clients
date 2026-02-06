import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { dataPrivacyRequests } from "@/lib/db/schema/additional-features";
import { eq, desc } from "drizzle-orm";

/**
 * GET /api/privacy-requests
 * Retrieve data privacy requests for a client
 */
export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;
    const clientId = searchParams.get("clientId");
    const userId = searchParams.get("userId");

    if (!clientId && !userId) {
      return NextResponse.json({ error: "Client ID or User ID is required" }, { status: 400 });
    }

    let query = db.select().from(dataPrivacyRequests);

    if (clientId) {
      query = query.where(eq(dataPrivacyRequests.clientId, clientId));
    }

    if (userId) {
      query = query.where(eq(dataPrivacyRequests.userId, userId));
    }

    const requests = await query.orderBy(desc(dataPrivacyRequests.requestedAt));

    return NextResponse.json(requests);
  } catch (error) {
    console.error("Error fetching privacy requests:", error);
    return NextResponse.json({ error: "Failed to fetch privacy requests" }, { status: 500 });
  }
}

/**
 * POST /api/privacy-requests
 * Create a new data privacy request
 */
export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { clientId, userId, requestType, notes } = body;

    if (!clientId || !requestType) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    const newRequest = await db
      .insert(dataPrivacyRequests)
      .values({
        clientId,
        userId: userId || null,
        requestType,
        status: "pending",
        notes,
      })
      .returning();

    return NextResponse.json(newRequest[0], { status: 201 });
  } catch (error) {
    console.error("Error creating privacy request:", error);
    return NextResponse.json({ error: "Failed to create privacy request" }, { status: 500 });
  }
}

/**
 * PATCH /api/privacy-requests
 * Update a privacy request status
 */
export async function PATCH(request: NextRequest) {
  try {
    const body = await request.json();
    const { id, status, dataExportUrl, notes } = body;

    if (!id || !status) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    const updated = await db
      .update(dataPrivacyRequests)
      .set({
        status,
        dataExportUrl,
        notes,
        completedAt: status === "completed" ? new Date() : null,
        updatedAt: new Date(),
      })
      .where(eq(dataPrivacyRequests.id, id))
      .returning();

    return NextResponse.json(updated[0]);
  } catch (error) {
    console.error("Error updating privacy request:", error);
    return NextResponse.json({ error: "Failed to update privacy request" }, { status: 500 });
  }
}
