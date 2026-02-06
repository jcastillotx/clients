import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";

export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const clientId = searchParams.get("clientId");
    const status = searchParams.get("status");

    const supabase = await createClient();

    // Build query
    let query = supabase
      .from("contracts")
      .select(
        `
        *,
        client:clients(id, company_name),
        document:documents(id, name, file_name, storage_path)
      `,
      )
      .is("deleted_at", null)
      .order("created_at", { ascending: false });

    // Filter by client if provided
    if (clientId) {
      query = query.eq("client_id", clientId);
    }

    // Filter by status if provided
    if (status) {
      query = query.eq("status", status);
    }

    const { data: contracts, error } = await query;

    if (error) throw error;

    return NextResponse.json({ contracts: contracts || [] });
  } catch (error) {
    console.error("Error fetching contracts:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to fetch contracts",
      },
      { status: 500 },
    );
  }
}

export async function POST(request: Request) {
  try {
    const canCreate = await hasPermission("contracts.create");
    if (!canCreate) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const body = await request.json();
    const {
      title,
      description,
      clientId,
      type,
      startDate,
      endDate,
      value,
      currency,
      billingCycle,
      terms,
      autoRenew,
      noticeRequired,
      tags,
      customFields,
    } = body;

    const supabase = await createClient();

    // Generate contract number
    const { data: contractNumber } = await supabase.rpc("generate_contract_number");

    const { data: contract, error } = await supabase
      .from("contracts")
      .insert({
        title,
        description,
        contract_number: contractNumber,
        client_id: clientId,
        type,
        start_date: startDate,
        end_date: endDate,
        value,
        currency,
        billing_cycle: billingCycle,
        terms,
        auto_renew: autoRenew,
        notice_required: noticeRequired,
        tags,
        custom_fields: customFields,
      })
      .select()
      .single();

    if (error) throw error;

    return NextResponse.json({ contract }, { status: 201 });
  } catch (error) {
    console.error("Error creating contract:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Failed to create contract",
      },
      { status: 500 },
    );
  }
}
