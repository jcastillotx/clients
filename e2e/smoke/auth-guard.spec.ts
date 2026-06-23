import { test, expect } from "@playwright/test";

test.describe("auth guards", () => {
  test("unauthenticated dashboard access redirects to home", async ({ page }) => {
    await page.goto("/dashboard");
    await expect(page).toHaveURL("/");
    await expect(page.getByRole("heading", { name: "Sign in" })).toBeVisible();
  });

  test("unauthenticated admin access redirects to home", async ({ page }) => {
    await page.goto("/admin");
    await expect(page).toHaveURL("/");
    await expect(page.getByRole("heading", { name: "Sign in" })).toBeVisible();
  });
});
