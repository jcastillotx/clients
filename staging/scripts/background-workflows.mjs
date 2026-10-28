import fs from "node:fs";
import crypto from "node:crypto";
import { createRequire } from "node:module";
const require = createRequire(new URL("../../package.json", import.meta.url));
const postgres = require("postgres");
const root = new URL("../", import.meta.url);
const env = Object.fromEntries(
  fs
    .readFileSync(new URL(".env.staging", root), "utf8")
    .split("\n")
    .filter((l) => l && !l.startsWith("#"))
    .map((l) => {
      const i = l.indexOf("=");
      return [l.slice(0, i), l.slice(i + 1)];
    }),
);
for (const [key, port] of [
  ["NEXT_PUBLIC_APP_URL", "3100"],
  ["NEXT_PUBLIC_SUPABASE_URL", "55321"],
  ["DATABASE_URL", "55322"],
]) {
  const url = new URL(env[key]);
  if (url.hostname !== "127.0.0.1" || url.port !== port)
    throw Error("Isolated staging required");
}
Object.assign(process.env, env);
const sql = postgres(env.DATABASE_URL, { max: 4 });
const { generateRecurringInvoice, recurringInvoiceId } = require(
  new URL("artifacts/recurring-helper.cjs", root).pathname,
);
const fixtures = JSON.parse(
  fs.readFileSync(new URL(".accounts.json", root), "utf8"),
);
const client = fixtures.clients.alpha,
  user = fixtures.accounts.admin.id,
  staff = fixtures.accounts.staff.id;
const tag = "QA-background-" + crypto.randomUUID(),
  results = [],
  invoiceIds = [],
  contractIds = [],
  ticketIds = [];
