import { redirect } from "next/navigation";

export default function AdminTemplateFormsRedirectPage() {
  redirect("/settings/form-templates");
}
