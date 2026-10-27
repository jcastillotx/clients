import { describe, expect, it, vi } from "vitest";
import { NextRequest } from "next/server";
import { GET } from "@/app/api/avatar/route";
const storage = vi.hoisted(() => vi.fn());
vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(async () => ({
    auth: {
      getUser: vi.fn(async () => ({ data: { user: { id: "fixture" } } })),
    },
  })),
}));
vi.mock("@/lib/storage/get-s3-credentials", () => ({
  getS3Credentials: storage,
}));
describe("avatar object boundary", () => {
  it.each([
    "private/invoice.pdf",
    "avatars/11111111-1111-4111-8111-111111111111/../private.png",
    "documents/secret.png",
  ])("rejects %s before obtaining storage credentials", async (key) => {
    const r = await GET(
      new NextRequest(
        "https://portal.test/api/avatar?key=" + encodeURIComponent(key),
      ),
    );
    expect(r.status).toBe(403);
    expect(storage).not.toHaveBeenCalled();
  });
});
