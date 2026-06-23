import { z } from "zod";

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
  companyName: z.string().min(1, "Company name is required"),
  contactName: z.string().min(1, "Contact name is required"),
  email: z.string().email(),
  phone: z.string().optional().nullable(),
  website: z.string().url().optional().nullable().or(z.literal("")),
  partnerType: partnerTypeSchema.default("affiliate"),
  status: partnerStatusSchema.default("active"),
  commissionRate: z.coerce.number().min(0).max(100).default(10),
  code: z.string().min(3).max(32).optional(),
  notes: z.string().optional().nullable(),
});

export const updatePartnerSchema = createPartnerSchema.partial();

export const createReferralSchema = z.object({
  partnerId: z.string().uuid(),
  referredName: z.string().min(1, "Name is required"),
  referredEmail: z.string().email().optional().nullable().or(z.literal("")),
  referredPhone: z.string().optional().nullable(),
  status: referralStatusSchema.default("pending"),
  notes: z.string().optional().nullable(),
});

export const updateReferralSchema = z.object({
  status: referralStatusSchema.optional(),
  referredName: z.string().min(1).optional(),
  referredEmail: z.string().email().optional().nullable().or(z.literal("")),
  referredPhone: z.string().optional().nullable(),
  commissionAmount: z.coerce.number().min(0).optional().nullable(),
  notes: z.string().optional().nullable(),
});

export const kbCategorySchema = z.object({
  name: z.string().min(1, "Name is required"),
  slug: z.string().min(1).optional(),
  description: z.string().optional().nullable(),
  parentId: z.string().uuid().optional().nullable(),
  icon: z.string().optional().nullable(),
  position: z.coerce.number().int().min(0).optional(),
});

export const kbArticleSchema = z.object({
  categoryId: z.string().uuid(),
  title: z.string().min(1, "Title is required"),
  slug: z.string().min(1).optional(),
  excerpt: z.string().optional().nullable(),
  content: z.string().min(1, "Content is required"),
  videoUrl: z.string().url().optional().nullable().or(z.literal("")),
  isPublished: z.boolean().default(true),
});

export const staffGuideCategorySchema = z.object({
  name: z.string().min(1, "Name is required"),
  slug: z.string().min(1).optional(),
  description: z.string().optional().nullable(),
  icon: z.string().optional().nullable(),
  position: z.coerce.number().int().min(0).optional(),
});

export const staffGuideSchema = z.object({
  categoryId: z.string().uuid(),
  title: z.string().min(1, "Title is required"),
  slug: z.string().min(1).optional(),
  summary: z.string().optional().nullable(),
  content: z.string().min(1, "Content is required"),
  serviceTier: z.string().optional().nullable(),
  price: z.coerce.number().min(0).optional().nullable(),
  commitment: z.string().optional().nullable(),
  isInternal: z.boolean().default(true),
  isPublished: z.boolean().default(true),
});
