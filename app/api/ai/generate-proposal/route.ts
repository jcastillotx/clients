import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { z } from "zod";

const generateProposalSchema = z.object({
  clientName: z.string().min(1),
  clientLocation: z.string().optional(),
  projectTitle: z.string().min(1),
  projectDescription: z.string().optional(),
  lineItems: z
    .array(
      z.object({
        description: z.string(),
        quantity: z.number(),
        unitPrice: z.number(),
        category: z.string().optional(),
      }),
    )
    .optional(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).default("USD"),
  additionalContext: z.string().optional(),
});

/**
 * POST /api/ai/generate-proposal
 *
 * Generate a detailed professional proposal using Claude AI
 */
export async function POST(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const apiKey = process.env.ANTHROPIC_API_KEY;
  if (!apiKey) {
    return NextResponse.json(
      { error: "AI features are not configured. Please set ANTHROPIC_API_KEY." },
      { status: 503 },
    );
  }

  try {
    const body = await req.json();
    const validatedData = generateProposalSchema.parse(body);

    const lineItemsSummary = validatedData.lineItems?.length
      ? validatedData.lineItems
          .map(
            (item) =>
              `- ${item.description}: ${item.quantity} × ${validatedData.currency} ${item.unitPrice.toFixed(2)} = ${validatedData.currency} ${(item.quantity * item.unitPrice).toFixed(2)}${item.category ? ` (${item.category})` : ""}`,
          )
          .join("\n")
      : "No line items provided yet.";

    const totalAmount = validatedData.lineItems?.reduce((sum, item) => sum + item.quantity * item.unitPrice, 0) ?? 0;

    const prompt = `You are a professional proposal writer for Kre8ivDesigns Marketing, LLC, a full-service digital agency based in San Antonio, Texas (2186 Jackson Keller Rd, Suite 2005, San Antonio, TX 78213, https://www.kre8ivdesigns.com).

Generate a comprehensive, legally-structured professional services proposal/agreement for the following project. The proposal should be detailed, thorough, and ready for client presentation.

PROJECT DETAILS:
- Client: ${validatedData.clientName}${validatedData.clientLocation ? ` (${validatedData.clientLocation})` : ""}
- Project Title: ${validatedData.projectTitle}
${validatedData.projectDescription ? `- Project Description: ${validatedData.projectDescription}` : ""}
- Currency: ${validatedData.currency}
- Line Items/Services:
${lineItemsSummary}
- Total Amount: ${validatedData.currency} ${totalAmount.toFixed(2)}
${validatedData.additionalContext ? `- Additional Context: ${validatedData.additionalContext}` : ""}

REQUIRED PROPOSAL SECTIONS (generate all of these with full detail):

1. PARTIES & RECITALS — Include full legal entity names, addresses, WHEREAS clauses establishing the purpose and engagement.

2. SCOPE OF WORK — Detailed breakdown of all deliverables, pages/features (if applicable), design direction, platform/technology architecture, functional requirements, additional services (SEO, analytics, CRO, responsive design, content strategy), and any legal requirements.

3. PROJECT TIMELINE — Phased timeline with deliverables, durations, and client dependencies for each phase. Include a client delay provision.

4. PAYMENT TERMS — Pricing breakdown with applicable taxes, milestone payment schedule with dates and amounts. Include payment methods, late payment terms, and non-payment provisions.

5. REVISIONS & CHANGE ORDERS — Number of included revision rounds, additional revision rates, and change order process.

6. INTELLECTUAL PROPERTY — IP transfer upon payment, pre-existing tools retention, portfolio rights, third-party asset licensing.

7. CONFIDENTIALITY — Mutual confidentiality obligations, definition of confidential information, exceptions, survival period.

8. WARRANTIES — Warranty period, what's covered, exclusions, disclaimer of implied warranties.

9. LIMITATION OF LIABILITY — Cap on damages, exclusion of consequential damages, risk allocation acknowledgment.

10. TERMINATION — Termination with notice, termination for cause, effect of termination on licenses and payment.

11. HOSTING DISCLAIMER — If applicable, hosting responsibility, deployment assistance, backup recommendations.

12. INDEMNIFICATION — Mutual indemnification provisions.

13. GOVERNING LAW & DISPUTE RESOLUTION — Texas governing law, Bexar County jurisdiction, mediation first, attorney's fees.

14. GENERAL PROVISIONS — Entire agreement, amendments, severability, waiver, assignment, force majeure, notices, independent contractor relationship.

15. SIGNATURES — Signature block for both parties with date fields.

FORMATTING RULES:
- Use numbered sections and subsections (e.g., 2.1, 2.2)
- Use tables where appropriate (for timelines, pricing, page lists) formatted in markdown
- Be specific and detailed — not generic boilerplate
- Tailor the scope of work to the actual project described
- Calculate payment milestones splitting the total into 3 equal installments
- Set a 30-day project timeline by default unless the scope suggests otherwise
- Use professional legal language throughout
- Include bullet points for lists of features/requirements
- The hourly rate should be derived from the line items if possible

Return ONLY the proposal content in markdown format. Do not include any preamble or explanation outside the proposal itself.`;

    const response = await fetch("https://api.anthropic.com/v1/messages", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "x-api-key": apiKey,
        "anthropic-version": "2023-06-01",
      },
      body: JSON.stringify({
        model: "claude-sonnet-4-20250514",
        max_tokens: 8000,
        messages: [
          {
            role: "user",
            content: prompt,
          },
        ],
      }),
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      console.error("Anthropic API error:", response.status, errorData);
      return NextResponse.json(
        { error: `AI generation failed: ${response.statusText}` },
        { status: 502 },
      );
    }

    const result = await response.json();
    const generatedContent = result.content?.[0]?.text || "";

    return NextResponse.json({
      content: generatedContent,
      model: "claude-sonnet-4-20250514",
      usage: result.usage,
    });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }
    console.error("Error generating proposal:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to generate proposal" },
      { status: 500 },
    );
  }
}
