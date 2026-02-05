/**
 * Data Migration Script: MySQL → PostgreSQL
 *
 * Migrates all data from Laravel MySQL database to Supabase PostgreSQL
 * in dependency order, handling foreign keys and relationships.
 *
 * Usage:
 *   pnpm install mysql2 @supabase/supabase-js
 *   tsx migrate-data.ts
 */

import mysql from "mysql2/promise";
import { createClient } from "@supabase/supabase-js";
import { config } from "dotenv";

config();

// ============================================================================
// Configuration
// ============================================================================

const MYSQL_CONFIG = {
  host: process.env.MYSQL_HOST || "localhost",
  user: process.env.MYSQL_USER || "root",
  password: process.env.MYSQL_PASSWORD || "",
  database: process.env.MYSQL_DATABASE || "kre8iv_clients",
};

const SUPABASE_URL = process.env.SUPABASE_URL!;
const SUPABASE_SERVICE_KEY = process.env.SUPABASE_SERVICE_KEY!;

const BATCH_SIZE = 1000; // Insert 1000 rows at a time

// ============================================================================
// Table Migration Order (Respecting Foreign Keys)
// ============================================================================

const MIGRATION_ORDER = [
  // No dependencies
  "migrations",
  "failed_jobs",
  "personal_access_tokens",
  "permissions",
  "roles",
  "tags",

  // User-related (no FK dependencies)
  "users",

  // Client-related
  "clients",

  // User relationships
  "user_roles",
  "role_permissions",
  "staff_assignments",

  // Client relationships
  "client_contacts",
  "client_notes",

  // Requests (depends on clients, users)
  "requests",
  "request_comments",

  // Invoices (depends on clients)
  "invoices",
  "invoice_items",
  "payments",

  // Contracts (depends on clients)
  "contracts",
  "contract_signatures",

  // Documents (depends on clients, requests)
  "documents",
  "document_versions",

  // Projects (depends on clients)
  "projects",
  "project_members",

  // Tasks (depends on projects)
  "tasks",
  "task_assignments",
  "task_comments",

  // Messaging
  "conversations",
  "conversation_participants",
  "messages",

  // SEO features
  "seo_keywords",
  "seo_rankings",
  "seo_backlinks",
  "seo_recommendations",
  "website_audits",
  "website_audit_pages",

  // AI features
  "ai_providers",
  "ai_conversations",
  "ai_messages",

  // Automation
  "automation_rules",
  "automation_logs",
  "webhooks",
  "webhook_logs",

  // Knowledge base
  "knowledge_base_categories",
  "knowledge_base_articles",
  "knowledge_base_feedback",

  // Storage
  "storage_connections",
  "storage_syncs",

  // Activity logs (polymorphic, migrate last)
  "activity_logs",

  // Taggables (polymorphic)
  "taggables",
];

// ============================================================================
// ID Mapping (MySQL ID → PostgreSQL UUID)
// ============================================================================

interface IdMapping {
  [mysqlId: string]: string; // MySQL ID → PostgreSQL UUID
}

const idMappings: Record<string, IdMapping> = {};

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Transform MySQL row to PostgreSQL format
 */
function transformRow(table: string, row: any): any {
  const transformed: any = { ...row };

  // Convert MySQL TINYINT(1) to boolean
  for (const [key, value] of Object.entries(row)) {
    if (typeof value === "number" && (value === 0 || value === 1)) {
      // Check if it's a boolean field
      const boolFields = [
        "is_active",
        "is_super_admin",
        "email_verified",
        "is_read",
        "is_deleted",
        "is_default",
        "enabled",
        "auto_renew",
        "is_recurring",
        "reminded_due_7",
        "reminded_due_3",
        "reminded_due_1",
        "reminded_overdue",
        "reminded_overdue_7",
      ];
      if (boolFields.includes(key)) {
        transformed[key] = value === 1;
      }
    }
  }

  // Convert MySQL DATETIME to PostgreSQL TIMESTAMPTZ
  const dateFields = [
    "created_at",
    "updated_at",
    "deleted_at",
    "email_verified_at",
    "last_login_at",
    "due_date",
    "start_date",
    "end_date",
    "sent_at",
    "paid_at",
    "reminded_due_7_at",
    "reminded_due_3_at",
    "reminded_due_1_at",
    "reminded_overdue_at",
    "reminded_overdue_7_at",
  ];
  for (const field of dateFields) {
    if (transformed[field]) {
      transformed[field] = new Date(transformed[field]).toISOString();
    }
  }

  // Convert JSON strings to objects (if needed)
  const jsonFields = [
    "enabled_features",
    "google_search_console_data",
    "marketing_strategy",
    "manual_permissions",
    "security_settings",
    "custom_fields",
    "items",
    "tax_details",
    "config",
    "conditions",
    "actions",
  ];
  for (const field of jsonFields) {
    if (transformed[field] && typeof transformed[field] === "string") {
      try {
        transformed[field] = JSON.parse(transformed[field]);
      } catch {
        // Already an object or null
      }
    }
  }

  // Map foreign key IDs from MySQL to PostgreSQL UUIDs
  const fkMappings: Record<string, { table: string; field: string }> = {
    users: { table: "users", field: "id" },
    requests: {
      client_id: { table: "clients", field: "id" },
      created_by: { table: "users", field: "id" },
      assigned_to: { table: "users", field: "id" },
    },
    invoices: {
      client_id: { table: "clients", field: "id" },
      created_by: { table: "users", field: "id" },
    },
    // Add more FK mappings as needed
  };

  if (fkMappings[table]) {
    const mappings = fkMappings[table];
    for (const [field, mapping] of Object.entries(mappings)) {
      if (transformed[field] && idMappings[mapping.table]) {
        const mysqlId = String(transformed[field]);
        const pgUuid = idMappings[mapping.table][mysqlId];
        if (pgUuid) {
          transformed[field] = pgUuid;
        }
      }
    }
  }

  return transformed;
}

