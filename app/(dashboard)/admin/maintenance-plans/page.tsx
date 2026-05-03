import { redirect } from "next/navigation";

export default function LegacyAdminMaintenancePlansPage() {
  redirect("/settings/maintenance-templates");
}
