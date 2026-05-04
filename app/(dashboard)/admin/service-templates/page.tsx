import { redirect } from "next/navigation";

export default function LegacyAdminServiceTemplatesPage() {
  redirect("/settings/service-templates");
}
