import { z } from "zod";
import { phoneSchema, cleanTextSchema } from "./fields";

export const partnerTypeSchema = z.enum(["agency", "affiliate", "reseller", "strategic"]);
export const partnerStatusSchema = z.enum(["active", "inactive", "pending", "suspended"]);
export const referralStatusSchema = z.enum([
  "pending",
  "contacted",
  "qualified",
  "converted",
  "rejected",
  "lost",
]);

export const createPartnerSchema = z.object({
  companyName: cleanTextSchema.min(1, "Company name is required"),
  contactName: cleanTextSchema.min(1, "Contact name is required"),
  email: cleanTextSchema.max(254).email(),
  phone: phoneSchema.optional().nullable(),
  website: cleanTextSchema.url().regex(/^https?:\/\/[^\s\/@]+(?:[\/?#][^\s]*)?$/i, "Use an HTTP or HTTPS URL without login credentials.").optional().nullable().or(z.literal("")),
  partnerType: partnerTypeSchema.default("affiliate"),
  status: partnerStatusSchema.default("active"),
  commissionRate: z.coerce.number().finite().min(0).max(100).default(10),
  code: cleanTextSchema.min(3).max(32).optional(),
  notes: cleanTextSchema.max(100000).optional().nullable(),
});

export const updatePartnerSchema = createPartnerSchema.partial();

export const createReferralSchema = z.object({
  partnerId: cleanTextSchema.uuid(),
  referredName: cleanTextSchema.min(1, "Name is required"),
  referredEmail: cleanTextSchema.max(254).email().optional().nullable().or(z.literal("")),
  referredPhone: phoneSchema.optional().nullable(),
  status: referralStatusSchema.default("pending"),
  notes: cleanTextSchema.max(100000).optional().nullable(),
});

export const updateReferralSchema = z.object({
  status: referralStatusSchema.optional(),
  referredName: cleanTextSchema.min(1).optional(),
  referredEmail: cleanTextSchema.max(254).email().optional().nullable().or(z.literal("")),
  referredPhone: phoneSchema.optional().nullable(),
  commissionAmount: z.coerce.number().finite().min(0).optional().nullable(),
  notes: cleanTextSchema.max(100000).optional().nullable(),
});

export const kbCategorySchema = z.object({
  name: cleanTextSchema.min(1, "Name is required"),
  slug: cleanTextSchema.min(1).optional(),
  description: cleanTextSchema.max(100000).optional().nullable(),
  parentId: cleanTextSchema.uuid().optional().nullable(),
  icon: cleanTextSchema.max(100000).optional().nullable(),
  position: z.coerce.number().finite().int().min(0).optional(),
});

export const kbArticleSchema = z.object({
  categoryId: cleanTextSchema.uuid(),
  title: cleanTextSchema.min(1, "Title is required"),
  slug: cleanTextSchema.min(1).optional(),
  excerpt: cleanTextSchema.max(100000).optional().nullable(),
  content: cleanTextSchema.min(1, "Content is required"),
  videoUrl: cleanTextSchema.url().regex(/^https?:\/\/[^\s\/@]+(?:[\/?#][^\s]*)?$/i, "Use an HTTP or HTTPS URL without login credentials.").optional().nullable().or(z.literal("")),
  isPublished: z.boolean().default(true),
});

export const staffGuideCategorySchema = z.object({
  name: cleanTextSchema.min(1, "Name is required"),
  slug: cleanTextSchema.min(1).optional(),
  description: cleanTextSchema.max(100000).optional().nullable(),
  icon: cleanTextSchema.max(100000).optional().nullable(),
  position: z.coerce.number().finite().int().min(0).optional(),
});

export const staffGuideSchema = z.object({
  categoryId: cleanTextSchema.uuid(),
  title: cleanTextSchema.min(1, "Title is required"),
  slug: cleanTextSchema.min(1).optional(),
  summary: cleanTextSchema.max(100000).optional().nullable(),
  content: cleanTextSchema.min(1, "Content is required"),
  serviceTier: cleanTextSchema.max(100000).optional().nullable(),
  price: z.coerce.number().finite().min(0).optional().nullable(),
  commitment: cleanTextSchema.max(100000).optional().nullable(),
  isInternal: z.boolean().default(true),
  isPublished: z.boolean().default(true),
});
