import { redirect } from "next/navigation";

import {
  ArchivedClientsManager,
  type ArchivedClientRow,
} from "@/components/admin/archive/archived-clients-manager";
import { hasPermission } from "@/lib/rbac/permissions";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";

export const metadata = {
  title: "Archive | KRE8IV",
  description: "Review and restore archived clients",
};

export default async function AdminArchivePage() {
  const isAdmin = await hasPermission("admin.access");
  if (!isAdmin) {
    redirect("/dashboard");
  }

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) {
    throw new Error(
      "Missing Supabase service role credentials for archive queries",
    );
  }

  const { data, error } = await adminClient
    .from("clients")
    .select("id, company_name, email, status, created_at, deleted_at")
    .not("deleted_at", "is", null)
    .order("deleted_at", { ascending: false });

  if (error) {
    console.error("Error fetching archived clients:", error);
    throw new Error("Failed to load archived clients");
  }

  return (
    <div className="container mx-auto space-y-6 py-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Archive</h1>
        <p className="text-muted-foreground">
          Review archived clients and restore them when needed.
        </p>
      </div>

      <ArchivedClientsManager clients={(data ?? []) as ArchivedClientRow[]} />
    </div>
  );
}
