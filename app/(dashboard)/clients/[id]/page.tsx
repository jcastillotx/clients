import { createClient, createAdminClientIfAvailable } from "@/lib/supabase/server";
import { notFound } from "next/navigation";
import {
  hasAnyRole,
  hasPermission,
  Permissions,
  Roles,
} from "@/lib/rbac/permissions";
import { ClientDetail } from "@/components/clients/client-detail";
import { ClientRequests } from "@/components/clients/client-requests";
import { ClientInvoices } from "@/components/clients/client-invoices";

interface ClientDetailPageProps {
  params: Promise<{
    id: string;
  }>;
}

interface StaffAssignmentRow {
  id: string;
  role: string;
  user:
    | {
        id: string;
        name: string;
        email: string;
        avatar?: string | null;
      }
    | Array<{
        id: string;
        name: string;
        email: string;
        avatar?: string | null;
      }>
    | null;
}

interface RequestRow {
  id: string;
  title: string;
  status: string;
  priority: string;
  created_at: string;
  assigned_user:
    | {
        id: string;
        name: string;
        avatar?: string | null;
      }
    | Array<{
        id: string;
        name: string;
        avatar?: string | null;
      }>
    | null;
}

/**
 * Client detail page (Server Component)
 *
 * Fetches client with all related data (requests, invoices, staff assignments).
 */
export default async function ClientDetailPage({
  params,
}: ClientDetailPageProps) {
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

  let canArchiveClient = false;
  let canResendLogin = false;
  if (currentUser) {
    const accessOptions = { supabase, userId: currentUser.id };
    const metadataRole = String(
      currentUser.user_metadata?.role ??
        currentUser.user_metadata?.app_role ??
        "",
    ).toLowerCase();
    const hasManagementMetadataRole =
      currentUser.user_metadata?.is_super_admin === true ||
      metadataRole === Roles.SUPER_ADMIN ||
      metadataRole === Roles.ADMIN ||
      metadataRole === Roles.ACCOUNT_MANAGER;
    const [canUpdateClients, canDeleteClients, hasManagementRoleDb] =
      await Promise.all([
        hasPermission(Permissions.CLIENTS_UPDATE, accessOptions),
        hasPermission(Permissions.CLIENTS_DELETE, accessOptions),
        hasAnyRole(
          [Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER],
          accessOptions,
        ),
      ]);
    canResendLogin =
      canUpdateClients || hasManagementRoleDb || hasManagementMetadataRole;
    canArchiveClient =
      canDeleteClients || hasManagementRoleDb || hasManagementMetadataRole;
  }

  // Use admin client for internal lookups (primary contact, staff) to avoid
  // RLS blocking the join to users when the logged-in user is an account_manager.
  const adminClient = createAdminClientIfAvailable();
  const lookupClient = adminClient ?? supabase;

  let primaryContact: {
    id: string;
    name: string;
    email: string;
    phone?: string | null;
    avatar?: string | null;
  } | null = null;

  if (
    typeof client.primary_contact_id === "string" &&
    client.primary_contact_id.length > 0
  ) {
    const { data: contact, error: contactError } = await lookupClient
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
  const [
    { data: requests, count: requestCount },
    { data: invoices },
    { data: staffAssignments },
  ] = await Promise.all([
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
      .is("deleted_at", null)
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
    lookupClient
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
    openRequests:
      requests?.filter((r) => ["pending", "in_progress"].includes(r.status))
        .length || 0,
    totalRevenue: invoices?.reduce((sum, inv) => sum + inv.amount, 0) || 0,
    paidRevenue:
      invoices
        ?.filter((inv) => inv.status === "paid")
        .reduce((sum, inv) => sum + inv.amount, 0) || 0,
  };

  const normalizedStaffAssignments = (
    (staffAssignments || []) as StaffAssignmentRow[]
  ).flatMap((assignment) => {
    const user = Array.isArray(assignment.user)
      ? (assignment.user[0] ?? null)
      : assignment.user;

    if (!user) {
      return [];
    }

    return [
      {
        ...assignment,
        user: {
          ...user,
          avatar: user.avatar ?? undefined,
        },
      },
    ];
  });

  const normalizedRequests = ((requests || []) as RequestRow[]).map(
    (request) => {
      const assignedUser = Array.isArray(request.assigned_user)
        ? (request.assigned_user[0] ?? null)
        : request.assigned_user;

      return {
        ...request,
        assigned_user: assignedUser
          ? {
              ...assignedUser,
              avatar: assignedUser.avatar ?? undefined,
            }
          : null,
      };
    },
  );

  return (
    <div className="flex flex-col gap-8 p-8">
      {/* Client details */}
      <ClientDetail
        canArchiveClient={canArchiveClient}
        canResendLogin={canResendLogin}
        client={clientWithPrimaryContact}
        staffAssignments={normalizedStaffAssignments}
        stats={stats}
      />

      {/* Related data tabs */}
      <div className="grid gap-6 md:grid-cols-2">
        <ClientRequests requests={normalizedRequests} clientId={id} />
        <ClientInvoices invoices={invoices || []} />
      </div>
    </div>
  );
}
