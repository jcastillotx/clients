import { NextRequest, NextResponse } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { proposals, serviceTemplates } from "@/lib/db/schema/proposals";
import { eq } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";

/**
 * POST /api/proposals/request
 * Client requests a service - creates a proposal from a template or custom request
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user }, error: authError } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ success: false, error: "Authentication required" }, { status: 401 });
    }

    const body = await request.json();

    if (!body.clientId) {
      return NextResponse.json({ success: false, error: "Client ID is required" }, { status: 400 });
    }

    // Option 1: Request from a service template
    if (body.serviceTemplateId) {
      const [template] = await db
        .select()
        .from(serviceTemplates)
        .where(eq(serviceTemplates.id, body.serviceTemplateId))
        .limit(1);

      if (!template) {
        return NextResponse.json({ success: false, error: "Service template not found" }, { status: 404 });
      }

      if (!template.isActive) {
        return NextResponse.json({ success: false, error: "This service is no longer available" }, { status: 400 });
      }

      const [proposal] = await db
        .insert(proposals)
        .values({
          clientId: body.clientId,
          serviceTemplateId: template.id,
          title: template.name,
          description: template.description,
          status: "sent",
          totalAmount: template.totalAmount,
          currency: template.currency,
          terms: template.terms,
          lineItems: template.lineItems,
          sentAt: new Date(),
          createdBy: user.id,
          metadata: {
            notes: body.notes || null,
          },
        })
        .returning();

      return NextResponse.json(
        { success: true, data: proposal, message: "Service requested successfully. A proposal has been created for your review." },
        { status: 201 },
      );
    }

    // Option 2: Custom service request
    if (!body.title?.trim()) {
      return NextResponse.json({ success: false, error: "Service title is required" }, { status: 400 });
    }
    if (!body.description?.trim()) {
      return NextResponse.json({ success: false, error: "Please describe what you need" }, { status: 400 });
    }

    const [proposal] = await db
      .insert(proposals)
      .values({
        clientId: body.clientId,
        title: body.title,
        description: body.description,
        status: "draft",
        totalAmount: "0",
        currency: body.currency || "USD",
        lineItems: [],
        createdBy: user.id,
        metadata: {
          isCustomRequest: true,
          customRequestDescription: body.description,
          notes: body.notes || null,
        },
      })
      .returning();

    return NextResponse.json(
      { success: true, data: proposal, message: "Custom service request submitted. We will review and create a proposal for you." },
      { status: 201 },
    );
  } catch (error) {
    console.error("Error creating service request:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to submit request", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}
