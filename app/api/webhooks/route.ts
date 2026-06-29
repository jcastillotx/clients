import crypto from "crypto";
import { NextRequest } from "next/server";
import { and, desc, eq } from "drizzle-orm";
import { z } from "zod";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { db } from "@/lib/db";
import { webhookEndpoints } from "@/lib/db/schema/additional-features";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

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

async function requireUserAccess(request: Request) {
  const supabase = await createClient();
  const {
    data: { user },
    error: authError,
  } = await supabase.auth.getUser();

  if (authError || !user) {
    return { error: apiUnauthorized(request) };
  }

  const access = await resolveRouteAccess(supabase, user);
  return { user, access };
}

export async function GET(request: NextRequest) {
  try {
    const auth = await requireUserAccess(request);
    if ("error" in auth) {
      return auth.error;
    }

    const rawClientId = request.nextUrl.searchParams.get("clientId");
    const adminAll = request.nextUrl.searchParams.get("adminAll") === "true";

    // Admin users may request all webhooks across clients
    if (adminAll || !rawClientId) {
      if (!auth.access.isAdmin) {
        return apiForbidden(request);
      }
      const endpoints = await db
        .select()
        .from(webhookEndpoints)
        .orderBy(desc(webhookEndpoints.createdAt));
      const sanitized = endpoints.map((endpoint: (typeof endpoints)[number]) => ({
        ...endpoint,
        secret: undefined,
      }));
      return apiSuccess(request, sanitized);
    }

    const parsed = querySchema.safeParse({ clientId: rawClientId });
    if (!parsed.success) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Client ID is required",
      });
    }

    const { clientId } = parsed.data;
    if (!canAccessClient(auth.access, clientId)) {
      return apiForbidden(request);
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

    return apiSuccess(request, sanitized);
  } catch (error) {
    console.error("Error fetching webhooks:", error);
    return apiInternalError(request, "Failed to fetch webhooks");
  }
}

export async function POST(request: NextRequest) {
  try {
    const auth = await requireUserAccess(request);
    if ("error" in auth) {
      return auth.error;
    }

    const body = await request.json();
    const parsed = createSchema.safeParse(body);
    if (!parsed.success) {
      return apiValidationError(request, parsed.error);
    }

    const { clientId, url, events, isActive, retryConfig } = parsed.data;
    if (!canAccessClient(auth.access, clientId)) {
      return apiForbidden(request);
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

    return apiSuccess(
      request,
      {
        ...newEndpoint[0],
        secret: undefined,
      },
      { status: 201 },
    );
  } catch (error) {
    console.error("Error creating webhook:", error);
    return apiInternalError(request, "Failed to create webhook");
  }
}

export async function PATCH(request: NextRequest) {
  try {
    const auth = await requireUserAccess(request);
    if ("error" in auth) {
      return auth.error;
    }

    const body = await request.json();
    const parsed = updateSchema.safeParse(body);
    if (!parsed.success) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Webhook ID is required",
      });
    }

    const { id, url, events, isActive, retryConfig } = parsed.data;
    const [existing] = await db
      .select()
      .from(webhookEndpoints)
      .where(eq(webhookEndpoints.id, id))
      .limit(1);

    if (!existing) {
      return apiNotFound(request, "Webhook not found");
    }

    if (!canAccessClient(auth.access, existing.clientId)) {
      return apiForbidden(request);
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

    return apiSuccess(request, {
      ...updated[0],
      secret: undefined,
    });
  } catch (error) {
    console.error("Error updating webhook:", error);
    return apiInternalError(request, "Failed to update webhook");
  }
}

export async function DELETE(request: NextRequest) {
  try {
    const auth = await requireUserAccess(request);
    if ("error" in auth) {
      return auth.error;
    }

    const parsed = deleteSchema.safeParse({ id: request.nextUrl.searchParams.get("id") });
    if (!parsed.success) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Webhook ID is required",
      });
    }

    const { id } = parsed.data;
    const [existing] = await db
      .select()
      .from(webhookEndpoints)
      .where(eq(webhookEndpoints.id, id))
      .limit(1);
    if (!existing) {
      return apiNotFound(request, "Webhook not found");
    }

    if (!canAccessClient(auth.access, existing.clientId)) {
      return apiForbidden(request);
    }

    await db
      .delete(webhookEndpoints)
      .where(
        and(
          eq(webhookEndpoints.id, id),
          eq(webhookEndpoints.clientId, existing.clientId),
        ),
      );

    return apiSuccess(request, { deleted: true }, { extra: { success: true } });
  } catch (error) {
    console.error("Error deleting webhook:", error);
    return apiInternalError(request, "Failed to delete webhook");
  }
}
