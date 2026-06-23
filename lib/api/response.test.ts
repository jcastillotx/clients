import { describe, expect, it } from "vitest";
import {
  apiError,
  apiSuccess,
  errorCodeFromStatus,
  extractApiErrorMessage,
} from "@/lib/api/response";
import { REQUEST_ID_HEADER } from "@/lib/api/request-context";

describe("api response helpers", () => {
  it("returns standardized error payloads with request id", async () => {
    const request = new Request("http://localhost/api/test", {
      headers: { [REQUEST_ID_HEADER]: "req-123" },
    });

    const response = apiError(request, {
      status: 401,
      code: "UNAUTHORIZED",
      message: "Authentication required",
    });

    expect(response.status).toBe(401);
    expect(response.headers.get(REQUEST_ID_HEADER)).toBe("req-123");

    const body = await response.json();
    expect(body).toEqual({
      success: false,
      error: {
        code: "UNAUTHORIZED",
        message: "Authentication required",
      },
      message: "Authentication required",
      requestId: "req-123",
    });
  });

  it("returns standardized success payloads with pagination", async () => {
    const request = new Request("http://localhost/api/clients");
    const response = apiSuccess(request, [{ id: "1" }], {
      pagination: {
        limit: 50,
        offset: 0,
        total: 1,
        hasMore: false,
      },
    });

    const body = await response.json();
    expect(body.success).toBe(true);
    expect(body.data).toHaveLength(1);
    expect(body.pagination?.total).toBe(1);
    expect(typeof body.requestId).toBe("string");
  });

  it("maps HTTP statuses to error codes", () => {
    expect(errorCodeFromStatus(401)).toBe("UNAUTHORIZED");
    expect(errorCodeFromStatus(429)).toBe("RATE_LIMITED");
    expect(errorCodeFromStatus(503)).toBe("SERVICE_UNAVAILABLE");
    expect(errorCodeFromStatus(500)).toBe("INTERNAL_ERROR");
  });

  it("extracts messages from new and legacy error payloads", () => {
    expect(
      extractApiErrorMessage({
        success: false,
        error: { code: "FORBIDDEN", message: "Permission denied" },
        message: "Permission denied",
      }),
    ).toBe("Permission denied");

    expect(extractApiErrorMessage({ error: "Legacy string error" })).toBe(
      "Legacy string error",
    );

    expect(extractApiErrorMessage(null, "fallback")).toBe("fallback");
  });
});
