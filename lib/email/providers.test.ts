import { afterEach, describe, expect, it, vi } from "vitest";
import { sendViaMicrosoftGraph } from "./providers";

describe("Microsoft Graph email provider", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it("sets the configured from address for Microsoft alias/send-as mail", async () => {
    const fetchMock = vi.fn(async (_input: RequestInfo | URL, _init?: RequestInit) =>
      new Response(null, { status: 202 }),
    );
    vi.stubGlobal("fetch", fetchMock);

    await sendViaMicrosoftGraph(
      {
        provider: "office365",
        from_email: "noreply@kre8ivdesigns.com",
        from_name: "Kre8ivdesigns Marketing",
        oauth_account_email: "j.castillo@castillocollective.com",
        oauth_access_token: "fresh-token",
        oauth_token_expiry: new Date(Date.now() + 60 * 60 * 1000).toISOString(),
      },
      {
        to: "client@example.com",
        subject: "Alias test",
        html: "<p>Hello</p>",
      },
    );

    expect(fetchMock).toHaveBeenCalledOnce();
    const call = fetchMock.mock.calls[0];
    if (!call) {
      throw new Error("Expected Microsoft Graph fetch call");
    }
    const init = call[1] as RequestInit;
    const body = JSON.parse(String(init.body));

    expect(body.message.from).toEqual({
      emailAddress: {
        address: "noreply@kre8ivdesigns.com",
        name: "Kre8ivdesigns Marketing",
      },
    });
    expect(body.message.toRecipients).toEqual([
      { emailAddress: { address: "client@example.com" } },
    ]);
  });

  it("maps Microsoft send-as permission failures to an actionable message", async () => {
    const fetchMock = vi.fn(async (_input: RequestInfo | URL, _init?: RequestInit) =>
      Response.json(
        {
          error: {
            message:
              "The user account which was used to submit this request does not have the right to send mail on behalf of the specified sending account., Cannot submit message.",
          },
        },
        { status: 403 },
      ),
    );
    vi.stubGlobal("fetch", fetchMock);

    await expect(
      sendViaMicrosoftGraph(
        {
          provider: "office365",
          from_email: "noreply@kre8ivdesigns.com",
          from_name: "Kre8ivdesigns Marketing",
          oauth_account_email: "j.castillo@castillocollective.com",
          oauth_access_token: "fresh-token",
          oauth_token_expiry: new Date(Date.now() + 60 * 60 * 1000).toISOString(),
        },
        {
          to: "client@example.com",
          subject: "Alias test",
          html: "<p>Hello</p>",
        },
      ),
    ).rejects.toThrow(
      "Connected account j.castillo@castillocollective.com is not allowed to send as noreply@kre8ivdesigns.com.",
    );
  });
});