function expect(value, message) {
  if (!value) throw Error(message);
}
async function test(name, fn) {
  try {
    await fn();
    results.push({ name, passed: true });
    console.log("PASS", name);
  } catch (e) {
    results.push({ name, passed: false, error: e.message });
    console.log("FAIL", name, e.message);
  }
  fs.writeFileSync(
    new URL("artifacts/background-workflows.json", root),
    JSON.stringify(results, null, 2),
  );
}
async function invoke(name) {
  const response = await fetch(
    "http://127.0.0.1:8288/v2/apps/kre8iv-clients/functions/" +
      name +
      "/invoke",
    {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify({ data: {} }),
    },
  );
  const body = await response.json();
  expect(response.ok, "Job invoke failed");
  for (let i = 0; i < 240; i++) {
    const result = await fetch(
      "http://127.0.0.1:8288/v1/runs/" + body.data.runId,
    ).then((r) => r.json());
    const status = result.data.status.toLowerCase();
    if (status === "completed") return;
    if (["failed", "cancelled"].includes(status))
      throw Error("Job " + name + " " + status);
    await new Promise((r) => setTimeout(r, 250));
  }
  throw Error("Job timed out");
}
async function template(description) {
  const [invoice] =
    await sql`insert into invoices(invoice_number,client_id,amount,subtotal,status,is_recurring,recurring_interval,next_recurring_date,created_by) values(${tag + description},${client},25,25,'sent',true,'monthly',now()-interval '1 day',${user}) returning id,next_recurring_date`;
  invoiceIds.push(invoice.id);
  await sql`insert into invoice_items(invoice_id,description,quantity,unit_price,amount) values(${invoice.id},${tag + description},1,25,25)`;
  return {
    ...invoice,
    period: new Date(invoice.next_recurring_date).toISOString(),
  };
}
try {
  const [smtp] =
    await sql`select value from system_settings where category='email' and key='smtp_host'`;
  expect(smtp?.value === "127.0.0.1", "Local mail capture required");
  await test("Concurrent recurring generation commits one invoice and one item", async () => {
    const t = await template("-concurrent");
    const id = recurringInvoiceId(t.id, t.period);
    invoiceIds.push(id);
    const generated = await Promise.all(
      Array.from({ length: 8 }, () => generateRecurringInvoice(t.id, t.period)),
    );
    expect(
      generated.every((x) => x?.id === id),
      "Retry returned inconsistent invoice",
    );
    const rows = await sql`select id from invoices where id=${id}`;
    expect(rows.length === 1, "Duplicate recurring invoice");
    const items =
      await sql`select id from invoice_items where invoice_id=${id}`;
    expect(items.length === 1, "Missing or duplicate line items");
  });
  await test("A line-item failure rolls back invoice and schedule advancement", async () => {
    const t = await template("-rollback"),
      id = recurringInvoiceId(t.id, t.period);
    invoiceIds.push(id);
    await sql.unsafe(
      `CREATE OR REPLACE FUNCTION public.qa_background_fail_item() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN IF NEW.description = '${tag}-rollback' THEN RAISE EXCEPTION 'synthetic line item failure'; END IF; RETURN NEW; END $$; CREATE TRIGGER qa_background_fail_item BEFORE INSERT ON public.invoice_items FOR EACH ROW EXECUTE FUNCTION public.qa_background_fail_item();`,
    );
    try {
      let failed = false;
      try {
        await generateRecurringInvoice(t.id, t.period);
      } catch {
        failed = true;
      }
      expect(failed, "Injected failure did not surface");
      const rows = await sql`select id from invoices where id=${id}`;
      expect(rows.length === 0, "Partial invoice committed");
      const [unchanged] =
        await sql`select next_recurring_date from invoices where id=${t.id}`;
      expect(
        new Date(unchanged.next_recurring_date).toISOString() === t.period,
        "Schedule advanced despite failure",
      );
    } finally {
      await sql.unsafe(
        "DROP TRIGGER IF EXISTS qa_background_fail_item ON public.invoice_items; DROP FUNCTION IF EXISTS public.qa_background_fail_item();",
      );
    }
    await sql`update invoices set is_recurring=false where id=${t.id}`;
  });
  await test("Inngest recurring job creates, emails and records a sent invoice", async () => {
    const t = await template("-delivery"),
      id = recurringInvoiceId(t.id, t.period);
    invoiceIds.push(id);
    await invoke("generate-recurring-invoices");
    const [invoice] = await sql`select status from invoices where id=${id}`;
    expect(invoice?.status === "sent", "Recurring invoice not sent");
    const mail = await fetch("http://127.0.0.1:55324/api/v1/messages").then(
      (r) => r.json(),
    );
    expect(
      mail.messages.some((m) => (m.Subject || "").includes("REC-" + id)),
      "Recurring invoice mail missing",
    );
  });
  await test("Contract expiry windows notify once and persist without a user session", async () => {
    for (const days of [20, 5]) {
      const [c] =
        await sql`insert into contracts(title,contract_number,client_id,type,status,start_date,end_date) values(${tag},${tag + "-" + days},${client},'service','active',now()-interval '10 days',now()+${days}*interval '1 day') returning id`;
      contractIds.push(c.id);
    }
    await invoke("check-contract-expirations");
    const rows =
      await sql`select id,notified_expiring_30_at,notified_expiring_7_at from contracts where id in ${sql(contractIds)}`;
    expect(
      rows.find((r) => r.id === contractIds[0])?.notified_expiring_30_at,
      "30-day notice missing",
    );
    const urgent = rows.find((r) => r.id === contractIds[1]);
    expect(
      urgent?.notified_expiring_7_at && !urgent.notified_expiring_30_at,
      "Urgent contract got duplicate notice windows",
    );
  });
  await test("Contract expiration and auto-renewal persist valid new periods", async () => {
    const ids = [];
    for (const renew of [false, true]) {
      const [c] =
        await sql`insert into contracts(title,contract_number,client_id,type,status,start_date,end_date,auto_renew,notified_expiring_30_at,notified_expiring_7_at) values(${tag},${tag + "-" + renew},${client},'service','active',now()-interval '31 days',now()-interval '1 day',${renew},now(),now()) returning id`;
      contractIds.push(c.id);
      ids.push(c.id);
    }
    await invoke("check-contract-expirations");
    const [expired] =
      await sql`select status from contracts where id=${ids[0]}`;
    expect(expired.status === "expired", "Contract not expired");
    const [renewed] =
      await sql`select renewed_count,end_date,notified_expiring_30_at,notified_expiring_7_at from contracts where id=${ids[1]}`;
    expect(
      renewed.renewed_count === 1 &&
        new Date(renewed.end_date) > new Date() &&
        !renewed.notified_expiring_30_at &&
        !renewed.notified_expiring_7_at,
      "Contract renewal invalid",
    );
  });
  await test("Support SLA warnings and breaches use persisted ticket deadlines", async () => {
    for (const minutes of [15, -15]) {
      const [t] =
        await sql`insert into support_tickets(client_id,created_by,assigned_to,ticket_number,subject,description,category,status,priority,created_at,sla_response_due_at,sla_resolution_due_at) values(${client},${user},${staff},${tag + "-" + minutes},${tag},'Synthetic SLA case','general','open','medium',now()-interval '85 minutes',now()+${minutes}*interval '1 minute',now()+interval '10 hours') returning id`;
      ticketIds.push(t.id);
    }
    await invoke("check-sla-compliance");
    const [warning] =
      await sql`select sla_response_warned_at from support_tickets where id=${ticketIds[0]}`;
    expect(warning.sla_response_warned_at, "SLA warning not saved");
    const [breach] =
      await sql`select sla_response_breached,sla_response_breached_at from support_tickets where id=${ticketIds[1]}`;
    expect(
      breach.sla_response_breached && breach.sla_response_breached_at,
      "SLA breach not saved",
    );
    const before = await fetch("http://127.0.0.1:55324/api/v1/messages").then(
      (r) => r.json(),
    );
    await invoke("check-sla-compliance");
    const after = await fetch("http://127.0.0.1:55324/api/v1/messages").then(
      (r) => r.json(),
    );
    expect(
      after.messages.length === before.messages.length,
      "SLA notification repeated on next schedule",
    );
  });
} finally {
  if (ticketIds.length)
    await sql`delete from support_tickets where id in ${sql(ticketIds)}`;
  if (contractIds.length)
    await sql`delete from contracts where id in ${sql(contractIds)}`;
  if (invoiceIds.length)
    await sql`delete from invoices where id in ${sql(invoiceIds)}`;
  await sql.end();
}
console.log(
  JSON.stringify({
    passed: results.filter((r) => r.passed).length,
    total: results.length,
    syntheticFixturesRemoved: true,
  }),
);
if (results.some((r) => !r.passed)) process.exitCode = 1;
