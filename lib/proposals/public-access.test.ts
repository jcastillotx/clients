import { beforeEach, describe, expect, it } from "vitest";
import {
  createProposalAccessToken,
  verifyProposalAccessToken,
} from "./public-access";

describe("proposal public access tokens", () => {
  beforeEach(() => {
    process.env.PROPOSAL_PUBLIC_LINK_SECRET = "test-secret";
  });

  it("validates correct proposal token", () => {
    const token = createProposalAccessToken("proposal-1");
    expect(token).toBeTruthy();
    expect(verifyProposalAccessToken("proposal-1", token)).toBe(true);
    expect(verifyProposalAccessToken("proposal-2", token)).toBe(false);
  });
});
