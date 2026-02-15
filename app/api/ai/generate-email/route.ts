import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { z } from "zod";
import { generateEmailSchema } from "@/lib/validations/ai";

/**
 * POST /api/ai/generate-email
 * 
 * Generate an email using AI based on provided parameters
 */
export async function POST(req: NextRequest) {
  const supabase = await createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const body = await req.json();
    
    // Validate input
    const validatedData = generateEmailSchema.parse(body);

    // For now, generate a basic email structure
    // TODO: Integrate with OpenAI/Anthropic API for real AI generation
    
    const email = generateEmailContent({
      purpose: validatedData.purpose,
      tone: validatedData.tone,
      recipient: validatedData.recipient || "",
      subject: validatedData.subject || "",
      keyPoints: validatedData.keyPoints,
      customInstructions: validatedData.customInstructions || "",
    });

    return NextResponse.json({ 
      email,
      subject: validatedData.subject || generateSubject(validatedData.purpose, validatedData.keyPoints),
    });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }
    console.error("Error generating email:", error);
    return NextResponse.json(
      { error: "Failed to generate email" },
      { status: 500 }
    );
  }
}

/**
 * Generate email content based on parameters
 * TODO: Replace with actual AI API call (OpenAI, Anthropic, etc.)
 */
function generateEmailContent(params: {
  purpose: string;
  tone: string;
  recipient: string;
  subject: string;
  keyPoints: string;
  customInstructions: string;
}): string {
  const greeting = params.recipient 
    ? `Hi ${params.recipient},`
    : `Hello,`;

  const toneAdjectives = {
    professional: "professional",
    friendly: "warm and friendly",
    formal: "formal and respectful",
    casual: "casual and conversational",
  };

  const closings = {
    professional: "Best regards",
    friendly: "Warm regards",
    formal: "Sincerely",
    casual: "Cheers",
  };

  // Parse key points into paragraphs
  const points = params.keyPoints
    .split("\n")
    .filter((p) => p.trim())
    .map((p) => p.trim().replace(/^[-•*]\s*/, ""));

  let body = "";

  // Opening based on purpose
  const openings = {
    introduction: "I hope this email finds you well. I wanted to reach out to introduce our company and services.",
    "follow-up": "I wanted to follow up on our recent conversation.",
    update: "I'm writing to provide you with an update on our progress.",
    proposal: "Thank you for considering our services. I'm pleased to present our proposal.",
    support: "Thank you for contacting our support team. I'm here to help.",
    "thank-you": "I wanted to take a moment to express my sincere gratitude.",
    custom: "I hope this message finds you well.",
  };

  body += openings[params.purpose as keyof typeof openings] || openings.custom;
  body += "\n\n";

  // Add key points as paragraphs
  if (points.length > 0) {
    points.forEach((point) => {
      body += point + "\n\n";
    });
  }

  // Add custom instructions context
  if (params.customInstructions) {
    body += params.customInstructions + "\n\n";
  }

  // Closing based on purpose
  const finalParagraphs = {
    introduction: "I would love the opportunity to discuss how we can help achieve your goals. Would you be available for a brief call next week?",
    "follow-up": "Please let me know if you have any questions or if there's anything else I can help with.",
    update: "I'll continue to keep you updated on our progress. Please don't hesitate to reach out with any questions.",
    proposal: "I'm confident we can deliver excellent results. I'd be happy to discuss this proposal in more detail at your convenience.",
    support: "If you need any further assistance, please don't hesitate to reach out.",
    "thank-you": "I look forward to our continued partnership.",
    custom: "Please let me know if you need any additional information.",
  };

  body += finalParagraphs[params.purpose as keyof typeof finalParagraphs] || finalParagraphs.custom;

  const signature = `\n\n${closings[params.tone as keyof typeof closings] || closings.professional},\n[Your Name]\n[Your Title]\n[Your Company]`;

  return greeting + "\n\n" + body + signature;
}

/**
 * Generate a subject line based on purpose and key points
 */
function generateSubject(purpose: string, keyPoints: string): string {
  const subjects = {
    introduction: "Introduction - [Your Company Name]",
    "follow-up": "Following Up on Our Conversation",
    update: "Project Update - [Project Name]",
    proposal: "Proposal for [Service/Project]",
    support: "Re: Your Support Request",
    "thank-you": "Thank You for Your Business",
    custom: "Message from [Your Company]",
  };

  return subjects[purpose as keyof typeof subjects] || subjects.custom;
}
