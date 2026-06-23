import { NextRequest } from "next/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { db } from "@/lib/db";
import { storageConnections } from "@/lib/db/schema/additional-features";
import { eq, and } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { users } from "@/lib/db/schema/users";
import { encrypt } from "@/lib/encryption";

function isAdminUser(
  user: { user_metadata?: Record<string, unknown> },
  dbUser: { isSuperAdmin?: boolean | null },
) {
  const metadataRole = String(
    user?.user_metadata?.role || user?.user_metadata?.app_role || "",
  ).toLowerCase();
  return Boolean(
    dbUser?.isSuperAdmin ||
    user?.user_metadata?.is_super_admin === true ||
    metadataRole === "admin" ||
    metadataRole === "super_admin",
  );
}

export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const searchParams = request.nextUrl.searchParams;
    const clientId = searchParams.get("clientId");
    const ownerType = searchParams.get("ownerType");

    if (!clientId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Client ID is required",
      });
    }

    const [dbUser] = await db
      .select()
      .from(users)
      .where(eq(users.id, user.id))
      .limit(1);
    if (!dbUser) {
      return apiNotFound(request, "User not found");
    }

    const isAdmin = isAdminUser(user, dbUser);
    if (!isAdmin && dbUser.clientId !== clientId) {
      return apiForbidden(request);
    }

    const conditions = [eq(storageConnections.clientId, clientId)];
    if (ownerType === "company" || ownerType === "client") {
      conditions.push(eq(storageConnections.ownerType, ownerType));
    }

    const connections = await db
      .select()
      .from(storageConnections)
      .where(and(...conditions));

    const sanitized = connections.map((conn: (typeof connections)[number]) => ({
      ...conn,
      credentialsEncrypted: undefined,
    }));

    return apiSuccess(request, sanitized);
  } catch (error) {
    console.error("Error fetching storage connections:", error);
    return apiInternalError(request, "Failed to fetch storage connections");
  }
}

export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const body = await request.json();
    const {
      clientId,
      provider,
      ownerType,
      connectionName,
      credentials,
      syncEnabled,
      config,
    } = body;

    if (!clientId || !provider || !connectionName || !credentials) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Missing required fields",
      });
    }

    const [dbUser] = await db
      .select()
      .from(users)
      .where(eq(users.id, user.id))
      .limit(1);
    if (!dbUser) {
      return apiNotFound(request, "User not found");
    }

    const isAdmin = isAdminUser(user, dbUser);

    if (ownerType === "company" && !isAdmin) {
      return apiForbidden(request, "Only admins can manage company storage");
    }

    if (!isAdmin && dbUser.clientId !== clientId) {
      return apiForbidden(request);
    }

    const credentialsEncrypted = encrypt(JSON.stringify(credentials));

    const newConnection = await db
      .insert(storageConnections)
      .values({
        clientId,
        provider,
        ownerType: ownerType || "client",
        connectionName,
        credentialsEncrypted,
        syncEnabled: syncEnabled ?? true,
        config,
      })
      .returning();

    return apiSuccess(
      request,
      {
        ...newConnection[0],
        credentialsEncrypted: undefined,
      },
      { status: 201 },
    );
  } catch (error) {
    console.error("Error creating storage connection:", error);
    return apiInternalError(request, "Failed to create storage connection");
  }
}

export async function DELETE(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const id = request.nextUrl.searchParams.get("id");

    if (!id) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Connection ID is required",
      });
    }

    const [dbUser] = await db
      .select()
      .from(users)
      .where(eq(users.id, user.id))
      .limit(1);
    if (!dbUser) {
      return apiNotFound(request, "User not found");
    }

    const [connection] = await db
      .select()
      .from(storageConnections)
      .where(eq(storageConnections.id, id))
      .limit(1);
    if (!connection) {
      return apiNotFound(request, "Connection not found");
    }

    const isAdmin = isAdminUser(user, dbUser);

    if (connection.ownerType === "company" && !isAdmin) {
      return apiForbidden(request, "Only admins can manage company storage");
    }

    if (!isAdmin && dbUser.clientId !== connection.clientId) {
      return apiForbidden(request);
    }

    await db
      .delete(storageConnections)
      .where(
        and(
          eq(storageConnections.id, id),
          eq(storageConnections.clientId, connection.clientId),
        ),
      );

    return apiSuccess(request, { deleted: true }, { extra: { success: true } });
  } catch (error) {
    console.error("Error deleting storage connection:", error);
    return apiInternalError(request, "Failed to delete storage connection");
  }
}
