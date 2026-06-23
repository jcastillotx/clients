import { z } from "zod";

export const stitchDeviceTypeSchema = z.enum(["MOBILE", "DESKTOP", "TABLET", "AGNOSTIC"]);

export const createStitchProjectSchema = z.object({
  title: z.string().min(1, "Project title is required").max(120),
});

export const generateStitchScreenSchema = z.object({
  projectId: z.string().min(1).optional(),
  title: z.string().min(1).max(120).optional(),
  prompt: z.string().min(3, "Prompt must be at least 3 characters").max(4000),
  deviceType: stitchDeviceTypeSchema.default("DESKTOP"),
});

export const editStitchScreenSchema = z.object({
  prompt: z.string().min(3).max(4000),
  deviceType: stitchDeviceTypeSchema.default("DESKTOP"),
});

export const stitchVariantsSchema = z.object({
  prompt: z.string().min(3).max(4000),
  variantCount: z.coerce.number().int().min(1).max(5).default(3),
  creativeRange: z.enum(["REFINE", "EXPLORE", "REIMAGINE"]).default("EXPLORE"),
  deviceType: stitchDeviceTypeSchema.default("DESKTOP"),
});
