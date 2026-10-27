import fs from "node:fs";
import { createRequire } from "node:module";
import crypto from "node:crypto";
const require = createRequire(new URL("../../package.json", import.meta.url));
const { createServerClient } = require("@supabase/ssr");
const { createClient } = require("@supabase/supabase-js");
const postgres = require("postgres");
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
for (const [key, port] of [
  ["DATABASE_URL", "55322"],
  ["NEXT_PUBLIC_SUPABASE_URL", "55321"],
  ["NEXT_PUBLIC_APP_URL", "3100"],
]) {
  const u = new URL(env[key]);
  if (!["127.0.0.1", "localhost"].includes(u.hostname) || u.port !== port)
    throw Error("Dedicated isolated staging required: " + key);
}
const fixtures = JSON.parse(
  fs.readFileSync(new URL(".accounts.json", root), "utf8"),
);
const sql = postgres(env.DATABASE_URL, { max: 1 });
const service = createClient(
  env.NEXT_PUBLIC_SUPABASE_URL,
  env.SUPABASE_SERVICE_ROLE_KEY,
  { auth: { persistSession: false } },
);
const results = [];
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
    new URL("artifacts/extended-client-workflows.json", root),
    JSON.stringify(results, null, 2),
  );
}
function expect(condition, message) {
  if (!condition) throw Error(message);
}
async function session(name) {
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
  const account = fixtures.accounts[name];
  const { error } = await auth.auth.signInWithPassword({
    email: account.email,
    password: account.password,
  });
  if (error) throw error;
  return {
    auth,
    account,
    cookie: () => jar.map((c) => c.name + "=" + c.value).join("; "),
  };
}
const clients = {};
for (const name of ["admin", "alpha", "beta", "staff"])
  clients[name] = await session(name);
async function call(who, path, body, method = "POST") {
  const headers = { cookie: clients[who].cookie() };
  if (body && !(body instanceof FormData))
    headers["content-type"] = "application/json";
  const r = await fetch(env.NEXT_PUBLIC_APP_URL + path, {
    method,
    headers,
    body:
      body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
    redirect: "manual",
  });
  let data;
  try {
    data = await r.json();
  } catch {}
  return { r, data, value: data?.data ?? data };
}
function status(x, code) {
  expect(
    x.r.status === code,
    `expected ${code}, got ${x.r.status}: ${JSON.stringify(x.data?.error || x.data?.message || {}).slice(0, 220)}`,
  );
}
const a = fixtures.clients.alpha,
  b = fixtures.clients.beta;

