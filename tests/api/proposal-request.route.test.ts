import { beforeEach, describe, expect, it, vi } from "vitest";
import { NextRequest } from "next/server";
const mock = vi.hoisted(() => ({ getUser: vi.fn(), access: vi.fn(), insert: vi.fn(), select: vi.fn() }));
vi.mock("@/lib/supabase/server", () => ({ createClient: async () => ({ auth: { getUser: mock.getUser } }) }));
vi.mock("@/lib/auth/route-access", () => ({ resolveRouteAccess: mock.access, canAccessClient: (a: {clientId: string}, id: string) => a.clientId === id }));
vi.mock("@/lib/db", () => ({ db: { select: mock.select, insert: mock.insert }, isDatabaseConfigurationError: () => false }));
import { POST } from "@/app/api/proposals/request/route";
const clientId = "11111111-1111-4111-8111-111111111111";
const templateId = "22222222-2222-4222-8222-222222222222";
const request = (body: unknown) => new NextRequest("https://example.com/api/proposals/request", { method: "POST", body: JSON.stringify(body) });
beforeEach(() => { vi.clearAllMocks(); mock.getUser.mockResolvedValue({ data: { user: {id: "user"} } }); mock.access.mockResolvedValue({clientId}); });
describe("service proposal requests", () => {
  it("rejects cross-organization writes before querying templates", async () => {
    const res = await POST(request({clientId:templateId, serviceTemplateId:templateId}));
    expect(res.status).toBe(403); expect(mock.select).not.toHaveBeenCalled(); expect(mock.insert).not.toHaveBeenCalled();
  });
  it("validates request input before writing", async () => {
    expect((await POST(request({clientId:"bad", title:""}))).status).toBe(400);
    expect(mock.insert).not.toHaveBeenCalled();
  });
  it("creates a quote-only request as a draft that cannot be accepted as a free proposal", async () => {
    mock.select.mockReturnValue({from: () => ({where: () => ({limit: async () => [{id:templateId,name:"Website",isActive:true,totalAmount:"0",currency:"USD",lineItems:[],metadata:{pricingMode:"quote"}}]})})});
    const values = vi.fn().mockReturnValue({returning: async () => [{id:"proposal"}]}); mock.insert.mockReturnValue({values});
    expect((await POST(request({clientId,serviceTemplateId:templateId}))).status).toBe(201);
    expect(values).toHaveBeenCalledWith(expect.objectContaining({clientId,status:"draft",sentAt:null,metadata:expect.objectContaining({isCustomRequest:true})}));
  });
});
