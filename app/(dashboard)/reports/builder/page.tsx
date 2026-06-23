import { redirect } from "next/navigation";

/** Legacy link target — report builder lives on the custom dashboard page. */
export default function ReportBuilderRedirectPage() {
  redirect("/reports/custom");
}
