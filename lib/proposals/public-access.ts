import {
  createSignedToken,
  getSigningSecret,
  verifySignedToken,
} from "../auth/signed-token";

const PROPOSAL_TOKEN_TTL_SECONDS = 60 * 60 * 24 * 14;

function getProposalSecret() {
  return getSigningSecret("PROPOSAL_PUBLIC_LINK_SECRET");
}

export function createProposalAccessToken(proposalId: string): string | null {
  const secret = getProposalSecret();
  if (!secret) {
    return null;
  }

  return createSignedToken(
    { proposalId, kind: "proposal_access" },
    secret,
    PROPOSAL_TOKEN_TTL_SECONDS,
  );
}

export function verifyProposalAccessToken(
  proposalId: string,
  token?: string | null,
): boolean {
  if (!token) {
    return false;
  }

  const secret = getProposalSecret();
  if (!secret) {
    return false;
  }

  const payload = verifySignedToken(token, secret);
  return (
    payload?.kind === "proposal_access" && payload?.proposalId === proposalId
  );
}
