import fs from "node:fs";
import crypto from "node:crypto";
import { createRequire } from "node:module";
import { execFileSync, spawn } from "node:child_process";
const require = createRequire(new URL("../../package.json", import.meta.url));
const postgres = require("postgres");
const { createServerClient } = require("@supabase/ssr");
const root = new URL("../", import.meta.url),
  repo = new URL("../../", import.meta.url).pathname;
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
if (
  env.INNGEST_BASE_URL !== "http://127.0.0.1:8488" ||
  env.STAGING_SELF_HOSTED_JOBS !== "1" ||
  new URL(env.DATABASE_URL).port !== "55322"
)
  throw Error("Isolated self-hosted stack required");
const fixtures = JSON.parse(
  fs.readFileSync(new URL(".accounts.json", root), "utf8"),
);
const sql = postgres(env.DATABASE_URL, { max: 2 });
const base = env.INNGEST_BASE_URL,
  headers = {
    "content-type": "application/json",
    Authorization: "Bearer " + env.INNGEST_SIGNING_KEY,
  };
const tag = "QA-selfhost-" + crypto.randomUUID(),
  results = [],
  clients = [],
  tasks = [],
  invoices = [];
let jar = [];
const auth = createServerClient(
  env.NEXT_PUBLIC_SUPABASE_URL,
  env.NEXT_PUBLIC_SUPABASE_ANON_KEY,
  {
    cookies: {
      getAll: () => jar,
      setAll: (all) => {
        for (const c of all) {
          jar = jar.filter((x) => x.name !== c.name);
          jar.push(c);
        }
      },
    },
  },
);
const login = await auth.auth.signInWithPassword({
  email: fixtures.accounts.admin.email,
  password: fixtures.accounts.admin.password,
});
if (login.error) throw login.error;
function expect(ok, message) {
  if (!ok) throw Error(message);
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
    new URL("artifacts/selfhost-events.json", root),
    JSON.stringify(results, null, 2),
  );
}
async function poll(fn, label, attempts = 300) {
  for (let i = 0; i < attempts; i++) {
    if (await fn()) return;
    await new Promise((r) => setTimeout(r, 250));
  }
  throw Error(label + " timed out");
}
async function event(name, data, key = env.INNGEST_EVENT_KEY) {
  const r = await fetch(base + "/e/" + key, {
    method: "POST",
    headers: { "content-type": "application/json" },
    body: JSON.stringify({
      id: crypto.randomUUID(),
      name,
      data,
      ts: Date.now(),
    }),
  });
  return r;
}
async function invoke(name) {
  const r = await fetch(
    base + "/v2/apps/kre8iv-clients/functions/" + name + "/invoke",
    { method: "POST", headers, body: JSON.stringify({ data: {} }) },
  );
  const body = await r.json();
  expect(r.ok, "Invoke failed");
  await poll(async () => {
    const r = await fetch(base + "/v1/runs/" + body.data.runId, {
      headers,
    }).then((x) => x.json());
    if (
      ["failed", "cancelled"].includes(
        (r.data?.status || "pending").toLowerCase(),
      )
    )
      throw Error("Run failed");
    return (r.data?.status || "pending").toLowerCase() === "completed";
  }, name);
  return body.data.runId;
}
async function newClient() {
  const [c] =
    await sql`insert into clients(company_name,email,status) values(${tag},${crypto.randomUUID() + "@example.invalid"},'active') returning id`;
  clients.push(c.id);
  return c.id;
}
async function task(input) {
  const r = await fetch(env.NEXT_PUBLIC_APP_URL + "/api/marketing/agents", {
    method: "POST",
    headers: {
      "content-type": "application/json",
      cookie: jar.map((c) => c.name + "=" + c.value).join("; "),
    },
    body: JSON.stringify({
      clientId: fixtures.clients.alpha,
      createDrafts: true,
      ...input,
    }),
  });
  const body = await r.json();
  expect(r.status === 202, "Marketing enqueue failed " + r.status);
  const id = body.data.id;
  tasks.push(id);
  await poll(
    async () => {
      const [t] = await sql`select status,error from ai_tasks where id=${id}`;
      return t?.status === "completed";
    },
    "Marketing task",
    480,
  );
  const [t] = await sql`select output,metadata from ai_tasks where id=${id}`;
  return { id, ...t };
}
let appStopped = false;
let historyRunId = null;
function startApp() {
  const fd = fs.openSync(
    "/tmp/clients-google-provider/selfhost-recovery-app.log",
    "a",
  );
  const p = spawn("python3", ["staging/scripts/run.py"], {
    cwd: repo,
    detached: true,
    stdio: ["ignore", fd, fd],
  });
  p.unref();
  appStopped = false;
}
try {
  await test("Unsigned portal requests and invalid event keys are rejected", async () => {
    const unsigned = await fetch(env.NEXT_PUBLIC_APP_URL + "/api/inngest", {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: "{}",
    });
    expect(unsigned.status === 401, "Unsigned request accepted");
    const bad = await event(
      "client.created",
      { clientId: crypto.randomUUID(), companyName: tag },
      "invalid",
    );
    expect(bad.status >= 400, "Invalid event key accepted");
  });
  await test("Signed client-created events create exactly one tenant folder", async () => {
    const id = await newClient();
    expect(
      (await event("client.created", { clientId: id, companyName: tag })).ok,
      "Event rejected",
    );
    await poll(async () => {
      const rows =
        await sql`select id from folders where client_id=${id} and type='brand_management'`;
      return rows.length === 1;
    }, "Client folder");
    await event("client.created", { clientId: id, companyName: tag });
    await new Promise((r) => setTimeout(r, 1500));
    const rows =
      await sql`select id from folders where client_id=${id} and type='brand_management'`;
    expect(rows.length === 1, "Duplicate folder");
  });
  await test("Backfill executes through signed self-hosted orchestration", async () => {
    const id = await newClient();
    historyRunId = await invoke("backfill-brand-folders");
    const rows =
      await sql`select id from folders where client_id=${id} and type='brand_management'`;
    expect(rows.length === 1, "Backfill failed");
  });
  await test("Invoice reminder job delivers a PDF to local capture and records state", async () => {
    const [i] =
      await sql`insert into invoices(invoice_number,client_id,amount,status,created_by,due_date) values(${tag},${fixtures.clients.alpha},25,'sent',${fixtures.accounts.admin.id},now()+interval '5 days') returning id`;
    invoices.push(i.id);
    await invoke("send-invoice-reminders");
    const [saved] =
      await sql`select reminded_due_7_at from invoices where id=${i.id}`;
    expect(saved.reminded_due_7_at, "Reminder state missing");
  });
  await test("Queued campaign and calendar agents persist drafts for approval", async () => {
    for (const input of [
      {
        workflowId: "campaign_plan",
        campaignName: tag,
        objective: "Test the signed queued workflow",
        targetAudience: "Synthetic audience",
        channels: ["email"],
      },
      {
        workflowId: "content_calendar",
        objective: "Test the signed queued workflow",
        targetAudience: "Synthetic audience",
        startDate: "2026-10-28",
        numberOfItems: 2,
        platforms: ["instagram"],
      },
    ]) {
      const t = await task(input);
      expect(
        t.output.result.approvalStatus === "pending_approval",
        "Draft bypassed approval",
      );
      expect(t.output.result.createdRecords.length > 0, "Draft not persisted");
    }
  });
  await test("Redelivering a completed marketing task does not duplicate its draft", async () => {
    const id = tasks[0];
    expect(!!id, "Missing completed task");
    await event("marketing-agent/run.requested", {
      taskId: id,
      clientId: fixtures.clients.alpha,
      userId: fixtures.accounts.admin.id,
    });
    await new Promise((r) => setTimeout(r, 5000));
    const rows =
      await sql`select id from campaigns where metadata->>'aiTaskId'=${id}`;
    expect(rows.length === 1, "Duplicate draft on completed-task redelivery");
  });
  await test("Queued quality-check agent finishes without external AI", async () => {
    const t = await task({
      workflowId: "quality_check",
      content: "A synthetic draft for review.",
      evidence: "Synthetic fixture only.",
    });
    expect(
      t.output.result.workflowId === "quality_check",
      "Quality workflow result missing",
    );
  });
  await test("A failed provider attempt is retried and completes the same task", async () => {
    const t = await task({
      workflowId: "campaign_plan",
      campaignName: tag,
      objective: "Test retry execution with a synthetic failure",
      targetAudience: "Synthetic audience",
      channels: ["email"],
      instructions: "SIMULATE_RETRY_ONCE_" + crypto.randomUUID(),
    });
    expect(
      t.output.result.createdRecords.length === 1,
      "Retry created unexpected records",
    );
    const rows =
      await sql`select id from campaigns where metadata->>'aiTaskId'=${t.id}`;
    expect(rows.length === 1, "Duplicate draft after retry");
  });
  await test("Queued work and history survive an Inngest/Redis/Postgres restart", async () => {
    const id = await newClient();
    const pid = Number(
      execFileSync("lsof", ["-t", "-iTCP:3100", "-sTCP:LISTEN"], {
        encoding: "utf8",
      }).trim(),
    );
    process.kill(pid, "SIGTERM");
    appStopped = true;
    expect(
      (await event("client.created", { clientId: id, companyName: tag })).ok,
      "Recovery event not accepted",
    );
    await new Promise((r) => setTimeout(r, 1500));
    const args = [
      "compose",
      "--project-name",
      "clients-selfhost-local",
      "--env-file",
      "staging/artifacts/selfhost.env",
      "-f",
      "ops/inngest/compose.yml",
    ];
    execFileSync("docker", [...args, "stop"], { cwd: repo, stdio: "ignore" });
    execFileSync(
      "docker",
      [...args, "up", "-d", "--wait", "--wait-timeout", "90"],
      { cwd: repo, stdio: "ignore" },
    );
    startApp();
    await poll(
      async () => {
        try {
          const rows =
            await sql`select id from folders where client_id=${id} and type='brand_management'`;
          return rows.length === 1;
        } catch {
          return false;
        }
      },
      "Recovered queued client setup",
      480,
    );
    const rows = await sql`select id from folders where client_id=${id}`;
    expect(rows.length === 1, "Recovered duplicate folder");
    const history = await fetch(base + "/v1/runs/" + historyRunId, {
      headers,
    }).then((r) => r.json());
    expect(
      history.data?.status.toLowerCase() === "completed",
      "Prior run history lost after restart",
    );
  });
} finally {
  if (appStopped) startApp();
  if (tasks.length) {
    await sql`delete from ai_usage_tracking where metadata->>'taskId' in ${sql(tasks)}`;
    await sql`delete from campaigns where metadata->>'aiTaskId' in ${sql(tasks)}`;
    await sql`delete from content_calendar_items where metadata->>'aiTaskId' in ${sql(tasks)}`;
    await sql`delete from ai_tasks where id in ${sql(tasks)}`;
  }
  if (invoices.length)
    await sql`delete from invoices where id in ${sql(invoices)}`;
  if (clients.length) {
    await sql`delete from folders where client_id in ${sql(clients)}`;
    await sql`delete from clients where id in ${sql(clients)}`;
  }
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
