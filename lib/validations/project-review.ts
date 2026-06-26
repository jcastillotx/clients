import { z } from "zod";

export const projectReviewItemTypeSchema = z.enum(["website", "image"]);

export const createWebsiteReviewSchema = z.object({
  type: z.literal("website"),
  title: z.string().min(1, "Title is required").max(180),
  websiteUrl: z.string().url("Enter a valid website URL").max(2048),
});

export const createReviewCommentSchema = z.object({
  body: z.string().min(1, "Comment is required").max(2000),
  xPercent: z.coerce.number().min(0).max(100).optional().nullable(),
  yPercent: z.coerce.number().min(0).max(100).optional().nullable(),
}).refine(
  (value) =>
    (value.xPercent == null && value.yPercent == null) ||
    (value.xPercent != null && value.yPercent != null),
  {
    message: "Both x and y coordinates are required for pinned comments.",
    path: ["xPercent"],
  },
);

export type CreateWebsiteReviewInput = z.infer<typeof createWebsiteReviewSchema>;
export type CreateReviewCommentInput = z.infer<typeof createReviewCommentSchema>;
