import { test, expect } from "@playwright/test";

test.describe("public pages smoke", () => {
  test("production sign-in loads without an unsafe-eval allowance", async ({
    page,
  }) => {
    const errors: string[] = [];
    page.on("pageerror", (error) => errors.push(error.message));
    const response = await page.goto("/");
    const csp = response?.headers()["content-security-policy"] ?? "";
    expect(csp).toContain("script-src 'self'");
    expect(csp).not.toContain("'unsafe-eval'");
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
