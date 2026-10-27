import type { ReactNode } from "react";
import { redirect } from "next/navigation";

import { requireAdminUser } from "@/lib/auth/route-guards";

export default async function ProjectManagementLayout({ children }: { children: ReactNode }) {
  const guard = await requireAdminUser();
  if ("error" in guard) redirect("/projects");
  return <>{children}</>;
}
