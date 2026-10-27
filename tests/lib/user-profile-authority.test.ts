import { describe, expect, it, vi } from "vitest";
import { ensureAuthUserProfile } from "@/lib/supabase/user-profile-sync";
import type { SupabaseClient, User } from "@supabase/supabase-js";
describe("profile synchronization authority", () => {
  it("never copies role, tenant, or super-admin claims from editable metadata", async () => {
    const upsert = vi.fn(
      async (
        _payload: Record<string, unknown>,
        _options: Record<string, unknown>,
      ) => ({ error: null }),
    );
    const client = {
      from: vi.fn(() => ({ upsert })),
    } as unknown as SupabaseClient;
    const user = {
      id: "fixture",
      email: "fixture@example.invalid",
      user_metadata: {
        name: "Fixture",
        client_id: "11111111-1111-4111-8111-111111111111",
        role: "super_admin",
        is_super_admin: true,
      },
    } as unknown as User;
    await ensureAuthUserProfile(client, user);
    expect(upsert.mock.calls[0][0]).toMatchObject({
      name: "Fixture",
      id: "fixture",
    });
    expect(upsert.mock.calls[0][0]).not.toHaveProperty("is_super_admin");
    expect(upsert.mock.calls[0][0]).not.toHaveProperty("client_id");
  });
});
