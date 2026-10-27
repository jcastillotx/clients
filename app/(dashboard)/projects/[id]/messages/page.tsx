import { getAuthorizedProject } from "@/lib/projects/page-access";
import { notFound, redirect } from "next/navigation";

export default async function ProjectMessagesPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const project = await getAuthorizedProject(id);

  if (!project) {
    notFound();
  }

  const requestId =
    project.metadata?.sourceProjectRequestId ||
    project.metadata?.projectRequestId;
  if (requestId) {
    redirect(`/projects/requests/${requestId}?tab=messaging`);
  }

  redirect("/messages");
}
