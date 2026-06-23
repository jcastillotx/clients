import { describe, expect, it } from "vitest";
import {
  getRequestId,
  REQUEST_ID_HEADER,
  resolveRequestId,
} from "@/lib/api/request-context";

describe("request context", () => {
  it("reads an existing request id header", () => {
    const request = new Request("http://localhost", {
      headers: { [REQUEST_ID_HEADER]: "abc-123" },
    });

    expect(getRequestId(request)).toBe("abc-123");
    expect(resolveRequestId(request)).toBe("abc-123");
  });

  it("generates a request id when missing", () => {
    const request = new Request("http://localhost");
    const requestId = getRequestId(request);

    expect(requestId).toMatch(
      /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i,
    );
  });
});
