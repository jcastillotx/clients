import { afterEach, describe, expect, it, vi } from "vitest";
import { sendViaResend } from "@/lib/email/providers";
vi.mock("@/lib/supabase/server", () => ({
  createAdminClientIfAvailable: vi.fn(),
}));
afterEach(() => {
  vi.unstubAllGlobals();
});
describe("Resend HTTPS transport", () => {
  it("preserves recipients, reply-to and base64 attachments without the vulnerable SDK", async () => {
    const fetcher = vi
      .fn()
      .mockResolvedValue(new Response(JSON.stringify({ id: "fixture" })));
    vi.stubGlobal("fetch", fetcher);
    await sendViaResend(
      {
        provider: "resend",
        api_key: "fixture",
        from_email: "Portal <sender@example.test>",
      },
      {
        to: ["recipient@example.test"],
        subject: "Fixture",
        text: "Message",
        replyTo: "reply@example.test",
        attachments: [
          { filename: "report.txt", content: Buffer.from("report") },
        ],
      },
    );
    const body = JSON.parse(fetcher.mock.calls[0][1].body);
    expect(fetcher.mock.calls[0][0]).toBe("https://api.resend.com/emails");
    expect(body.from).toBe("Portal <sender@example.test>");
    expect(body.to).toEqual(["recipient@example.test"]);
    expect(body.reply_to).toBe("reply@example.test");
    expect(body.attachments[0].content).toBe(
      Buffer.from("report").toString("base64"),
    );
  });
  it("does not claim delivery when the provider rejects the request", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        new Response(JSON.stringify({ message: "Rejected" }), {
          status: 403,
        }),
      ),
    );
    await expect(
      sendViaResend(
        { provider: "resend", api_key: "fixture" },
        { to: "recipient@example.test", subject: "Fixture" },
      ),
    ).rejects.toThrow("Rejected");
  });
});
