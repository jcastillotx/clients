import { requireStaffPageAccess } from "@/lib/auth/staff-page-access";

export default async function StaffTasksLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  await requireStaffPageAccess();
  return children;
}
