import { createClient } from "@/lib/supabase/server";
import { notFound } from "next/navigation";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";
import { ClientDetail } from "@/components/clients/client-detail";
import { ClientRequests } from "@/components/clients/client-requests";
import { ClientInvoices } from "@/components/clients/client-invoices";

interface ClientDetailPageProps {
  params: Promise<{
    id: string;
  }>;
}

/**
 * Client detail page (Server Component)
 *
 * Fetches client with all related data (requests, invoices, staff assignments).
 */
export default async function ClientDetailPage({ params }: ClientDetailPageProps) {
  const { id } = await params;
  const supabase = await createClient();

  // Fetch client with all related data
  const { data: client, error } = await supabase
    .from("clients")
    .select(
      `
      *
    `,
    )
    .eq("id", id)
    .single();

  if (error || !client) {
    notFound();
  }

  const {
    data: { user: currentUser },
  } = await supabase.auth.getUser();

  let canResendLogin = false;
  if (currentUser) {
    const accessOptions = { supabase, userId: currentUser.id };
    const metadataRole = String(
      currentUser.user_metadata?.role ?? currentUser.user_metadata?.app_role ?? "",
    ).toLowerCase();
    const hasManagementMetadataRole =
      currentUser.user_metadata?.is_super_admin === true ||
      metadataRole === Roles.SUPER_ADMIN ||
      metadataRole === Roles.ADMIN ||
      metadataRole === Roles.ACCOUNT_MANAGER;
    const [canUpdateClients, hasManagementRoleDb] = await Promise.all([
      hasPermission(Permissions.CLIENTS_UPDATE, accessOptions),
      hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
    ]);
    canResendLogin = canUpdateClients || hasManagementRoleDb || hasManagementMetadataRole;
  }

  let primaryContact: { id: string; name: string; email: string; phone?: string | null; avatar?: string | null } | null =
    null;

  if (typeof client.primary_contact_id === "string" && client.primary_contact_id.length > 0) {
    const { data: contact, error: contactError } = await supabase
      .from("users")
      .select("id, name, email, phone, avatar")
      .eq("id", client.primary_contact_id)
      .maybeSingle();

    if (contactError) {
      console.error("Error fetching client primary contact:", contactError);
    } else {
      primaryContact = contact;
    }
  }

  const clientWithPrimaryContact = {
    ...client,
    primary_contact: primaryContact,
  };

  // Fetch related data in parallel
  const [{ data: requests, count: requestCount }, { data: invoices }, { data: staffAssignments }] = await Promise.all([
    supabase
      .from("requests")
      .select(
        `
        id,
        title,
        status,
        priority,
        created_at,
        assigned_user:users!requests_assigned_to_fkey(id, name, avatar)
      `,
        { count: "exact" },
      )
      .eq("client_id", id)
      .order("created_at", { ascending: false })
      .limit(10),
    supabase
      .from("invoices")
      .select(
        `
        id,
        invoice_number,
        amount,
        status,
        due_date,
        created_at
      `,
      )
      .eq("client_id", id)
      .order("created_at", { ascending: false })
      .limit(10),
    supabase
      .from("staff_assignments")
      .select(
        `
        id,
        role,
        user:users(id, name, email, avatar)
      `,
      )
      .eq("client_id", id),
  ]);

  // Calculate stats
  const stats = {
    totalRequests: requestCount || 0,
    openRequests: requests?.filter((r) => ["pending", "in_progress"].includes(r.status)).length || 0,
    totalRevenue: invoices?.reduce((sum, inv) => sum + inv.amount, 0) || 0,
    paidRevenue: invoices?.filter((inv) => inv.status === "paid").reduce((sum, inv) => sum + inv.amount, 0) || 0,
  };

  return (
    <div className="flex flex-col gap-8 p-8">
      {/* Client details */}
      <ClientDetail
        canResendLogin={canResendLogin}
        client={clientWithPrimaryContact}
        staffAssignments={
          (staffAssignments || []).map((sa: any) => ({
            ...sa,
            user: Array.isArray(sa.user) ? sa.user[0] : sa.user,
          })) as any
        }
        stats={stats}
      />

      {/* Related data tabs */}
      <div className="grid gap-6 md:grid-cols-2">
        <ClientRequests
          requests={
            (requests || []).map((r: any) => ({
              ...r,
              assigned_user: Array.isArray(r.assigned_user) ? r.assigned_user[0] : r.assigned_user,
            })) as any
          }
          clientId={id}
        />
        <ClientInvoices
          invoices={invoices || []}
        />
      </div>
    </div>
  );
}
