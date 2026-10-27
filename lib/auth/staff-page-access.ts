import { cache } from "react";
import { notFound } from "next/navigation";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";

export const requireStaffPageAccess = cache(async () => {
  const access = await resolveStaffAccess();
  if (!access?.isStaff) notFound();
  return access;
});
