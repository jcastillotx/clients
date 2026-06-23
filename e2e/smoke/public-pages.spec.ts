import { test, expect } from "@playwright/test";

test.describe("public pages smoke", () => {
  test("home page shows sign-in form", async ({ page }) => {
    await page.goto("/");
    await expect(page.getByRole("heading", { name: "Sign in" })).toBeVisible();
    await expect(page.getByLabel("Email")).toBeVisible();
  });

  test("login page loads", async ({ page }) => {
    await page.goto("/login");
    await expect(page.getByRole("heading", { name: "Welcome back" })).toBeVisible();
    await expect(page.getByRole("heading", { name: "Sign in" })).toBeVisible();
  });

  test("public project request page loads", async ({ page }) => {
    await page.goto("/request-project");
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
      page.getByText("Enter your invoice details below to pay securely with Stripe."),
    ).toBeVisible();
  });
});