/**
 * Migrate a single table
 */
async function migrateTable(
  mysqlConn: mysql.Connection,
  supabase: ReturnType<typeof createClient>,
  tableName: string,
): Promise<void> {
  console.log(`\n📦 Migrating table: ${tableName}`);

  try {
    // 1. Count rows in MySQL
    const [countResult] = await mysqlConn.query<any[]>(`SELECT COUNT(*) as count FROM \`${tableName}\``);
    const totalRows = countResult[0].count;
    console.log(`   Total rows: ${totalRows}`);

    if (totalRows === 0) {
      console.log(`   ⏭️  Skipping empty table`);
      return;
    }

    // 2. Fetch all rows from MySQL
    const [rows] = await mysqlConn.query<any[]>(`SELECT * FROM \`${tableName}\``);

    // 3. Transform rows
    const transformed = rows.map((row) => transformRow(tableName, row));

    // 4. Batch insert into PostgreSQL
    let inserted = 0;
    const batches = Math.ceil(transformed.length / BATCH_SIZE);

    for (let i = 0; i < batches; i++) {
      const batch = transformed.slice(i * BATCH_SIZE, (i + 1) * BATCH_SIZE);

      const { data, error } = await supabase.from(tableName).insert(batch).select("id");

      if (error) {
        console.error(`   ❌ Error inserting batch ${i + 1}/${batches}:`, error);
        throw error;
      }

      // Store ID mappings (MySQL ID → PostgreSQL UUID)
      if (data && rows[i * BATCH_SIZE].id) {
        if (!idMappings[tableName]) {
          idMappings[tableName] = {};
        }

        for (let j = 0; j < batch.length; j++) {
          const mysqlRow = rows[i * BATCH_SIZE + j];
          const pgRow = data[j];
          if (mysqlRow.id && pgRow.id) {
            idMappings[tableName][String(mysqlRow.id)] = pgRow.id;
          }
        }
      }

      inserted += batch.length;
      console.log(`   ✅ Inserted batch ${i + 1}/${batches} (${inserted}/${totalRows})`);
    }

    // 5. Verify row count
    const { count, error: countError } = await supabase.from(tableName).select("*", { count: "exact", head: true });

    if (countError) {
      console.error(`   ⚠️  Could not verify row count:`, countError);
    } else if (count !== totalRows) {
      console.warn(`   ⚠️  Row count mismatch! MySQL: ${totalRows}, PostgreSQL: ${count}`);
    } else {
      console.log(`   ✅ Migration complete! ${count} rows verified`);
    }
  } catch (error) {
    console.error(`   ❌ Failed to migrate ${tableName}:`, error);
    throw error;
  }
}

// ============================================================================
// Main Migration Function
// ============================================================================

async function main() {
  console.log("🚀 Starting data migration: MySQL → PostgreSQL\n");
  console.log("Configuration:");
  console.log(`  MySQL: ${MYSQL_CONFIG.host}/${MYSQL_CONFIG.database}`);
  console.log(`  Supabase: ${SUPABASE_URL}\n`);

  // Connect to MySQL
  console.log("📡 Connecting to MySQL...");
  const mysqlConn = await mysql.createConnection(MYSQL_CONFIG);
  console.log("   ✅ MySQL connected\n");

  // Connect to Supabase
  console.log("📡 Connecting to Supabase...");
  const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
  console.log("   ✅ Supabase connected\n");

  // Migrate tables in order
  const startTime = Date.now();
  let migratedTables = 0;
  let totalRows = 0;

  for (const tableName of MIGRATION_ORDER) {
    try {
      await migrateTable(mysqlConn, supabase, tableName);
      migratedTables++;

      // Get row count for summary
      const { count } = await supabase.from(tableName).select("*", { count: "exact", head: true });
      totalRows += count || 0;
    } catch (error) {
      console.error(`\n❌ Migration failed at table: ${tableName}`);
      console.error(error);
      process.exit(1);
    }
  }

  // Close MySQL connection
  await mysqlConn.end();

  // Summary
  const duration = ((Date.now() - startTime) / 1000).toFixed(2);
  console.log("\n" + "=".repeat(60));
  console.log("🎉 Migration complete!\n");
  console.log(`  Tables migrated: ${migratedTables}`);
  console.log(`  Total rows: ${totalRows.toLocaleString()}`);
  console.log(`  Duration: ${duration}s`);
  console.log("=".repeat(60));

  // Save ID mappings for reference
  const fs = await import("fs");
  fs.writeFileSync("./id-mappings.json", JSON.stringify(idMappings, null, 2));
  console.log("\n💾 ID mappings saved to id-mappings.json");
}

// Run migration
main().catch(console.error);
