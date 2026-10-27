import { and, eq, isNull } from "drizzle-orm";
import { z } from "zod";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { canAccessClient } from "@/lib/auth/route-access";
import { db } from "@/lib/db";
import { projects } from "@/lib/db/schema/projects";

export async function getAuthorizedProject(projectId: string) {
  if (!z.string().uuid().safeParse(projectId).success) return null;
  const access = await resolveStaffAccess();
  if (!access) return null;
  const [project] = await db
    .select()
    .from(projects)
    .where(and(eq(projects.id, projectId), isNull(projects.deletedAt)))
    .limit(1);
  return project && canAccessClient(access, project.clientId) ? project : null;
}
