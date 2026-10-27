import { describe, expect, it, vi } from "vitest";
import { NextRequest } from "next/server";
import { GET } from "@/app/api/calendar/connect/google/route";

vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(async () => ({
    auth: { getUser: async () => ({ data: { user: { id: "owner" } } }) },
  })),
}));
vi.mock("@/lib/auth/signed-token", () => ({
  getSigningSecret: () => "fixture-signing-key",
  createSignedToken: () => "fixture-state",
}));
vi.mock("@/lib/calendar/oauth-credentials", () => ({
  resolveCalendarOAuthCredentialsForUser: async () => ({
    client_id: "fixture-client", client_secret: "fixture-secret", clientScopeId: null,
  }),
  hasCompleteCalendarOAuthCredentials: () => true,
}));

describe("Google calendar consent boundary", () => {
  it("requests availability and calendar metadata without event access", async () => {
    const response = await GET(new NextRequest("https://portal.example/api/calendar/connect/google"));
    const target = new URL(response.headers.get("location")!);
    const scopes = target.searchParams.get("scope")!.split(" ");
    expect(scopes).toEqual([
      "https://www.googleapis.com/auth/calendar.calendars.readonly",
      "https://www.googleapis.com/auth/calendar.freebusy",
    ]);
    expect(target.searchParams.get("access_type")).toBe("offline");
    expect(target.searchParams.get("state")).toBe("fixture-state");
  });
});
