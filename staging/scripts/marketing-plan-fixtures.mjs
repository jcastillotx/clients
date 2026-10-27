import fs from "node:fs";
import { createRequire } from "node:module";
const require = createRequire(new URL("../../package.json", import.meta.url)),
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
const u = new URL(env.DATABASE_URL);
if (!["localhost", "127.0.0.1"].includes(u.hostname) || u.port !== "55322")
  throw Error("Local synthetic database only");
const sql = postgres(env.DATABASE_URL, { max: 1 }),
  fixtures = JSON.parse(
    fs.readFileSync(new URL(".accounts.json", root), "utf8"),
  );
for (const name of ["041_marketing_plans.sql", "042_marketing_connections.sql"])
  await sql.unsafe(
    fs.readFileSync(
      new URL("../../lib/db/migrations/" + name, import.meta.url),
      "utf8",
    ),
  );
const [plan] = await sql`select id from marketing_plans where slug='complete'`;
for (const id of Object.values(fixtures.clients))
  await sql`insert into client_marketing_plans(client_id,plan_id,status) values(${id},${plan.id},'active') on conflict(client_id) do update set plan_id=excluded.plan_id,status='active',ends_at=null,stripe_subscription_id=null,stripe_customer_id=null,cancel_at_period_end=false`;
await sql`update marketing_plans set published=false`;
await sql.end();
console.log("Synthetic client plans assigned; production untouched");
