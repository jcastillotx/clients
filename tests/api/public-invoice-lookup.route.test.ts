import { NextRequest } from "next/server";
import { beforeEach, describe, expect, it, vi } from "vitest";

import { GET } from "@/app/api/public/invoices/lookup/route";
import { extractApiErrorMessage } from "@/lib/api/response";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { readJson } from "../helpers/http";

vi.mock("@/lib/supabase/server", () => ({
  createAdminClientIfAvailable: vi.fn(),
}));

describe("GET /api/public/invoices/lookup", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("returns invoice amount and billing defaults for a known invoice", async () => {
    vi.mocked(createAdminClientIfAvailable).mockReturnValue({
      from: vi.fn().mockReturnValue({
        select: vi.fn().mockReturnThis(),
        eq: vi.fn().mockReturnThis(),
        is: vi.fn().mockReturnThis(),
        single: vi.fn().mockResolvedValue({
          data: {
            invoice_number: "INV-1001",
            amount: "1500.5",
            status: "sent",
            due_date: "2027-01-21",
            client: {
              company_name: "Acme Corp",
              email: "billing@acme.com",
            },
          },
          error: null,
        }),
      }),
    } as never);

    const response = await GET(
      new NextRequest("http://localhost/api/public/invoices/lookup?invoice=INV-1001"),
    );
    const body = await readJson<{
      success: boolean;
      data: {
        invoiceNumber: string;
        amount: number;
        amountFormatted: string;
        businessName: string;
        email: string;
      };
    }>(response);

    expect(response.status).toBe(200);
    expect(body.success).toBe(true);
    expect(body.data).toMatchObject({
      invoiceNumber: "INV-1001",
      amount: 1500.5,
      amountFormatted: "1500.50",
      businessName: "Acme Corp",
      email: "billing@acme.com",
    });
  });

  it("returns 404 when invoice is not found", async () => {
    vi.mocked(createAdminClientIfAvailable).mockReturnValue({
      from: vi.fn().mockReturnValue({
        select: vi.fn().mockReturnThis(),
        eq: vi.fn().mockReturnThis(),
        is: vi.fn().mockReturnThis(),
        single: vi.fn().mockResolvedValue({
          data: null,
          error: { message: "not found" },
        }),
      }),
    } as never);

    const response = await GET(
      new NextRequest("http://localhost/api/public/invoices/lookup?invoice=INV-404"),
    );
    const body = await readJson<Record<string, unknown>>(response);

    expect(response.status).toBe(404);
    expect(extractApiErrorMessage(body)).toContain("Invoice not found");
  });

  it("returns 400 when invoice query parameter is missing", async () => {
    const response = await GET(
      new NextRequest("http://localhost/api/public/invoices/lookup"),
    );
    const body = await readJson<Record<string, unknown>>(response);

    expect(response.status).toBe(400);
    expect(extractApiErrorMessage(body)).toContain("Invoice number is required");
    expect(createAdminClientIfAvailable).not.toHaveBeenCalled();
  });
});
