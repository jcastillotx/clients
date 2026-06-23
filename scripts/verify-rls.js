#!/usr/bin/env node
/**
 * Verify Row-Level Security is enabled on tenant-scoped tables.
 *
 * Usage:
 *   DATABASE_URL=postgresql://... node scripts/verify-rls.js
 *
 * Exit codes:
 *   0 — all checked tables have RLS enabled
 *   1 — one or more tables missing RLS or not found
 *   2 — configuration / connection error
 */

const postgres = require("postgres");

const TENANT_TABLES = [
  "clients",
  "users",
  "requests",
  "invoices",
  "contracts",
  "documents",
  "projects",
  "tasks",
  "request_comments",
  "invoice_items",
  "project_members",
  "task_assignments",
  "conversations",
  "messages",
  "activity_logs",
  "client_contacts",
  "client_notes",
  "support_tickets",
  "proposals",
  "time_entries",
];

async function main() {
  const databaseUrl = process.env.DATABASE_URL || process.env.POSTGRES_URL;

  if (!databaseUrl) {
    console.error("[verify-rls] DATABASE_URL or POSTGRES_URL is required");
    process.exit(2);
  }

  const sql = postgres(databaseUrl, { max: 1 });

  try {
    const rows = await sql`
      SELECT
        c.relname AS table_name,
        c.relrowsecurity AS rls_enabled,
        c.relforcerowsecurity AS rls_forced
      FROM pg_class c
      JOIN pg_namespace n ON n.oid = c.relnamespace
      WHERE n.nspname = 'public'
        AND c.relkind = 'r'
        AND c.relname = ANY(${TENANT_TABLES})
      ORDER BY c.relname
    `;

    const found = new Map(rows.map((row) => [row.table_name, row]));
    const missing = TENANT_TABLES.filter((table) => !found.has(table));
    const disabled = rows.filter((row) => !row.rls_enabled);

    console.log(`[verify-rls] Checked ${TENANT_TABLES.length} tenant tables`);
    console.log(`[verify-rls] Found in database: ${rows.length}`);

    if (missing.length > 0) {
      console.warn("[verify-rls] Tables not found (may not be migrated yet):");
      for (const table of missing) {
        console.warn(`  - ${table}`);
      }
    }

    if (disabled.length > 0) {
      console.error("[verify-rls] RLS DISABLED on:");
      for (const row of disabled) {
        console.error(
          `  - ${row.table_name} (force=${row.rls_forced ? "yes" : "no"})`,
        );
      }
      process.exit(1);
    }

    if (rows.length === 0) {
      console.error(
        "[verify-rls] No tenant tables found. Run migrations before verifying RLS.",
      );
      process.exit(1);
    }

    console.log("[verify-rls] All found tenant tables have RLS enabled.");
    process.exit(missing.length > 0 ? 1 : 0);
  } catch (error) {
    console.error(
      "[verify-rls] Failed to query database:",
      error instanceof Error ? error.message : error,
    );
    process.exit(2);
  } finally {
    await sql.end({ timeout: 5 });
  }
}

main();
