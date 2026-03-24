import { redirect } from "next/navigation";

export default function AdminTemplatesRedirectPage() {
  redirect("/admin/settings/templates");
}
