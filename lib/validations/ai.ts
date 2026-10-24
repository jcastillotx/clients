import { cleanTextSchema } from "./fields";
import { z } from "zod";

/**
 * AI Email Generation Schema
 */
export const generateEmailSchema = z.object({
  purpose: z.enum(["introduction", "follow-up", "update", "proposal", "support", "thank-you", "custom"]),
  tone: z.enum(["professional", "friendly", "formal", "casual"]),
  recipient: cleanTextSchema.max(255).optional(),
  subject: cleanTextSchema.max(255).optional(),
  keyPoints: cleanTextSchema.min(1, "Key points are required").or(z.literal("")),
  customInstructions: cleanTextSchema.max(100000).optional(),
}).refine(
  (data) => data.keyPoints.trim() || data.customInstructions?.trim(),
  {
    message: "Either key points or custom instructions must be provided",
    path: ["keyPoints"],
  }
);

export type GenerateEmailInput = z.infer<typeof generateEmailSchema>;
