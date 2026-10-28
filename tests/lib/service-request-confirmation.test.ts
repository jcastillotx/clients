import { beforeEach, describe, expect, it, vi } from "vitest";
import { sendServiceRequestConfirmation } from "@/lib/email/service-request-confirmation";
import { sendEmail } from "@/lib/email/client";
import { getEmailBrandContext } from "@/lib/email/branding";

vi.mock("@/lib/email/client", () => ({ sendEmail: vi.fn() }));
vi.mock("@/lib/email/branding", async (importOriginal) => ({
  ...await importOriginal<typeof import("@/lib/email/branding")>(), getEmailBrandContext: vi.fn(),
}));

describe("public service request confirmation", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(getEmailBrandContext).mockResolvedValue({
      app_name: "Client Portal", brand_name: "Kre8ivDesigns", brand_logo_url: "https://brand.test/logo.png",
      brand_primary_color: "#5F5F82", brand_secondary_color: "#ffffff",
      support_email: "help@brand.test", site_url: "https://clients.kre8ivdesigns.com",
    });
    vi.mocked(sendEmail).mockResolvedValue({ success: true });
  });

  it("sends a branded receipt with a reference only to the submitting address", async () => {
    expect(await sendServiceRequestConfirmation({ email: "submitter@example.com", requestId: "request-123" })).toBe(true);
    const message = vi.mocked(sendEmail).mock.calls[0][0];
    expect(message.to).toBe("submitter@example.com");
    expect(message.replyTo).toBe("help@brand.test");
    expect(message.cc).toBeUndefined();
    expect(message.bcc).toBeUndefined();
    expect(message.subject).toBe("Service request received | Kre8ivDesigns");
    expect(message.html).toContain("https://brand.test/logo.png");
    expect(message.html).toContain("#5F5F82");
    expect(message.html).toContain("request-123");
    expect(message.html).toContain("awaiting review");
    expect(message.text).toContain("Request reference: request-123");
    expect(message.text).toContain("no action is required");
    expect(message.html).not.toContain("/projects/requests/");
    expect(message.html).not.toContain("account is ready");
  });

  it("escapes the request reference in HTML", async () => {
    await sendServiceRequestConfirmation({ email: "submitter@example.com", requestId: "<script>alert(1)</script>" });
    const message = vi.mocked(sendEmail).mock.calls[0][0];
    expect(message.html).toContain("&lt;script&gt;");
    expect(message.html).not.toContain("<script>");
  });

  it("reports provider rejection without claiming the receipt was sent", async () => {
    vi.mocked(sendEmail).mockResolvedValue({ success: false, error: "Rejected" });
    expect(await sendServiceRequestConfirmation({ email: "submitter@example.com", requestId: "request-123" })).toBe(false);
  });

  it("handles unavailable branding without throwing", async () => {
    vi.mocked(getEmailBrandContext).mockRejectedValueOnce(new Error("Branding unavailable"));
    expect(await sendServiceRequestConfirmation({ email: "submitter@example.com", requestId: "request-123" })).toBe(false);
    expect(sendEmail).not.toHaveBeenCalled();
  });
});
