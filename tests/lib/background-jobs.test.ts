import { beforeEach, describe, expect, it, vi } from "vitest";

type Context = {
  step: { run: <T>(name: string, fn: () => T | Promise<T>) => Promise<T> };
};
type Handler = (context: Context) => Promise<unknown>;
const m = vi.hoisted(() => ({
  admin: vi.fn(),
  from: vi.fn(),
  send: vi.fn(),
  render: vi.fn(),
  invoiceEmail: vi.fn(),
  generate: vi.fn(),
  responses: [] as Array<{ data: unknown; error: { message: string } | null }>,
  updates: [] as Array<Record<string, unknown>>,
}));
vi.mock("@/lib/inngest/client", () => ({
  inngest: {
    createFunction: (_config: unknown, _trigger: unknown, handler: Handler) =>
      handler,
  },
}));
vi.mock("@/lib/supabase/server", () => ({ createAdminClient: m.admin }));
vi.mock("@/lib/email/client", () => ({ sendEmail: m.send }));
vi.mock("@/lib/email/templates", () => ({
  renderEmailTemplateWithClient: m.render,
}));
vi.mock("@/lib/email/send-invoice-email", () => ({
  sendInvoiceEmailWithPdf: m.invoiceEmail,
}));
vi.mock("@/lib/invoices/generate-recurring", () => ({
  generateRecurringInvoice: m.generate,
}));
import { checkSLACompliance } from "@/lib/inngest/functions/sla-checks";
import { checkContractExpirations } from "@/lib/inngest/functions/contract-checks";
import { sendInvoiceReminders } from "@/lib/inngest/functions/invoice-reminders";
import { generateRecurringInvoices } from "@/lib/inngest/functions/recurring-invoices";
const jobs = [
  checkSLACompliance,
  checkContractExpirations,
  sendInvoiceReminders,
  generateRecurringInvoices,
];
const context: Context = { step: { run: async (_name, fn) => fn() } };
function run(job: unknown) {
  return (job as Handler)(context);
}
const id = "00000000-0000-4000-8000-000000000001";
beforeEach(() => {
  vi.clearAllMocks();
  m.responses.length = 0;
  m.updates.length = 0;
  vi.stubEnv("BACKGROUND_SCHEDULES_ENABLED", "true");
  m.admin.mockReturnValue({ from: m.from });
  m.from.mockImplementation(() => {
    const query = {
      select: () => query,
      eq: () => query,
      in: () => query,
      is: () => query,
      neq: () => query,
      gte: () => query,
      lte: () => query,
      lt: () => query,
      gt: () => query,
      single: () => query,
      maybeSingle: () => query,
      update: (patch: Record<string, unknown>) => {
        m.updates.push(patch);
        return query;
      },
      then: (resolve: (value: unknown) => void) =>
        Promise.resolve(m.responses.shift() ?? { data: [], error: null }).then(
          resolve,
        ),
    };
    return query;
  });
  m.send.mockResolvedValue({ success: true });
  m.invoiceEmail.mockResolvedValue({ success: true });
  m.render.mockResolvedValue({ subject: "QA", html: "QA", plainText: "QA" });
});
describe("scheduled job execution", () => {
  it.each(jobs.map((job, index) => [index, job] as const))(
    "keeps schedule %i paused until explicitly enabled",
    async (_index, job) => {
      vi.stubEnv("BACKGROUND_SCHEDULES_ENABLED", "false");
      expect(await run(job)).toEqual({
        skipped: true,
        reason: "schedules_disabled",
      });
      expect(m.admin).not.toHaveBeenCalled();
    },
  );
  it.each(jobs.map((job, index) => [index, job] as const))(
    "reports missing system credentials for schedule %i",
    async (_index, job) => {
      m.admin.mockImplementation(() => {
        throw Error("Missing service credentials");
      });
      await expect(run(job)).rejects.toThrow("Missing service credentials");
    },
  );
  it.each(jobs.map((job, index) => [index, job] as const))(
    "does not report a failed query as success for schedule %i",
    async (_index, job) => {
      m.responses.push({
        data: null,
        error: { message: "database unavailable" },
      });
      await expect(run(job)).rejects.toThrow("database unavailable");
    },
  );
  it("reads SLA deadlines from support tickets and records a successful warning", async () => {
    const now = Date.now();
    m.responses.push({
      data: [
        {
          id,
          subject: "QA",
          ticket_number: "QA-1",
          priority: "medium",
          created_at: new Date(now - 8 * 3600000).toISOString(),
          first_response_at: null,
          sla_response_due_at: new Date(now + 2 * 3600000).toISOString(),
          sla_resolution_due_at: null,
          sla_response_breached: false,
          sla_resolution_breached: false,
          sla_response_warned_at: null,
          sla_resolution_warned_at: null,
          sla_paused_duration_minutes: 0,
          assignee: { email: "staff@example.invalid" },
        },
      ],
      error: null,
    });
    await expect(run(checkSLACompliance)).resolves.toEqual({
      checked: 1,
      warnings: 1,
      breaches: 0,
    });
    expect(m.from).toHaveBeenCalledWith("support_tickets");
    expect(m.updates[0]).toHaveProperty("sla_response_warned_at");
  });
  it("leaves an invoice reminder retryable when email delivery fails", async () => {
    m.responses.push({
      data: [
        {
          id,
          client: {
            email: "client@example.invalid",
            company_name: "QA",
            primary_contact_id: null,
          },
        },
      ],
      error: null,
    });
    m.invoiceEmail.mockResolvedValue({ success: false });
    await expect(run(sendInvoiceReminders)).rejects.toThrow("delivery failed");
    expect(m.updates).toHaveLength(0);
  });
  it("does not record contract notification delivery when the mail provider fails", async () => {
    m.responses.push({
      data: [
        {
          id,
          title: "QA",
          contract_number: "QA-1",
          end_date: new Date(Date.now() + 20 * 86400000).toISOString(),
          client: { email: "client@example.invalid", company_name: "QA" },
        },
      ],
      error: null,
    });
    m.send.mockResolvedValue({ success: false });
    await expect(run(checkContractExpirations)).rejects.toThrow(
      "delivery failed",
    );
    expect(m.updates).toHaveLength(0);
  });
});
