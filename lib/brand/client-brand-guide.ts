import { z } from "zod";

const optionalUrlSchema = z
  .string()
  .trim()
  .max(2048)
  .refine(
    (value) => value === "" || z.string().url().safeParse(value).success,
    {
      message: "Enter a complete URL, including https://",
    },
  );

export const brandColorSchema = z.object({
  id: z.string().min(1),
  name: z.string().trim().min(1, "Color name is required").max(80),
  hex: z
    .string()
    .trim()
    .regex(/^#[0-9a-fA-F]{6}$/, "Use a six-digit hex color"),
  usage: z.string().trim().max(240).default(""),
});

export const clientBrandGuideContentSchema = z.object({
  title: z.string().trim().min(1, "Guide title is required").max(120),
  summary: z.string().trim().max(600).default(""),
  positioning: z.string().trim().max(1200).default(""),
  mission: z.string().trim().max(1200).default(""),
  audience: z.string().trim().max(1200).default(""),
  personality: z.string().trim().max(800).default(""),
  tagline: z.string().trim().max(180).default(""),
  logoUrl: optionalUrlSchema.default(""),
  logoNotes: z.string().trim().max(1200).default(""),
  colors: z.array(brandColorSchema).max(12).default([]),
  headingFont: z.string().trim().max(120).default(""),
  bodyFont: z.string().trim().max(120).default(""),
  typographyNotes: z.string().trim().max(1200).default(""),
  voiceTone: z.string().trim().max(1200).default(""),
  voiceDo: z.string().trim().max(1200).default(""),
  voiceAvoid: z.string().trim().max(1200).default(""),
  applicationNotes: z.string().trim().max(1600).default(""),
});

export const saveClientBrandGuideSchema = z.object({
  status: z.enum(["draft", "published"]).default("draft"),
  content: clientBrandGuideContentSchema,
});

export type ClientBrandGuideContent = z.infer<
  typeof clientBrandGuideContentSchema
>;

export function createDefaultClientBrandGuide(
  companyName: string,
  logoUrl?: string | null,
): ClientBrandGuideContent {
  return {
    title: `${companyName} Brand Guide`,
    summary: "",
    positioning: "",
    mission: "",
    audience: "",
    personality: "",
    tagline: "",
    logoUrl: logoUrl ?? "",
    logoNotes: "",
    colors: [],
    headingFont: "",
    bodyFont: "",
    typographyNotes: "",
    voiceTone: "",
    voiceDo: "",
    voiceAvoid: "",
    applicationNotes: "",
  };
}

export function parseClientBrandGuideContent(
  value: unknown,
  companyName: string,
  logoUrl?: string | null,
): ClientBrandGuideContent {
  const fallback = createDefaultClientBrandGuide(companyName, logoUrl);
  const parsed = clientBrandGuideContentSchema.safeParse(value);

  return parsed.success ? parsed.data : fallback;
}

export function makeClientBrandGuideSlug(
  companyName: string,
  clientId: string,
): string {
  const companySlug = companyName
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 64);

  return `${companySlug || "client"}-${clientId.slice(0, 8)}-brand-guide`;
}
