import {createProjectSchema, updateProjectSchema} from "./project";
import {createCampaignSchema, createLeadSchema, createContentSchema} from "./marketing";
import {createAdCampaignSchema} from "./ad-campaign";
import {taskFormSchema, boardFormSchema, contractFormSchema, roleFormSchema, userCreateSchema, userEditSchema} from "./workspace";
import type { ZodTypeAny } from "zod";
import {clientFieldsSchema, updateClientFieldsSchema} from "./client";
import {createPublicProjectRequestSchema, createProjectRequestSchema, updateProjectRequestSchema} from "./project-request";
import {createPublicInvoiceCheckoutSchema} from "./public-invoice-payment";
import {createRequestSchema, updateRequestSchema} from "./request";
import {createSupportTicketSchema, updateSupportTicketSchema} from "./support-ticket";
import {createPartnerSchema, updatePartnerSchema, createReferralSchema, updateReferralSchema, kbArticleSchema, kbCategorySchema, staffGuideSchema, staffGuideCategorySchema} from "./partners-kb";
import {createMeetingSchema, updateMeetingSchema} from "./meeting";
import {createTimeEntrySchema, updateTimeEntrySchema} from "./time-entry";
import {createSurveySchema} from "./survey";

/** Matches only form resource endpoints; action/subresource APIs keep their own contracts. */
const forms: Record<string, {create: ZodTypeAny; update?: ZodTypeAny}> = {
  "/api/admin/users": {create: userCreateSchema, update: userEditSchema},
  "/api/projects": {create: createProjectSchema, update: updateProjectSchema},
  "/api/marketing/campaigns": {create: createCampaignSchema},
  "/api/marketing/leads": {create: createLeadSchema},
  "/api/marketing/content-calendar": {create: createContentSchema},
  "/api/ads/campaigns": {create: createAdCampaignSchema},
  "/api/tasks": {create: taskFormSchema},
  "/api/tasks/boards": {create: boardFormSchema},
  "/api/contracts": {create: contractFormSchema},
  "/api/rbac/roles": {create: roleFormSchema, update: roleFormSchema.partial()},
  "/api/clients": {create: clientFieldsSchema, update: updateClientFieldsSchema},
  "/api/public/project-requests": {create: createPublicProjectRequestSchema},
  "/api/public/invoices/create-checkout-session": {create: createPublicInvoiceCheckoutSchema},
  "/api/projects/requests": {create: createProjectRequestSchema, update: updateProjectRequestSchema},
  "/api/requests": {create: createRequestSchema, update: updateRequestSchema},
  "/api/support": {create: createSupportTicketSchema, update: updateSupportTicketSchema},
  "/api/partners": {create: createPartnerSchema, update: updatePartnerSchema},
  "/api/referrals": {create: createReferralSchema, update: updateReferralSchema},
  "/api/meetings": {create: createMeetingSchema, update: updateMeetingSchema},
  "/api/time-tracking": {create: createTimeEntrySchema, update: updateTimeEntrySchema},
  "/api/surveys": {create: createSurveySchema},
  "/api/knowledge-base/articles": {create: kbArticleSchema},
  "/api/knowledge-base/categories": {create: kbCategorySchema},
  "/api/staff-guides": {create: staffGuideSchema},
  "/api/staff-guides/categories": {create: staffGuideCategorySchema},
};
export function validateFormRequest(path: string, method: string, body: unknown) {
  const key = path.split("?")[0].replace(/\/$/, "");
  const schema = method === "POST" ? forms[key]?.create : ["PATCH", "PUT"].includes(method) ? forms[key.substring(0, key.lastIndexOf("/"))]?.update : undefined;
  return schema?.safeParse(body);
}
