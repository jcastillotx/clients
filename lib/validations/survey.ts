import { z } from "zod";

export const surveyQuestionTypeEnum = ["text", "rating", "nps", "multiple_choice", "checkbox"] as const;

export const createSurveyQuestionSchema = z
  .object({
    prompt: z.string().min(3, "Question prompt must be at least 3 characters").max(500),
    type: z.enum(surveyQuestionTypeEnum),
    isRequired: z.boolean().default(true),
    options: z.array(z.string().min(1)).optional(),
  })
  .superRefine((value, ctx) => {
    const requiresOptions = value.type === "multiple_choice" || value.type === "checkbox";
    if (requiresOptions) {
      if (!value.options || value.options.length < 2) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: "Multiple choice and checkbox questions require at least 2 options",
          path: ["options"],
        });
      }
    }
  });

export const createSurveySchema = z.object({
  title: z.string().min(3, "Title must be at least 3 characters").max(255),
  description: z.string().max(2000).optional().nullable(),
  clientId: z.string().uuid().optional().nullable(),
  isActive: z.boolean().default(true),
  anonymousAllowed: z.boolean().default(true),
  questions: z.array(createSurveyQuestionSchema).min(1, "Add at least one question"),
});

export type CreateSurveyInput = z.infer<typeof createSurveySchema>;
