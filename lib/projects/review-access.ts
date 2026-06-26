import { and, eq, isNull } from "drizzle-orm";
import type { NextResponse } from "next/server";
import type { User } from "@supabase/supabase-js";

import { apiForbidden, apiNotFound, apiUnauthorized } from "@/lib/api/response";
import { db } from "@/lib/db";
import { projects } from "@/lib/db/schema/projects";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { createClient } from "@/lib/supabase/server";

type ProjectReviewAccessResult =
  | { error: NextResponse }
  | {
      error?: never;
      user: User;
      project: {
        id: string;
        clientId: string;
        name: string;
      };
      access: Awaited<ReturnType<typeof resolveRouteAccess>>;
    };

export async function requireProjectReviewAccess(
  request: Request,
  projectId: string,
): Promise<ProjectReviewAccessResult> {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return { error: apiUnauthorized(request) };
  }

  const [project] = await db
    .select({
      id: projects.id,
      clientId: projects.clientId,
      name: projects.name,
    })
    .from(projects)
    .where(and(eq(projects.id, projectId), isNull(projects.deletedAt)))
    .limit(1);

  if (!project) {
    return { error: apiNotFound(request, "Project not found") };
  }

  const access = await resolveRouteAccess(supabase, user);
  if (!canAccessClient(access, project.clientId)) {
    return { error: apiForbidden(request, "Access denied") };
  }

  return {
    user,
    project,
    access,
  };
}
