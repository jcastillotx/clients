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
    new URL("artifacts/marketing-billing-" + process.argv[2] + ".json", root),
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
  const r = await fetch("http://127.0.0.1:3101" + path, {
    method: body ? "POST" : "GET",
    headers: { cookie: cookie?.() || "", "content-type": "application/json" },
    body: body ? JSON.stringify(body) : undefined,
  });
  return { status: r.status, json: await r.json() };
}

const config = await call(a, "/api/marketing/plans");
if (config.json.data?.billingMode !== "test")
  throw Error("Real Stripe sandbox mode required");

const phase = process.argv[2];
if (!["active", "paused", "cancelled"].includes(phase))
  throw Error("Expected lifecycle phase required");
await check("Real subscription lifecycle: " + phase, async () => {
  const [r] =
    await sql`select status from client_marketing_plans where client_id=${fixtures.clients.alpha}`;
  expect(
    r.status === phase,
    "local subscription does not match Stripe lifecycle",
  );
  const tools = await call(a, "/api/marketing/tools");
  expect(
    phase === "active"
      ? tools.status === 200 && tools.json.data.tools.length === 17
      : tools.status === 403,
    "incorrect paid tool access",
  );
  const plans = await call(a, "/api/marketing/plans");
  expect(Boolean(plans.json.data.billing), "billing management missing");
});
await sql.end();
console.log(
  JSON.stringify({
    passed: results.filter((x) => x.passed).length,
    total: results.length,
  }),
);
if (results.some((x) => !x.passed)) process.exitCode = 1;
