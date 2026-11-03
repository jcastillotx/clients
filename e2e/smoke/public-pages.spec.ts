import { test, expect } from "@playwright/test";

test.describe("public pages smoke", () => {
  test("production sign-in loads without an unsafe-eval allowance", async ({
    page,
  }) => {
    const errors: string[] = [];
    page.on("pageerror", (error) => errors.push(error.message));
    // Simulate an injected parser-inserted script in the HTML response.
    // DevTools evaluate() is privileged and can inherit strict-dynamic trust.
    await page.route("**/", async (route) => {
      const upstream = await route.fetch();
      const body = (await upstream.text()).replace(
        "</head>",
        "<script id=untrusted-fixture>document.documentElement.dataset.untrustedScript = 'ran'</script></head>",
      );
      await route.fulfill({ response: upstream, body });
    });
    const response = await page.goto("/");
    const csp = response?.headers()["content-security-policy"] ?? "";
    expect(csp).toContain("script-src 'self'");
    expect(csp).not.toContain("'unsafe-eval'");
    const scriptPolicy = csp
      .split(";")
      .find((part) => part.trim().startsWith("script-src "))!;
    expect(scriptPolicy).not.toContain("'unsafe-inline'");
    const nonce = /'nonce-([^']+)'/.exec(scriptPolicy)?.[1];
    expect(nonce).toBeTruthy();
    expect(response?.headers()["cache-control"]).toContain("no-store");
    const scriptNonces = await page
      .locator("script:not(#untrusted-fixture)")
      .evaluateAll((scripts) =>
        scripts.map((script) => (script as HTMLScriptElement).nonce),
      );
    expect(scriptNonces.length).toBeGreaterThan(0);
    expect(scriptNonces.every((value) => value === nonce)).toBe(true);
    expect(
      await page.locator("html").getAttribute("data-untrusted-script"),
    ).toBeNull();
    const nextResponse = await page.request.get("/api/health");
    expect(nextResponse.headers()["content-security-policy"]).not.toContain(
      `'nonce-${nonce}'`,
    );
    await expect(page.getByLabel("Email")).toBeVisible();
    expect(errors).toEqual([]);
  });

  test("home page shows sign-in form", async ({ page }) => {
    await page.goto("/");
    await expect(page.getByRole("heading", { name: "Sign in" })).toBeVisible();
    await expect(page.getByLabel("Email")).toBeVisible();
  });

  test("login page loads", async ({ page }) => {
    await page.goto("/login");
    await expect(
      page.getByRole("heading", { name: "Welcome back" }),
    ).toBeVisible();
    await expect(page.getByRole("heading", { name: "Sign in" })).toBeVisible();
  });

  test("public project request page loads", async ({ page }) => {
    await page.goto("/request-project");
    await expect(
      page.getByRole("heading", { name: "Request a New Project" }),
    ).toBeVisible();
  });

  test("service request alias loads directly", async ({ page }) => {
    const response = await page.goto("/service-request");
    expect(response?.status()).toBe(200);
    await expect(page).toHaveURL("/service-request");
    await expect(
      page.getByRole("heading", { name: "Request a New Project" }),
    ).toBeVisible();
  });

  test("public invoice payment page loads", async ({ page }) => {
    await page.goto("/pay-invoice");
    await expect(
      page.getByRole("heading", { name: "Invoice Payment" }),
    ).toBeVisible();
    await expect(
      page.getByText(
        "Enter your invoice details below to pay securely with Stripe.",
      ),
    ).toBeVisible();
  });
});
