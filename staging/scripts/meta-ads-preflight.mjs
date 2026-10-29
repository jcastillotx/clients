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
    new URL("artifacts/meta-ads-preflight.json", root),
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
  const r = await fetch("http://localhost:3101" + path, {
    method: body ? "POST" : "GET",
    headers: { cookie: cookie?.() || "", "content-type": "application/json" },
    body: body ? JSON.stringify(body) : undefined,
  });
  return { status: r.status, json: await r.json() };
}

const crypto = require("node:crypto");
function decrypt(cipher) {
  const p = Buffer.from(cipher, "base64"),
    key = crypto.scryptSync(env.ENCRYPTION_KEY, p.subarray(0, 32), 32),
    d = crypto.createDecipheriv("aes-256-gcm", key, p.subarray(32, 48));
  d.setAuthTag(p.subarray(48, 64));
  return Buffer.concat([d.update(p.subarray(64)), d.final()]).toString();
}
const [connection] =
  await sql`select * from marketing_connections where client_id=${fixtures.clients.alpha} and user_id=${fixtures.accounts.alpha.id} and provider='meta_ads'`;
if (!connection) throw Error("Connect the approved Meta test account first");
await check(
  "Real Meta OAuth tokens are encrypted and absent from API responses",
  async () => {
    expect(
      decrypt(connection.access_token) !== connection.access_token &&
        connection.refresh_token === null,
      "plaintext token",
    );
    const r = await call(a, "/api/marketing/connections");
    expect(
      r.status === 200 &&
        !JSON.stringify(r.json).includes("access_token") &&
        !JSON.stringify(r.json).includes("refresh_token"),
      "token leak",
    );
  },
);
await check(
  "Another client cannot see the owner Meta connection",
  async () => {
    const r = await call(b, "/api/marketing/connections");
    expect(
      r.status === 200 && r.json.data.providers.every((p) => !p.connection),
      "foreign connection visible",
    );
  },
);
await check("Actual Meta ad-account discovery succeeds", async () => {
  const r = await call(a, "/api/marketing/connections", { provider: "meta_ads" });
  expect(r.status === 200 && Array.isArray(r.json.data.resources) && r.json.data.resources.length > 0, "Meta discovery failed");
});
await check(
  "Forged property selection is rejected against Meta resource membership",
  async () =>
    expect(
      (
        await call(a, "/api/marketing/connections", {
          provider: "meta_ads",
          resourceId: "act_1",
        })
      ).status === 403,
      "forged property selected",
    ),
);
await check(
  "Expired Meta user token requires reconnect instead of borrowing other credentials",
  async () => {
    const original = connection.expires_at;
    await sql`update marketing_connections set expires_at=now()-interval '1 hour' where id=${connection.id}`;
    try {
      expect(
        (
          await call(a, "/api/marketing/connections/report", {
            provider: "meta_ads",
          })
        ).status >= 400,
        "expired Meta token accepted",
      );
    } finally {
      await sql`update marketing_connections set expires_at=${original} where id=${connection.id}`;
    }
  },
);
await check("Foreign client cannot use the owner Meta Ads token", async () =>
  expect(
    (
      await call(b, "/api/marketing/connections/report", {
        provider: "meta_ads",
        clientId: fixtures.clients.alpha,
      })
    ).status >= 400,
    "foreign report access",
  ),
);
await check(
  "Expired plan blocks reporting but preserves disconnect access",
  async () => {
    await sql`update client_marketing_plans set status='paused' where client_id=${fixtures.clients.alpha}`;
    try {
      expect(
        (
          await call(a, "/api/marketing/connections/report", {
            provider: "meta_ads",
          })
        ).status === 403,
        "paused plan reporting",
      );
      const r = await call(a, "/api/marketing/connections");
      expect(
        r.status === 200 &&
          r.json.data.providers.some(
            (p) => p.provider === "meta_ads" && !p.permitted && p.connection,
          ),
        "disconnect unavailable",
      );
    } finally {
      await sql`update client_marketing_plans set status='active' where client_id=${fixtures.clients.alpha}`;
    }
  },
);
await check(
  "Invalid OAuth state fails safely without changing saved connections",
  async () => {
    const r = await fetch(
      "http://localhost:3101/api/marketing/connections/meta/callback?state=synthetic-invalid&error=access_denied",
      { redirect: "manual" },
    );
    expect(
      r.status === 307 &&
        r.headers.get("location").endsWith("error=connection_failed"),
      "unsafe callback",
    );
    const [count] =
      await sql`select count(*)::int as n from marketing_connections where id=${connection.id}`;
    expect(count.n === 1, "existing connection changed");
  },
);
await sql.end();
console.log(
  JSON.stringify({
    passed: results.filter((x) => x.passed).length,
    total: results.length,
  }),
);
if (results.some((x) => !x.passed)) process.exitCode = 1;
