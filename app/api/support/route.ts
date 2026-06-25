import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { getAuthBaseUrl } from "@/lib/supabase/redirect-url";
import { dispatchNotification } from "@/lib/notifications/service";
import { withPlatformNotificationEmails } from "@/lib/notifications/platform-email";
import { createSupportTicketSchema } from "@/lib/validations/support-ticket";
import { buildWebsiteSupportTriage } from "@/lib/support/website-ticket-triage";
import { calculateSlaDueDates } from "@/lib/utils/sla";
import { NextRequest } from "next/server";
import { z } from "zod";

/**
 * GET /api/support
 *
 * Fetch all support tickets for the authenticated user's client
 */
export async function GET(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  const searchParams = req.nextUrl.searchParams;
  const search = searchParams.get("search");
  const status = searchParams.get("status");
  const priority = searchParams.get("priority");
  const category = searchParams.get("category");
  const sortBy = searchParams.get("sortBy") || "created_at";
  const sortOrder = searchParams.get("sortOrder") === "asc" ? "asc" : "desc";

  let query = supabase
    .from("support_tickets")
    .select(
      `
      *,
      client:clients(company_name),
      creator:users!support_tickets_created_by_fkey(name, avatar),
      assigned_user:users!support_tickets_assigned_to_fkey(name, avatar)
    `,
    )
    .is("deleted_at", null)
    .order(sortBy, { ascending: sortOrder === "asc" });

  if (search) {
    query = query.or(`subject.ilike.%${search}%,ticket_number.ilike.%${search}%`);
  }

  if (status) {
    query = query.eq("status", status);
  }

  if (priority) {
    query = query.eq("priority", priority);
  }

  if (category) {
    query = query.eq("category", category);
  }

  const { data, error } = await query;

  if (error) {
    console.error("Error fetching tickets:", error);
    return apiInternalError(req, error.message);
  }

  const rows = data ?? [];

  return apiSuccess(req, rows, { extra: { tickets: rows } });
}

/**
 * POST /api/support
 *
 * Create a new support ticket
 */
export async function POST(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  const body = await req.json();

  try {
    const validatedData = createSupportTicketSchema.parse(body);

    const { data: dbUser, error: dbUserError } = await supabase
      .from("users")
      .select("id, client_id")
      .eq("id", user.id)
      .maybeSingle();

    if (dbUserError) {
      console.error("Error loading user profile for support ticket creation:", dbUserError);
      return apiInternalError(req, "Failed to load user profile");
    }

    const clientId = dbUser?.client_id;
    if (!clientId) {
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "User not associated with a client",
      });
    }

    const ticketNumber = await generateTicketNumber();
    const now = new Date();
    const slaDates = calculateSlaDueDates(validatedData.priority, now);
    const websiteSupport = validatedData.metadata?.customFields?.websiteSupport;
    const websiteSupportTriage = websiteSupport?.isWebsiteSupport
      ? buildWebsiteSupportTriage({
          subject: validatedData.subject,
          description: validatedData.description,
          priority: validatedData.priority,
          intake: websiteSupport,
        })
      : null;
    const metadata = {
      ...(validatedData.metadata || {}),
      customFields: {
        ...(validatedData.metadata?.customFields || {}),
        ...(websiteSupportTriage ? { websiteSupportTriage } : {}),
      },
    };

    const { data, error } = await supabase
      .from("support_tickets")
      .insert({
        client_id: clientId,
        created_by: user.id,
        assigned_to: validatedData.assignedTo || null,
        ticket_number: ticketNumber,
        subject: validatedData.subject,
        description: validatedData.description,
        category: validatedData.category,
        priority: validatedData.priority,
        status: "open",
        metadata,
        sla_response_due_at: slaDates.slaResponseDueAt.toISOString(),
        sla_resolution_due_at: slaDates.slaResolutionDueAt.toISOString(),
      })
      .select(
        `
        *,
        client:clients(company_name),
        creator:users!support_tickets_created_by_fkey(name, avatar),
        assigned_user:users!support_tickets_assigned_to_fkey(name, avatar)
      `,
      )
      .single();

    if (error) {
      console.error("Error creating ticket:", error);
      return apiInternalError(req, error.message);
    }

    try {
      const base = getAuthBaseUrl();
      await dispatchNotification({
        eventType: "support_ticket_created",
        clientId,
        subjectType: "support_ticket",
        subjectId: data.id,
        actorUserId: user.id,
        recipientUserIds: validatedData.assignedTo ? [validatedData.assignedTo] : [],
        extraEmails: await withPlatformNotificationEmails(),
        data: {
          ticketSubject: data.subject,
          ticketNumber: data.ticket_number,
          ticketPriority: data.priority,
          ticketCategory: data.category,
          ticketUrl: `${base}/support/${data.id}`,
        },
      });
    } catch (notifyErr) {
      console.error("[POST /api/support] notification dispatch:", notifyErr);
    }

    return apiSuccess(req, data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }

    console.error("Unexpected error:", error);
    return apiInternalError(req, "Internal server error");
  }
}

async function generateTicketNumber(): Promise<string> {
  const prefix = "TKT-";
  const date = new Date().toISOString().slice(0, 10).replace(/-/g, "");
  const uniquePart = crypto.randomUUID().substring(0, 8).toUpperCase();

  return `${prefix}${date}-${uniquePart}`;
}
