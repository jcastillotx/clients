import { NextRequest } from "next/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { db } from "@/lib/db";
import { dataPrivacyRequests } from "@/lib/db/schema/additional-features";
import { eq, desc, and } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { resolveRouteAccess } from "@/lib/auth/route-access";

export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const searchParams = request.nextUrl.searchParams;
    const clientId = searchParams.get("clientId");
    const userId = searchParams.get("userId");
    const adminAll = searchParams.get("adminAll") === "true";

    // Admin users may request all privacy requests across clients
    if (adminAll || (!clientId && !userId)) {
      const access = await resolveRouteAccess(supabase, user);
      if (!access.isAdmin) {
        return adminAll
          ? apiForbidden(request)
          : apiError(request, {
              status: 400,
              code: "BAD_REQUEST",
              message: "Client ID or User ID is required",
            });
      }
      const rows = await db
        .select()
        .from(dataPrivacyRequests)
        .orderBy(desc(dataPrivacyRequests.requestedAt));
      return apiSuccess(request, rows);
    }

    const conditions = [];
    if (clientId) {
      conditions.push(eq(dataPrivacyRequests.clientId, clientId));
    }
    if (userId) {
      conditions.push(eq(dataPrivacyRequests.userId, userId));
    }

    const rows = await db
      .select()
      .from(dataPrivacyRequests)
      .where(conditions.length > 0 ? (conditions.length > 1 ? and(...conditions) : conditions[0]) : undefined)
      .orderBy(desc(dataPrivacyRequests.requestedAt));

    return apiSuccess(request, rows);
  } catch (error) {
    console.error("Error fetching privacy requests:", error);
    return apiInternalError(request, "Failed to fetch privacy requests");
  }
}

export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const body = await request.json();
    const { clientId, userId, requestType, notes } = body;

    if (!clientId || !requestType) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Missing required fields",
      });
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

    return apiSuccess(request, newRequest[0], { status: 201 });
  } catch (error) {
    console.error("Error creating privacy request:", error);
    return apiInternalError(request, "Failed to create privacy request");
  }
}

export async function PATCH(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const body = await request.json();
    const { id, status, dataExportUrl, notes } = body;

    if (!id || !status) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Missing required fields",
      });
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

    return apiSuccess(request, updated[0]);
  } catch (error) {
    console.error("Error updating privacy request:", error);
    return apiInternalError(request, "Failed to update privacy request");
  }
}
