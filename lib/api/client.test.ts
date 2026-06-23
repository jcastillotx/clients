import { afterEach, describe, expect, it, vi } from "vitest";
import { ApiClientError, fetchApi, fetchBinary, unwrapApiData } from "@/lib/api/client";

describe("api client helpers", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("unwrapApiData returns data from standardized payloads", () => {
    expect(unwrapApiData<{ id: string }>({ success: true, data: { id: "1" } })).toEqual({
      id: "1",
    });
  });

  it("unwrapApiData passes through legacy payloads", () => {
    expect(unwrapApiData<{ id: string }>({ id: "legacy" })).toEqual({ id: "legacy" });
  });

  it("fetchApi unwraps success payloads", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        new Response(JSON.stringify({ success: true, data: { ok: true }, requestId: "req-1" }), {
          status: 200,
          headers: { "Content-Type": "application/json" },
        }),
      ),
    );

    const data = await fetchApi<{ ok: boolean }>("/api/test");
    expect(data).toEqual({ ok: true });
  });

  it("fetchApi returns raw payloads when requested", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        new Response(
          JSON.stringify({
            success: true,
            data: [{ id: "1" }],
            pagination: { limit: 50, offset: 0, total: 1, hasMore: false },
            requestId: "req-2",
          }),
          { status: 200, headers: { "Content-Type": "application/json" } },
        ),
      ),
    );

    const body = await fetchApi<{ pagination: { total: number } }>("/api/clients", undefined, {
      raw: true,
    });
    expect(body.pagination.total).toBe(1);
  });

  it("fetchApi throws ApiClientError with standardized error messages", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        new Response(
          JSON.stringify({
            success: false,
            error: { code: "FORBIDDEN", message: "Permission denied" },
            message: "Permission denied",
            requestId: "req-3",
          }),
          { status: 403, headers: { "Content-Type": "application/json" } },
        ),
      ),
    );

    await expect(fetchApi("/api/test")).rejects.toMatchObject({
      name: "ApiClientError",
      message: "Permission denied",
      status: 403,
      requestId: "req-3",
    } satisfies Partial<ApiClientError>);
  });

  it("fetchApi uses fallback message for empty error bodies", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(new Response("", { status: 500 })),
    );

    await expect(
      fetchApi("/api/test", undefined, { fallbackMessage: "Could not save" }),
    ).rejects.toThrow("Could not save");
  });

  it("fetchBinary returns blob payloads on success", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        new Response(new Blob(["pdf"]), {
          status: 200,
          headers: { "Content-Type": "application/pdf" },
        }),
      ),
    );

    const blob = await fetchBinary("/api/proposals/1/pdf");
    expect(blob.type).toBe("application/pdf");
  });

  it("fetchBinary throws ApiClientError for JSON error responses", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        new Response(JSON.stringify({ message: "PDF unavailable" }), {
          status: 404,
          headers: { "Content-Type": "application/json" },
        }),
      ),
    );

    await expect(fetchBinary("/api/proposals/1/pdf")).rejects.toThrow("PDF unavailable");
  });
});
