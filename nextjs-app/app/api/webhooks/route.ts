import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { webhookEndpoints, webhookDeliveries } from "@/lib/db/schema/additional-features";
import { eq, desc } from "drizzle-orm";
import crypto from "crypto";

/**
 * GET /api/webhooks
 * Retrieve webhook endpoints for a client
 */
export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;
    const clientId = searchParams.get("clientId");

    if (!clientId) {
      return NextResponse.json({ error: "Client ID is required" }, { status: 400 });
    }

    const endpoints = await db
      .select()
      .from(webhookEndpoints)
      .where(eq(webhookEndpoints.clientId, clientId))
      .orderBy(desc(webhookEndpoints.createdAt));

    // Exclude secret from response
    const sanitized = endpoints.map((endpoint) => ({
      ...endpoint,
      secret: undefined,
    }));

    return NextResponse.json(sanitized);
  } catch (error) {
    console.error("Error fetching webhooks:", error);
    return NextResponse.json({ error: "Failed to fetch webhooks" }, { status: 500 });
  }
}

/**
 * POST /api/webhooks
 * Create a new webhook endpoint
 */
export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { clientId, url, events, isActive, retryConfig, createdBy } = body;

    if (!clientId || !url || !events || events.length === 0) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    // Generate a secure secret for HMAC signature
    const secret = crypto.randomBytes(32).toString("hex");

    const newEndpoint = await db
      .insert(webhookEndpoints)
      .values({
        clientId,
        url,
        secret,
        events,
        isActive: isActive ?? true,
        retryConfig: retryConfig || {
          maxAttempts: 3,
          backoffMultiplier: 2,
          initialDelay: 5,
        },
        createdBy: createdBy || null,
      })
      .returning();

    return NextResponse.json(
      {
        ...newEndpoint[0],
        secret: undefined, // Don't return the secret
      },
      { status: 201 },
    );
  } catch (error) {
    console.error("Error creating webhook:", error);
    return NextResponse.json({ error: "Failed to create webhook" }, { status: 500 });
  }
}

/**
 * PATCH /api/webhooks
 * Update a webhook endpoint
 */
export async function PATCH(request: NextRequest) {
  try {
    const body = await request.json();
    const { id, url, events, isActive, retryConfig } = body;

    if (!id) {
      return NextResponse.json({ error: "Webhook ID is required" }, { status: 400 });
    }

    const updated = await db
      .update(webhookEndpoints)
      .set({
        url,
        events,
        isActive,
        retryConfig,
        updatedAt: new Date(),
      })
      .where(eq(webhookEndpoints.id, id))
      .returning();

    return NextResponse.json({
      ...updated[0],
      secret: undefined,
    });
  } catch (error) {
    console.error("Error updating webhook:", error);
    return NextResponse.json({ error: "Failed to update webhook" }, { status: 500 });
  }
}

/**
 * DELETE /api/webhooks
 * Delete a webhook endpoint
 */
export async function DELETE(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;
    const id = searchParams.get("id");

    if (!id) {
      return NextResponse.json({ error: "Webhook ID is required" }, { status: 400 });
    }

    await db.delete(webhookEndpoints).where(eq(webhookEndpoints.id, id));

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting webhook:", error);
    return NextResponse.json({ error: "Failed to delete webhook" }, { status: 500 });
  }
}
