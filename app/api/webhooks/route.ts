import crypto from "crypto";
import { NextRequest, NextResponse } from "next/server";
import { and, desc, eq } from "drizzle-orm";
import { z } from "zod";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { db } from "@/lib/db";
import { webhookEndpoints } from "@/lib/db/schema/additional-features";
import { createClient } from "@/lib/supabase/server";

const querySchema = z.object({
  clientId: z.string().uuid(),
});

const webhookEventSchema = z.enum([
  "client.created",
  "client.updated",
  "invoice.created",
  "invoice.paid",
  "project.completed",
  "ticket.created",
  "ticket.resolved",
  "user.created",
]);

const createSchema = z.object({
  clientId: z.string().uuid(),
  url: z.string().url(),
  events: z.array(webhookEventSchema).min(1),
  isActive: z.boolean().optional(),
  retryConfig: z
    .object({
      maxAttempts: z.number().optional(),
      backoffMultiplier: z.number().optional(),
      initialDelay: z.number().optional(),
    })
    .optional(),
});

const updateSchema = z.object({
  id: z.string().uuid(),
  url: z.string().url().optional(),
  events: z.array(webhookEventSchema).optional(),
  isActive: z.boolean().optional(),
  retryConfig: z
    .object({
      maxAttempts: z.number().optional(),
      backoffMultiplier: z.number().optional(),
      initialDelay: z.number().optional(),
    })
    .optional(),
});

const deleteSchema = z.object({
  id: z.string().uuid(),
});

async function requireUserAccess() {
  const supabase = await createClient();
  const {
    data: { user },
    error: authError,
  } = await supabase.auth.getUser();

  if (authError || !user) {
    return {
      error: NextResponse.json({ error: "Unauthorized" }, { status: 401 }),
    };
  }

  const access = await resolveRouteAccess(supabase, user);
  return { user, access };
}

/**
 * GET /api/webhooks
 * Retrieve webhook endpoints for a client
 */
export async function GET(request: NextRequest) {
  try {
    const auth = await requireUserAccess();
    if ("error" in auth) {
      return auth.error;
    }

    const searchParams = request.nextUrl.searchParams;
    const parsed = querySchema.safeParse({
      clientId: searchParams.get("clientId"),
    });
    if (!parsed.success) {
      return NextResponse.json(
        { error: "Client ID is required" },
        { status: 400 },
      );
    }

    const { clientId } = parsed.data;
    if (!canAccessClient(auth.access, clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const endpoints = await db
      .select()
      .from(webhookEndpoints)
      .where(eq(webhookEndpoints.clientId, clientId))
      .orderBy(desc(webhookEndpoints.createdAt));

    const sanitized = endpoints.map((endpoint: (typeof endpoints)[number]) => ({
      ...endpoint,
      secret: undefined,
    }));

    return NextResponse.json(sanitized);
  } catch (error) {
    console.error("Error fetching webhooks:", error);
    return NextResponse.json(
      { error: "Failed to fetch webhooks" },
      { status: 500 },
    );
  }
}

/**
 * POST /api/webhooks
 * Create a new webhook endpoint
 */
export async function POST(request: NextRequest) {
  try {
    const auth = await requireUserAccess();
    if ("error" in auth) {
      return auth.error;
    }

    const body = await request.json();
    const parsed = createSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json(
        { error: "Missing required fields" },
        { status: 400 },
      );
    }

    const { clientId, url, events, isActive, retryConfig } = parsed.data;
    if (!canAccessClient(auth.access, clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

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
        createdBy: auth.user.id,
      })
      .returning();

    return NextResponse.json(
      {
        ...newEndpoint[0],
        secret: undefined,
      },
      { status: 201 },
    );
  } catch (error) {
    console.error("Error creating webhook:", error);
    return NextResponse.json(
      { error: "Failed to create webhook" },
      { status: 500 },
    );
  }
}

/**
 * PATCH /api/webhooks
 * Update a webhook endpoint
 */
export async function PATCH(request: NextRequest) {
  try {
    const auth = await requireUserAccess();
    if ("error" in auth) {
      return auth.error;
    }

    const body = await request.json();
    const parsed = updateSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json(
        { error: "Webhook ID is required" },
        { status: 400 },
      );
    }

    const { id, url, events, isActive, retryConfig } = parsed.data;
    const [existing] = await db
      .select()
      .from(webhookEndpoints)
      .where(eq(webhookEndpoints.id, id))
      .limit(1);

    if (!existing) {
      return NextResponse.json({ error: "Webhook not found" }, { status: 404 });
    }

    if (!canAccessClient(auth.access, existing.clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
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
      .where(
        and(
          eq(webhookEndpoints.id, id),
          eq(webhookEndpoints.clientId, existing.clientId),
        ),
      )
      .returning();

    return NextResponse.json({
      ...updated[0],
      secret: undefined,
    });
  } catch (error) {
    console.error("Error updating webhook:", error);
    return NextResponse.json(
      { error: "Failed to update webhook" },
      { status: 500 },
    );
  }
}

/**
 * DELETE /api/webhooks
 * Delete a webhook endpoint
 */
export async function DELETE(request: NextRequest) {
  try {
    const auth = await requireUserAccess();
    if ("error" in auth) {
      return auth.error;
    }

    const searchParams = request.nextUrl.searchParams;
    const parsed = deleteSchema.safeParse({ id: searchParams.get("id") });
    if (!parsed.success) {
      return NextResponse.json(
        { error: "Webhook ID is required" },
        { status: 400 },
      );
    }

    const { id } = parsed.data;
    const [existing] = await db
      .select()
      .from(webhookEndpoints)
      .where(eq(webhookEndpoints.id, id))
      .limit(1);
    if (!existing) {
      return NextResponse.json({ error: "Webhook not found" }, { status: 404 });
    }

    if (!canAccessClient(auth.access, existing.clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    await db
      .delete(webhookEndpoints)
      .where(
        and(
          eq(webhookEndpoints.id, id),
          eq(webhookEndpoints.clientId, existing.clientId),
        ),
      );

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting webhook:", error);
    return NextResponse.json(
      { error: "Failed to delete webhook" },
      { status: 500 },
    );
  }
}
