import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { createProposalSchema, updateProposalSchema } from "@/lib/validations/proposal";
import { NextRequest } from "next/server";
import { z } from "zod";

/**
 * GET /api/proposals
 *
 * Fetch all proposals for the authenticated user's client
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
  const sortBy = searchParams.get("sortBy") || "created_at";
  const sortOrder = searchParams.get("sortOrder") === "asc" ? "asc" : "desc";

  let query = supabase
    .from("proposals")
    .select(
      `
      *,
      client:clients(id, company_name),
      creator:users!proposals_created_by_fkey(id, name)
    `,
    )
    .order(sortBy, { ascending: sortOrder === "asc" });

  if (search) {
    query = query.or(`title.ilike.%${search}%,description.ilike.%${search}%`);
  }

  if (status && status !== "all") {
    query = query.eq("status", status);
  }

  const { data, error } = await query;

  if (error) {
    console.error("Error fetching proposals:", error);
    return apiInternalError(req, error.message);
  }

  const rows = data ?? [];

  return apiSuccess(req, rows, { extra: { proposals: rows } });
}

/**
 * POST /api/proposals
 *
 * Create a new proposal
 */
export async function POST(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  try {
    const body = await req.json();
    const validatedData = createProposalSchema.parse(body);

    const { data, error } = await supabase
      .from("proposals")
      .insert({
        client_id: validatedData.clientId,
        title: validatedData.title,
        description: validatedData.description || null,
        status: "draft",
        total_amount: validatedData.totalAmount,
        currency: validatedData.currency,
        valid_until: validatedData.validUntil || null,
        terms: validatedData.terms || null,
        line_items: validatedData.lineItems,
        metadata: validatedData.metadata || null,
        created_by: user.id,
      })
      .select()
      .single();

    if (error) {
      throw error;
    }

    return apiSuccess(req, data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }
    console.error("Error creating proposal:", error);
    return apiInternalError(
      req,
      error instanceof Error ? error.message : "Failed to create proposal",
    );
  }
}

/**
 * PATCH /api/proposals/:id
 *
 * Update a proposal
 */
export async function PATCH(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  try {
    const body = await req.json();
    const { id, ...updates } = body;
    const validatedData = updateProposalSchema.parse(updates);

    const { data, error } = await supabase
      .from("proposals")
      .update(validatedData)
      .eq("id", id)
      .select()
      .single();

    if (error) {
      throw error;
    }

    return apiSuccess(req, data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }
    console.error("Error updating proposal:", error);
    return apiInternalError(
      req,
      error instanceof Error ? error.message : "Failed to update proposal",
    );
  }
}

/**
 * DELETE /api/proposals/:id
 *
 * Delete a proposal
 */
export async function DELETE(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  try {
    const id = req.nextUrl.searchParams.get("id");

    if (!id) {
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Proposal ID is required",
      });
    }

    const { error } = await supabase.from("proposals").delete().eq("id", id);

    if (error) {
      throw error;
    }

    return apiSuccess(req, { deleted: true });
  } catch (error) {
    console.error("Error deleting proposal:", error);
    return apiInternalError(
      req,
      error instanceof Error ? error.message : "Failed to delete proposal",
    );
  }
}
