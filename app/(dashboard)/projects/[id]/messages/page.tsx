import { notFound, redirect } from "next/navigation";
import { and, eq, isNull } from "drizzle-orm";
import { db } from "@/lib/db";
import { projects } from "@/lib/db/schema/projects";

export default async function ProjectMessagesPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const [project] = await db
    .select({ metadata: projects.metadata })
    .from(projects)
    .where(and(eq(projects.id, id), isNull(projects.deletedAt)))
    .limit(1);

  if (!project) {
    notFound();
  }

  const requestId = project.metadata?.sourceProjectRequestId || project.metadata?.projectRequestId;
  if (requestId) {
    redirect(`/projects/requests/${requestId}?tab=messaging`);
  }

  redirect(`/projects/${id}`);
}
