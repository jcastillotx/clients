import { afterEach, describe, expect, it, vi } from "vitest";
import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import { GET } from "@/app/api/calendar/availability/route";
vi.mock("@/lib/supabase/server", () => ({ createClient: vi.fn() }));
vi.mock("@/lib/db", () => ({ db: { select: vi.fn() } }));
vi.mock("@/lib/encryption", () => ({
  decrypt: (v: string) => v,
  encrypt: (v: string) => v,
}));
const owner = "11111111-1111-4111-8111-111111111111";
function mockAccess(visible: boolean) {
  const query = {
    select: vi.fn().mockReturnThis(),
    in: vi.fn().mockReturnThis(),
    is: vi.fn().mockReturnThis(),
    eq: vi
      .fn()
      .mockResolvedValue({
        data: visible ? [{ id: owner, name: "Synthetic user" }] : [],
        error: null,
      }),
  };
  vi.mocked(createClient).mockResolvedValue({
    auth: {
      getUser: vi.fn().mockResolvedValue({ data: { user: { id: owner } } }),
    },
    from: vi.fn().mockReturnValue(query),
  } as never);
  vi.mocked(db.select).mockReturnValue({
    from: vi.fn().mockReturnThis(),
    where: vi
      .fn()
      .mockResolvedValue([
        {
          id: "fixture",
          userId: owner,
          provider: "microsoft",
          encryptedAccessToken: "fixture-token",
          tokenExpiry: new Date(Date.now() + 3600000),
        },
      ]),
  } as never);
}
function request() {
  return new NextRequest(
    `http://localhost/api/calendar/availability?userIds=${owner}&start=2026-10-27T16:00:00Z&end=2026-10-27T17:00:00Z`,
  );
}
afterEach(() => {
  vi.clearAllMocks();
  vi.unstubAllGlobals();
});
describe("calendar availability authorization and accuracy", () => {
  it("denies invisible users before loading privileged calendar tokens", async () => {
    mockAccess(false);
    const r = await GET(request());
    expect(r.status).toBe(403);
    expect(db.select).not.toHaveBeenCalled();
  });
  it("reports provider failure as unavailable instead of free", async () => {
    mockAccess(true);
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(new Response("{}", { status: 401 })),
    );
    const r = await GET(request());
    expect((await r.json()).data[0].status).toBe("unavailable");
  });
  it("returns busy timestamps in UTC without requesting event subjects", async () => {
    mockAccess(true);
    const fetcher = vi
      .fn()
      .mockResolvedValue(
        Response.json({
          value: [
            {
              start: { dateTime: "2026-10-27T16:10:00" },
              end: { dateTime: "2026-10-27T16:40:00" },
            },
          ],
        }),
      );
    vi.stubGlobal("fetch", fetcher);
    const r = await GET(request());
    const row = (await r.json()).data[0];
    expect(row.status).toBe("busy");
    expect(row.busyBlocks[0].start).toBe("2026-10-27T16:10:00Z");
    expect(fetcher.mock.calls[0][0]).not.toContain("subject");
  });
});
