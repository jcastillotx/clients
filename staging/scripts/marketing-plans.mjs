import fs from "node:fs";
import { createRequire } from "node:module";
const require = createRequire(new URL("../../package.json", import.meta.url));
const { createServerClient } = require("@supabase/ssr"),
  postgres = require("postgres");
const root = new URL("../", import.meta.url);
const env = Object.fromEntries(
  fs
    .readFileSync(new URL(".env.staging", root), "utf8")
    .trim()
    .split("\n")
    .map((l) => {
      const i = l.indexOf("=");
      return [l.slice(0, i), l.slice(i + 1)];
    }),
);
if (!/^http:\/\/(127\.0\.0\.1|localhost):3100$/.test(env.NEXT_PUBLIC_APP_URL))
  throw Error("Local isolated app required");
for (const [key, port] of [
  ["DATABASE_URL", "55322"],
  ["NEXT_PUBLIC_SUPABASE_URL", "55321"],
]) {
  const u = new URL(env[key]);
  if (!["127.0.0.1", "localhost"].includes(u.hostname) || u.port !== port)
    throw Error("Dedicated local staging service required: " + key);
}
const fixtures = JSON.parse(
  fs.readFileSync(new URL(".accounts.json", root), "utf8"),
);
const sql = postgres(env.DATABASE_URL, { max: 1 }),
  results = [];
async function check(name, fn) {
  try {
    await fn();
    results.push({ name, passed: true });
    console.log("PASS", name);
  } catch (e) {
    results.push({ name, passed: false, error: e.message });
    console.log("FAIL", name, e.message);
  }
  fs.writeFileSync(
    new URL("artifacts/marketing-plans.json", root),
    JSON.stringify(results, null, 2),
  );
}
function expect(v, m) {
  if (!v) throw Error(m);
}
async function session(name) {
  let jar = [];
  const c = createServerClient(
    env.NEXT_PUBLIC_SUPABASE_URL,
    env.NEXT_PUBLIC_SUPABASE_ANON_KEY,
    {
      cookies: {
        getAll: () => jar,
        setAll: (all) => {
          for (const x of all) {
            jar = jar.filter((y) => y.name !== x.name);
            jar.push(x);
          }
        },
      },
    },
  );
  const a = fixtures.accounts[name];
  const { error } = await c.auth.signInWithPassword({
    email: a.email,
    password: a.password,
  });
  if (error) throw error;
  return () => jar.map((x) => x.name + "=" + x.value).join("; ");
}
const a = await session("alpha"),
  b = await session("beta");
async function call(cookie, path, body) {
  const r = await fetch(env.NEXT_PUBLIC_APP_URL + path, {
    method: body ? "POST" : "GET",
    headers: { cookie: cookie?.() || "", "content-type": "application/json" },
    body: body ? JSON.stringify(body) : undefined,
  });
  return { status: r.status, json: await r.json() };
}

const [complete] =
  await sql`select id from marketing_plans where slug='complete'`;
const [essentials] =
  await sql`select id from marketing_plans where slug='essentials'`;
await check("Clients cannot see unpublished draft plans", async () => {
  const r = await call(a, "/api/marketing/plans");
  expect(
    r.status === 200 && r.json.data.plans.length === 0,
    "draft plans exposed for purchase",
  );
});
await check("Clients cannot buy an unpublished plan", async () => {
  const r = await call(a, "/api/marketing/plans/checkout", {
    planId: complete.id,
  });
  expect(r.status === 403, "draft checkout allowed");
});
await check(
  "Essentials filters tool catalog and denies paid media methodology and execution",
  async () => {
    await sql`update client_marketing_plans set plan_id=${essentials.id} where client_id=${fixtures.clients.alpha}`;
    const r = await call(a, "/api/marketing/tools");
    expect(
      r.status === 200 &&
        r.json.data.tools.length > 0 &&
        r.json.data.tools.every((t) =>
          ["Content", "Strategy & Research", "Email & Lifecycle"].includes(
            t.category,
          ),
        ),
      "wrong tools",
    );
    expect(
      (await call(a, "/api/marketing/tools?skillId=google-ads")).status === 403,
      "methodology bypass",
    );
    expect(
      (
        await call(a, "/api/marketing/tools", {
          skillId: "google-ads",
          brief: "Prepare a draft using synthetic metrics only.",
        })
      ).status === 403,
      "run bypass",
    );
  },
);
await check(
  "Plan changes filter previously saved paid-media history",
  async () => {
    const r = await call(a, "/api/marketing/tools");
    expect(
      !r.json.data.history.some((t) => t.skillId === "google-ads"),
      "old restricted tool exposed",
    );
  },
);
await check("Plan controls Google connection availability", async () => {
  const r = await call(a, "/api/marketing/connections");
  expect(
    r.status === 200 && r.json.data.providers.length === 0,
    "restricted connections exposed",
  );
  expect(
    (
      await call(a, "/api/marketing/connections/connect", {
        provider: "google_ads",
      })
    ).status === 403,
    "connection bypass",
  );
});
for (const state of ["paused", "cancelled", "expired"])
  await check(state + " plan denies all tool access", async () => {
    await sql`update client_marketing_plans set status=${state === "expired" ? "active" : state},ends_at=${state === "expired" ? new Date(Date.now() - 1000) : null},starts_at=now()-interval '1 day' where client_id=${fixtures.clients.alpha}`;
    expect(
      (await call(a, "/api/marketing/tools")).status === 403,
      "inactive plan bypass",
    );
    expect(
      (
        await call(a, "/api/marketing/tools", {
          skillId: "copywriting-frameworks",
          brief: "Prepare a draft for a fictional studio.",
        })
      ).status === 403,
      "inactive execution bypass",
    );
  });
await sql`delete from client_marketing_plans where client_id=${fixtures.clients.alpha}`;
await check("No assigned plan fails closed", async () =>
  expect(
    (await call(a, "/api/marketing/tools")).status === 403,
    "missing plan bypass",
  ),
);
await sql`insert into client_marketing_plans(client_id,plan_id,status) values(${fixtures.clients.alpha},${complete.id},'active')`;
await check(
  "Complete plan restores all 48 tools and both Google providers",
  async () => {
    expect(
      (await call(a, "/api/marketing/tools")).json.data.tools.length === 48,
      "complete tools missing",
    );
    expect(
      (await call(a, "/api/marketing/connections")).json.data.providers
        .length === 2,
      "connections missing",
    );
  },
);
await check(
  "Client cannot inject resource selection without its own OAuth grant",
  async () =>
    expect(
      (
        await call(a, "/api/marketing/connections", {
          provider: "google_analytics",
          resourceId: "properties/123",
        })
      ).status >= 400,
      "unconnected selection accepted",
    ),
);
await check("Other client cannot forge target tenant catalog", async () =>
  expect(
    (await call(b, "/api/marketing/tools?clientId=" + fixtures.clients.alpha))
      .status === 403,
    "tenant bypass",
  ),
);
await sql.end();
console.log(
  JSON.stringify({
    passed: results.filter((x) => x.passed).length,
    total: results.length,
  }),
);
if (results.some((x) => !x.passed)) process.exitCode = 1;
