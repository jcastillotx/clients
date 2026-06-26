import { notFound } from "next/navigation";
import { and, eq, isNull } from "drizzle-orm";
import { db } from "@/lib/db";
import { projects } from "@/lib/db/schema/projects";
import { ProjectReviewPanel } from "@/components/projects/project-review-panel";

export default async function ProjectFeedbackPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const [project] = await db
    .select({ name: projects.name })
    .from(projects)
    .where(and(eq(projects.id, id), isNull(projects.deletedAt)))
    .limit(1);

  if (!project) {
    notFound();
  }

  return (
    <div className="container mx-auto space-y-6 px-4 py-8">
      <div>
        <h1 className="text-3xl font-bold">Project Feedback</h1>
        <p className="text-muted-foreground">{project.name}</p>
      </div>
      <ProjectReviewPanel projectId={id} />
    </div>
  );
}
