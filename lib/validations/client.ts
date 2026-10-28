import { z } from "zod";
import {emailSchema, phoneSchema, websiteSchema, countrySchema, cleanTextSchema } from "./fields";

/** Snake-case API contract, shared by create and edit endpoints. */
export const clientFieldsSchema = z.object({
  company_name: cleanTextSchema.min(2, "Enter a company name with at least 2 characters.").max(255),
  email: emailSchema,
  status: z.enum(["active", "inactive", "pending", "suspended"]).optional(),
  primary_contact_id: z.string().guid().nullable().optional(),
  domain: websiteSchema.nullable().optional(),
  industry: cleanTextSchema.max(255).nullable().optional(),
  logo_url: cleanTextSchema.max(2048).refine(value => !value || /^https?:\/\/[^\s/@]+(?:[/?#][^\s]*)?$/i.test(value) && URL.canParse(value), "Enter an HTTP or HTTPS logo URL.").nullable().optional(),
  phone: phoneSchema.nullable().optional(),
  address: cleanTextSchema.max(500).nullable().optional(),
  city: cleanTextSchema.max(255).nullable().optional(),
  state: cleanTextSchema.max(255).nullable().optional(),
  zip_code: cleanTextSchema.max(50).nullable().optional(),
  country: countrySchema.nullable().optional(),
  website: websiteSchema.nullable().optional(),
});
export const updateClientFieldsSchema = clientFieldsSchema.partial();
