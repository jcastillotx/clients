function stripTrailingSlash(url: string): string {
  return url.replace(/\/+$/, "");
}

export function resolveAppBaseUrl(): string {
  const raw = process.env.NEXT_PUBLIC_APP_URL?.trim() || "http://localhost:3000";
  return stripTrailingSlash(raw);
}

export function buildPublicPayInvoiceUrl(invoiceNumber: string): string {
  const base = resolveAppBaseUrl();
  return `${base}/pay-invoice?invoice=${encodeURIComponent(invoiceNumber)}`;
}

export function formatInvoiceDate(iso: string | null | undefined): string {
  if (!iso) return "—";
  try {
    return new Date(iso).toLocaleDateString(undefined, {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  } catch {
    return "—";
  }
}

export function formatInvoiceAmount(amount: unknown): string {
  const n = typeof amount === "string" ? Number.parseFloat(amount) : Number(amount);
  if (!Number.isFinite(n)) return "0.00";
  return n.toFixed(2);
}

export function resolveSenderCompanyName(): string {
  return (
    process.env.NEXT_PUBLIC_APP_NAME?.trim() ||
    process.env.RESEND_FROM_NAME?.trim() ||
    "Your team"
  );
}

type ClientRow = {
  company_name?: string | null;
  contact_name?: string | null;
  email?: string | null;
  primary_contact_id?: string | null;
};

type UserRow = { name: string | null; email: string | null };

/**
 * Prefer billing contact (primary user), then client record email.
 */
export function resolveInvoiceRecipientEmail(
  client: ClientRow,
  primaryContact: UserRow | null,
): string | null {
  const fromUser = primaryContact?.email?.trim();
  if (fromUser) return fromUser;
  const fromClient = client.email?.trim();
  if (fromClient) return fromClient;
  return null;
}

export function resolveInvoiceClientDisplayName(
  client: ClientRow,
  primaryContact: UserRow | null,
): string {
  return (
    primaryContact?.name?.trim() ||
    client.contact_name?.trim() ||
    client.company_name?.trim() ||
    "Client"
  );
}
