import { beforeEach, describe, expect, it, vi } from "vitest";
import { sendClientWelcomeEmail, sendWelcomeEmail } from "@/lib/email/send-welcome-email";
import { sendEmail } from "@/lib/email/client";
import { getEmailBrandContext } from "@/lib/email/branding";

vi.mock("@/lib/email/client", () => ({ sendEmail: vi.fn() }));
vi.mock("@/lib/branding/get-branding", () => ({ getPortalBranding: vi.fn() }));
vi.mock("@/lib/email/branding", async (importOriginal) => ({
  ...await importOriginal<typeof import("@/lib/email/branding")>(), getEmailBrandContext: vi.fn(),
}));
vi.mock("@/lib/supabase/redirect-url", () => ({ getAuthBaseUrl: () => "https://clients.kre8ivdesigns.com" }));

describe("portal welcome email", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(getEmailBrandContext).mockResolvedValue({
      app_name: "KRE8IV Clients", brand_name: "Kre8ivDesigns", brand_logo_url: "https://brand.test/logo.png",
      brand_primary_color: "#5F5F82", brand_secondary_color: "#ffffff",
      support_email: "help@brand.test", site_url: "https://clients.kre8ivdesigns.com",
    });
    vi.mocked(sendEmail).mockResolvedValue({ success: true });
  });

  it("includes branding, escaped recipient details, and usable sign-in and reset links", async () => {
    await sendWelcomeEmail({ name: '<img src=x onerror="alert(1)">', email: "jane@example.com" });
    const message = vi.mocked(sendEmail).mock.calls[0][0];
    expect(message.subject).toBe("Welcome to KRE8IV Clients");
    expect(message.html).toContain("https://brand.test/logo.png");
    expect(message.html).toContain("https://clients.kre8ivdesigns.com/login");
    expect(message.html).toContain("https://clients.kre8ivdesigns.com/forgot-password");
    expect(message.html).toContain("&lt;img");
    expect(message.html).not.toContain('<img src=x');
    expect(message.text).toContain("jane@example.com");
  });

  it("welcomes a client with branding without claiming login access", async () => {
    await sendClientWelcomeEmail({ companyName: "Acme <Team>", email: "team@example.com" });
    const message = vi.mocked(sendEmail).mock.calls[0][0];
    expect(message.to).toBe("team@example.com");
    expect(message.subject).toBe("Welcome to Kre8ivDesigns");
    expect(message.html).toContain("https://brand.test/logo.png");
    expect(message.html).toContain("Acme &lt;Team&gt;");
    expect(message.html).toContain("help@brand.test");
    expect(message.text).toContain("next steps");
    expect(message.html).not.toContain("Your portal account is ready");
  });

  it("returns delivery failure without throwing", async () => {
    vi.mocked(sendEmail).mockResolvedValue({ success: false, error: "Rejected" });
    expect((await sendWelcomeEmail({ name: "Jane", email: "jane@example.com" })).success).toBe(false);
  });
});
