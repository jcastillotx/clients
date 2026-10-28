import { z } from "zod";
export const packageGroups = [
  "Website projects",
  "Website care",
  "Marketing support",
  "Discovery & specialist work",
] as const;
export const publicPackageSchema = z
  .object({
    id: z.string().regex(/^[a-zA-Z0-9-]{1,100}$/),
    name: z.string().trim().min(1).max(120),
    group: z.enum(packageGroups),
    fit: z.string().trim().min(10).max(1000),
    includes: z.array(z.string().trim().min(1).max(500)).min(1).max(10),
    published: z.boolean().default(false),
  })
  .strict();
export type PublicPackage = z.infer<typeof publicPackageSchema>;
