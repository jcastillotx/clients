import { requireStaffPageAccess } from "@/lib/auth/staff-page-access";
import { getAuthorizedProject } from "@/lib/projects/page-access";
import { notFound, redirect } from "next/navigation";

export default async function ProjectTasksPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  await requireStaffPageAccess();
  const { id } = await params;
  const project = await getAuthorizedProject(id);

  if (!project) {
    notFound();
  }

  if (project.metadata?.taskBoardId) {
    redirect(`/tasks/${project.metadata.taskBoardId}`);
  }

  redirect(`/tasks?projectId=${id}`);
}
