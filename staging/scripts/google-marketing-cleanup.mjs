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
    new URL("artifacts/google-marketing-cleanup.json", root),
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

await check(
  "Client disconnect removes both encrypted test connections",
  async () => {
    const [r] =
      await sql`select count(*)::int as n from marketing_connections where client_id=${fixtures.clients.alpha} and user_id=${fixtures.accounts.alpha.id}`;
    expect(r.n === 0, "stored connections remain");
    const r2 = await call(a, "/api/marketing/connections");
    expect(
      r2.status === 200 && r2.json.data.providers.every((p) => !p.connection),
      "disconnected state not returned",
    );
  },
);
await sql`delete from marketing_oauth_states where client_id=${fixtures.clients.alpha} and user_id=${fixtures.accounts.alpha.id}`;
await sql.end();
console.log(
  JSON.stringify({
    passed: results.filter((x) => x.passed).length,
    total: results.length,
  }),
);
if (results.some((x) => !x.passed)) process.exitCode = 1;
