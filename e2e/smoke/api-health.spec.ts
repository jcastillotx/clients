import { test, expect } from "@playwright/test";

test.describe("API smoke", () => {
  test("health endpoint responds with JSON status", async ({ request }) => {
    const response = await request.get("/api/health");
    const body = await response.json();

    expect([200, 503]).toContain(response.status());

    const health = body.data ?? body;
    expect(body.success).toBe(true);
    expect(health).toMatchObject({
      status: expect.stringMatching(/operational|degraded|down/),
      timestamp: expect.any(String),
      services: expect.any(Object),
    });
    expect(typeof body.requestId).toBe("string");
  });

  test("health HEAD returns 200 or 503", async ({ request }) => {
    const response = await request.head("/api/health");
    expect([200, 503]).toContain(response.status());
  });
});
