import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import {
  apiSuccess,
  apiUnauthorized,
  apiNotFound,
  apiForbidden,
  apiInternalError,
} from "@/lib/api/response";
import { hasPermission } from "@/lib/rbac/check";

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return apiUnauthorized(req);

  const { data, error } = await supabase
    .from("invoices")
    .select("*, client:clients(id, company_name), line_items:invoice_line_items(*)")
    .eq("id", id)
    .single();

  if (error || !data) return apiNotFound(req, "Invoice not found");

  return apiSuccess(req, data);
}

export async function DELETE(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return apiUnauthorized(req);

  const canDelete = await hasPermission("invoices.delete", { supabase, userId: user.id });
  if (!canDelete) return apiForbidden(req, "You don't have permission to delete invoices");

  const { data: invoice } = await supabase
    .from("invoices")
    .select("id, status")
    .eq("id", id)
    .single();

  if (!invoice) return apiNotFound(req, "Invoice not found");

  if (invoice.status === "paid") {
    return apiForbidden(req, "Paid invoices cannot be deleted");
  }

  const { error } = await supabase.from("invoices").delete().eq("id", id);
  if (error) return apiInternalError(req, error.message);

  return apiSuccess(req, { deleted: true });
}