await test("Project feedback saves a rating and rejects invalid input", async () => {
  const [p] =
    await sql`select id from requests where client_id=${a} and custom_fields @> '{"type":"project"}'::jsonb order by created_at desc limit 1`;
  expect(!!p, "missing synthetic project request");
  const feedback = await call(
    "alpha",
    "/api/projects/requests/" + p.id + "/feedback",
    { rating: 4, message: "Extended QA project feedback" },
  );
  status(feedback, 201);
  const list = await call(
    "alpha",
    "/api/projects/requests/" + p.id + "/feedback",
    null,
    "GET",
  );
  status(list, 200);
  expect(
    list.value.some(
      (x) => x.rating === 4 && x.message === "Extended QA project feedback",
    ),
    "feedback not reloaded",
  );
  status(
    await call("alpha", "/api/projects/requests/" + p.id + "/feedback", {
      rating: 6,
      message: "invalid",
    }),
    400,
  );
  const foreign = await call(
    "beta",
    "/api/projects/requests/" + p.id + "/feedback",
    { rating: 1, message: "forbidden" },
  );
  expect([403, 404].includes(foreign.r.status), "foreign feedback accepted");
});
await test("Proposal cancellation authorizes tenant before revealing accepted status", async () => {
  const [p] =
    await sql`insert into proposals(client_id,title,status,total_amount,created_by,line_items) values(${b},'Extended QA foreign accepted proposal','accepted',25,${clients.admin.account.id},'[]') returning id`;
  try {
    status(await call("alpha", "/api/proposals/" + p.id + "/cancel", {}), 403);
  } finally {
    await sql`delete from proposals where id=${p.id}`;
  }
});
await test("A client can cancel its sent proposal but cannot cancel an accepted proposal", async () => {
  const [p] =
    await sql`insert into proposals(client_id,title,status,total_amount,created_by,line_items) values(${a},'Extended QA cancellation','sent',25,${clients.admin.account.id},'[]') returning id`;
  try {
    status(await call("alpha", "/api/proposals/" + p.id + "/cancel", {}), 200);
    const [row] =
      await sql`select status,cancelled_at from proposals where id=${p.id}`;
    expect(
      row.status === "cancelled" && !!row.cancelled_at,
      "cancellation not saved",
    );
    await sql`update proposals set status='accepted' where id=${p.id}`;
    status(await call("alpha", "/api/proposals/" + p.id + "/cancel", {}), 400);
  } finally {
    await sql`delete from proposals where id=${p.id}`;
  }
});
await test("Concurrent message read receipts remain idempotent", async () => {
  const conv = await call("alpha", "/api/messages/conversations", {
    clientId: a,
    title: "Extended QA read receipts",
    participantIds: [clients.alpha.account.id],
  });
  status(conv, 201);
  const [message] =
    await sql`insert into messages(conversation_id,sender_id,body,type) values(${conv.value.id},${clients.admin.account.id},'Extended QA incoming message','text') returning id`;
  const results = await Promise.all(
    Array.from({ length: 8 }, () =>
      call("alpha", "/api/messages/" + message.id + "/read", {}),
    ),
  );
  expect(
    results.every((x) => x.r.status === 200),
    "concurrent read statuses: " + results.map((x) => x.r.status).join(","),
  );
  const [count] =
    await sql`select count(*)::int as n from message_reads where message_id=${message.id} and user_id=${clients.alpha.account.id}`;
  expect(count.n === 1, "duplicate read receipts");
  status(await call("beta", "/api/messages/" + message.id + "/read", {}), 403);
  status(
    await call(
      "alpha",
      "/api/messages/" + message.id + "/read",
      null,
      "DELETE",
    ),
    200,
  );
});
await test("Notification preferences persist independently for each client", async () => {
  const before = await call(
    "alpha",
    "/api/notifications/preferences",
    null,
    "GET",
  );
  status(before, 200);
  const betaBefore = await call(
    "beta",
    "/api/notifications/preferences",
    null,
    "GET",
  );
  status(betaBefore, 200);
  try {
    status(
      await call(
        "alpha",
        "/api/notifications/preferences",
        {
          emailEnabled: false,
          inAppEnabled: false,
          userId: clients.beta.account.id,
        },
        "PATCH",
      ),
      200,
    );
    const after = await call(
      "alpha",
      "/api/notifications/preferences",
      null,
      "GET",
    );
    expect(
      after.value.emailEnabled === false && after.value.inAppEnabled === false,
      "preferences not saved",
    );
    const betaAfter = await call(
      "beta",
      "/api/notifications/preferences",
      null,
      "GET",
    );
    expect(
      JSON.stringify(betaAfter.value) === JSON.stringify(betaBefore.value),
      "other client preferences changed",
    );
  } finally {
    await call(
      "alpha",
      "/api/notifications/preferences",
      before.value,
      "PATCH",
    );
  }
});
await test("Concurrent mark-all notifications read is idempotent", async () => {
  const r = await call("alpha", "/api/notifications", null, "GET");
  status(r, 200);
  expect(r.value.notifications.length > 0, "missing synthetic notifications");
  await sql`delete from message_reads where user_id=${clients.alpha.account.id} and message_id in (select m.id from messages m join conversations c on c.id=m.conversation_id where c.client_id=${a} and c.title='System Notifications')`;
  const replies = await Promise.all(
    Array.from({ length: 6 }, () => call("alpha", "/api/notifications", {})),
  );
  expect(
    replies.every((x) => x.r.status === 200),
    "concurrent notification statuses: " +
      replies.map((x) => x.r.status).join(","),
  );
  const after = await call("alpha", "/api/notifications", null, "GET");
  expect(after.value.unreadCount === 0, "unread notifications remain");
});
await test("Concurrent proposal acceptance and cancellation preserve one legal state", async () => {
  for (let n = 0; n < 4; n++) {
    const [p] =
      await sql`insert into proposals(client_id,title,status,total_amount,created_by,line_items) values(${a},'Extended QA proposal transition race','sent',25,${clients.admin.account.id},'[]') returning id`;
    try {
      const replies = await Promise.all([
        call("alpha", "/api/proposals/" + p.id + "/sign", {
          action: "accept",
          signatureData: "data:image/png;base64,iVBORw0KGgo=",
          signerName: "QA Client",
          signerEmail: clients.alpha.account.email,
        }),
        call("alpha", "/api/proposals/" + p.id + "/cancel", {}),
      ]);
      expect(
        replies.every((x) => [200, 400, 409].includes(x.r.status)),
        "transition race statuses: " + replies.map((x) => x.r.status).join(","),
      );
      expect(
        replies.filter((x) => x.r.status === 200).length === 1,
        "conflicting transitions both succeeded",
      );
      const [saved] =
        await sql`select status,accepted_at from proposals where id=${p.id}`;
      expect(
        (saved.status === "accepted" && saved.accepted_at) ||
          (saved.status === "cancelled" && !saved.accepted_at),
        "accepted proposal was cancelled or signature was lost",
      );
    } finally {
      await sql`delete from proposals where id=${p.id}`;
    }
  }
});
await test("Design-review website comments persist and stay tenant-scoped", async () => {
  const [project] =
    await sql`select id from projects where client_id=${a} and deleted_at is null limit 1`;
  expect(!!project, "missing synthetic project");
  const made = await call("alpha", "/api/projects/" + project.id + "/reviews", {
    type: "website",
    title: "Extended QA website review",
    websiteUrl: env.NEXT_PUBLIC_APP_URL + "/calendar-privacy",
  });
  status(made, 201);
  const review = made.value.id;
  const comment = await call(
    "alpha",
    "/api/projects/" + project.id + "/reviews/" + review + "/comments",
    { body: "Extended QA pinned design comment", xPercent: 25, yPercent: 40 },
  );
  status(comment, 201);
  const list = await call(
    "alpha",
    "/api/projects/" + project.id + "/reviews",
    null,
    "GET",
  );
  status(list, 200);
  expect(
    list.value.some(
      (x) =>
        x.id === review &&
        x.comments.some((c) => c.body === "Extended QA pinned design comment"),
    ),
    "design comment did not reload",
  );
  status(
    await call(
      "alpha",
      "/api/projects/" + project.id + "/reviews/" + review + "/comments",
      { body: "incomplete pin", xPercent: 25 },
    ),
    400,
  );
  const foreign = await call(
    "beta",
    "/api/projects/" + project.id + "/reviews/" + review + "/comments",
    { body: "forbidden" },
  );
  expect(
    [403, 404].includes(foreign.r.status),
    "foreign design comment accepted",
  );
});
await test("Design-review image upload and authorized download preserve bytes", async () => {
  const [project] =
    await sql`select id from projects where client_id=${a} and deleted_at is null limit 1`;
  const image = Buffer.from(
    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jf1cAAAAASUVORK5CYII=",
    "base64",
  );
  const form = new FormData();
  form.set("title", "Extended QA image review");
  form.set(
    "file",
    new Blob([image], { type: "image/png" }),
    "extended-qa-review.png",
  );
  const made = await call(
    "alpha",
    "/api/projects/" + project.id + "/reviews/upload",
    form,
  );
  status(made, 201);
  const path =
    "/api/projects/" + project.id + "/reviews/" + made.value.id + "/asset";
  const response = await call("alpha", path, null, "GET");
  expect([302, 307].includes(response.r.status), "image asset redirect failed");
  const location = response.r.headers.get("location");
  expect(!!location, "missing image redirect");
  const u = new URL(location);
  expect(
    ["127.0.0.1", "localhost"].includes(u.hostname),
    "non-local test object",
  );
  const bytes = Buffer.from(await fetch(location).then((r) => r.arrayBuffer()));
  expect(bytes.equals(image), "review image bytes differ");
  const foreign = await call("beta", path, null, "GET");
  expect(
    [403, 404].includes(foreign.r.status),
    "foreign review image available",
  );
});
await test("Nested project pages do not expose another tenant's project", async () => {
  const [foreign] =
    await sql`select id,name from projects where client_id=${b} and deleted_at is null limit 1`;
  expect(!!foreign, "missing foreign fixture");
  const marker = "QA-private-project-" + crypto.randomUUID();
  await sql`update projects set name=${marker} where id=${foreign.id}`;
  try {
    for (const suffix of [
      "budget",
      "timeline",
      "feedback",
      "messages",
      "tasks",
    ]) {
      const response = await fetch(
        env.NEXT_PUBLIC_APP_URL + "/projects/" + foreign.id + "/" + suffix,
        { headers: { cookie: clients.alpha.cookie() }, redirect: "manual" },
      );
      const html = await response.text();
      expect(response.status < 500, "page error on " + suffix);
      expect(!html.includes(marker), "foreign project leaked on " + suffix);
      expect(
        !response.headers.get("location"),
        "foreign metadata redirect on " + suffix,
      );
    }
  } finally {
    await sql`update projects set name=${foreign.name} where id=${foreign.id}`;
  }
});
await test("Staff task pages block clients while preserving staff access", async () => {
  const marker = "QA-private-board-" + crypto.randomUUID();
  const [board] =
    await sql`insert into staff_task_boards(name,created_by) values(${marker},${clients.admin.account.id}) returning id`;
  try {
    for (const path of [
      "/tasks",
      "/tasks/new",
      "/tasks/" + board.id,
      "/tasks/" + board.id + "/list",
      "/time-tracking",
      "/projects/budgets",
    ]) {
      const response = await fetch(env.NEXT_PUBLIC_APP_URL + path, {
        headers: { cookie: clients.alpha.cookie() },
        redirect: "manual",
      });
      const html = await response.text();
      expect(
        response.status === 404,
        "client staff-page status " + path + ": " + response.status,
      );
      expect(!html.includes(marker), "staff board leaked");
    }
    const response = await fetch(env.NEXT_PUBLIC_APP_URL + "/tasks", {
      headers: { cookie: clients.staff.cookie() },
    });
    expect(
      response.status === 200 && (await response.text()).includes(marker),
      "legitimate staff board access failed",
    );
  } finally {
    await sql`delete from staff_task_boards where id=${board.id}`;
  }
});
await test("Client project actions show working destinations and omit staff-only tools", async () => {
  const [project] =
    await sql`select id,name from projects where client_id=${a} and deleted_at is null limit 1`;
  const response = await fetch(
    env.NEXT_PUBLIC_APP_URL + "/projects/" + project.id,
    { headers: { cookie: clients.alpha.cookie() } },
  );
  const html = await response.text();
  expect(
    response.status === 200 && html.includes(project.name),
    "own project missing",
  );
  expect(
    !html.includes('href="/time-tracking?projectId='),
    "client sees staff time tracker",
  );
  expect(
    !html.includes('href="/projects/' + project.id + '/tasks"'),
    "client sees staff task board",
  );
  for (const suffix of ["budget", "timeline", "feedback"]) {
    const r = await fetch(
      env.NEXT_PUBLIC_APP_URL + "/projects/" + project.id + "/" + suffix,
      { headers: { cookie: clients.alpha.cookie() } },
    );
    expect(
      r.status === 200 && (await r.text()).includes(project.name),
      "own project page unavailable " + suffix,
    );
  }
  const inbox = await fetch(
    env.NEXT_PUBLIC_APP_URL + "/projects/" + project.id + "/messages",
    { headers: { cookie: clients.alpha.cookie() }, redirect: "manual" },
  );
  expect(
    [302, 307].includes(inbox.status) &&
      inbox.headers.get("location") === "/messages",
    "project messages loops back to overview",
  );
});
await sql.end();
console.log(
  JSON.stringify({
    passed: results.filter((x) => x.passed).length,
    failed: results.filter((x) => !x.passed).length,
  }),
);
if (results.some((x) => !x.passed)) process.exitCode = 1;
