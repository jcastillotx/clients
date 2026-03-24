import { redirect } from "next/navigation";

export default function AdminTemplateFormsRedirectPage() {
  redirect("/admin/settings/templates");
}
