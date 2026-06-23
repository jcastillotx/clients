import { afterEach, describe, expect, it, vi } from "vitest";
import {
  isSupabaseConfigured,
  resetEnvValidationForTests,
  validateEnvAtStartup,
} from "@/lib/env";

describe("env validation", () => {
  afterEach(() => {
    resetEnvValidationForTests();
    vi.unstubAllEnvs();
  });

  it("isSupabaseConfigured returns false when vars are missing", () => {
    vi.stubEnv("NEXT_PUBLIC_SUPABASE_URL", "");
    vi.stubEnv("NEXT_PUBLIC_SUPABASE_ANON_KEY", "");
    expect(isSupabaseConfigured()).toBe(false);
  });

  it("isSupabaseConfigured returns true when vars are set", () => {
    vi.stubEnv("NEXT_PUBLIC_SUPABASE_URL", "https://example.supabase.co");
    vi.stubEnv("NEXT_PUBLIC_SUPABASE_ANON_KEY", "anon-key");
    expect(isSupabaseConfigured()).toBe(true);
  });

  it("validateEnvAtStartup skips outside production", () => {
    vi.stubEnv("NODE_ENV", "development");
    expect(() => validateEnvAtStartup()).not.toThrow();
  });

  it("validateEnvAtStartup throws in production when required vars are missing", () => {
    vi.stubEnv("NODE_ENV", "production");
    vi.stubEnv("NEXT_PUBLIC_SUPABASE_URL", "");
    vi.stubEnv("NEXT_PUBLIC_SUPABASE_ANON_KEY", "");
    vi.stubEnv("DATABASE_URL", "");
    vi.stubEnv("POSTGRES_URL", "");
    vi.stubEnv("SUPABASE_SERVICE_KEY", "");
    vi.stubEnv("SUPABASE_SERVICE_ROLE_KEY", "");

    expect(() => validateEnvAtStartup()).toThrow(
      /Invalid production environment configuration/,
    );
  });

  it("validateEnvAtStartup passes in production with required vars", () => {
    vi.stubEnv("NODE_ENV", "production");
    vi.stubEnv("NEXT_PUBLIC_SUPABASE_URL", "https://example.supabase.co");
    vi.stubEnv("NEXT_PUBLIC_SUPABASE_ANON_KEY", "anon-key");
    vi.stubEnv("DATABASE_URL", "postgresql://postgres:pass@localhost:5432/postgres");
    vi.stubEnv("SUPABASE_SERVICE_KEY", "service-key");

    const warnSpy = vi.spyOn(console, "warn").mockImplementation(() => {});

    expect(() => validateEnvAtStartup()).not.toThrow();
    expect(warnSpy).toHaveBeenCalled();

    warnSpy.mockRestore();
  });
});
