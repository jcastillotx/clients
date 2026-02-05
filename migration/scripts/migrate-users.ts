/**
 * User Migration Script: Laravel Users → Supabase Auth
 *
 * Migrates user accounts from Laravel's users table to Supabase Auth,
 * preserving bcrypt passwords and metadata.
 *
 * Usage:
 *   pnpm install mysql2 @supabase/supabase-js
 *   tsx migrate-users.ts
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

// ============================================================================
// User Mapping Interface
// ============================================================================

interface LaravelUser {
  id: number;
  name: string;
  email: string;
  email_verified_at: Date | null;
  password: string; // bcrypt hash
  phone: string | null;
  avatar: string | null;
  client_id: number | null;
  is_active: boolean;
  is_super_admin: boolean;
  status: string;
  last_login_at: Date | null;
  manual_permissions: string | null;
  security_settings: string | null;
  created_at: Date;
  updated_at: Date;
}

interface UserIdMapping {
  laravelId: number;
  supabaseId: string;
  email: string;
}

// ============================================================================
// Migration Functions
// ============================================================================

/**
 * Migrate a single user to Supabase Auth
 */
async function migrateUser(supabase: ReturnType<typeof createClient>, user: LaravelUser): Promise<string | null> {
  try {
    // Parse JSON fields
    const manualPermissions = user.manual_permissions ? JSON.parse(user.manual_permissions) : [];
    const securitySettings = user.security_settings ? JSON.parse(user.security_settings) : {};

    // Create user in Supabase Auth
    const { data, error } = await supabase.auth.admin.createUser({
      email: user.email,
      password: user.password, // Supabase accepts bcrypt hashes directly!
      email_confirm: !!user.email_verified_at,
      user_metadata: {
        name: user.name,
        phone: user.phone,
        avatar: user.avatar,
        client_id: user.client_id,
        is_active: user.is_active,
        is_super_admin: user.is_super_admin,
        status: user.status,
        last_login_at: user.last_login_at?.toISOString(),
        manual_permissions: manualPermissions,
        security_settings: securitySettings,
        migrated_from_laravel: true,
        laravel_id: user.id,
      },
    });

    if (error) {
      console.error(`   ❌ Failed to create user ${user.email}:`, error.message);
      return null;
    }

    console.log(`   ✅ Created user: ${user.email} (${data.user.id})`);
    return data.user.id;
  } catch (error) {
    console.error(`   ❌ Error migrating user ${user.email}:`, error);
    return null;
  }
}

/**
 * Update users table with Supabase UUIDs
 */
async function updateUsersTable(mysqlConn: mysql.Connection, mappings: UserIdMapping[]): Promise<void> {
  console.log("\n📝 Updating users table with Supabase UUIDs...");

  // Add supabase_id column if it doesn't exist
  await mysqlConn.query(`
    ALTER TABLE users
    ADD COLUMN IF NOT EXISTS supabase_id VARCHAR(36) UNIQUE
  `);

  for (const mapping of mappings) {
    await mysqlConn.query("UPDATE users SET supabase_id = ? WHERE id = ?", [mapping.supabaseId, mapping.laravelId]);
  }

  console.log(`   ✅ Updated ${mappings.length} users with Supabase IDs`);
}

/**
 * Verify migration integrity
 */
async function verifyMigration(
  mysqlConn: mysql.Connection,
  supabase: ReturnType<typeof createClient>,
): Promise<boolean> {
  console.log("\n🔍 Verifying migration...");

  // Count users in MySQL
  const [mysqlCount] = await mysqlConn.query<any[]>("SELECT COUNT(*) as count FROM users");
  const mysqlTotal = mysqlCount[0].count;

  // Count users in Supabase Auth
  const { data: supabaseUsers, error } = await supabase.auth.admin.listUsers();

  if (error) {
    console.error("   ❌ Could not list Supabase users:", error);
    return false;
  }

  const supabaseTotal = supabaseUsers.users.length;

  console.log(`   MySQL users: ${mysqlTotal}`);
  console.log(`   Supabase users: ${supabaseTotal}`);

  if (mysqlTotal === supabaseTotal) {
    console.log("   ✅ User counts match!");
    return true;
  } else {
    console.warn(`   ⚠️  User count mismatch! Difference: ${mysqlTotal - supabaseTotal}`);
    return false;
  }
}

// ============================================================================
// Main Migration Function
// ============================================================================

async function main() {
  console.log("🚀 Starting user migration: Laravel → Supabase Auth\n");
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

  // Fetch all users from MySQL
  console.log("📥 Fetching users from MySQL...");
  const [users] = await mysqlConn.query<any[]>("SELECT * FROM users ORDER BY id ASC");
  console.log(`   Found ${users.length} users\n`);

  // Migrate users
  console.log("👥 Migrating users to Supabase Auth...");
  const startTime = Date.now();
  const mappings: UserIdMapping[] = [];
  let successCount = 0;
  let failCount = 0;

  for (const user of users) {
    const supabaseId = await migrateUser(supabase, user as LaravelUser);

    if (supabaseId) {
      mappings.push({
        laravelId: user.id,
        supabaseId,
        email: user.email,
      });
      successCount++;
    } else {
      failCount++;
    }
  }

  const duration = ((Date.now() - startTime) / 1000).toFixed(2);

  console.log("\n" + "=".repeat(60));
  console.log(`✅ Successfully migrated: ${successCount} users`);
  console.log(`❌ Failed: ${failCount} users`);
  console.log(`⏱️  Duration: ${duration}s`);
  console.log("=".repeat(60));

  // Update MySQL users table with Supabase IDs
  if (mappings.length > 0) {
    await updateUsersTable(mysqlConn, mappings);
  }

  // Save mappings to file
  const fs = await import("fs");
  fs.writeFileSync("./user-mappings.json", JSON.stringify(mappings, null, 2));
  console.log("\n💾 User mappings saved to user-mappings.json");

  // Verify migration
  const verified = await verifyMigration(mysqlConn, supabase);

  // Close MySQL connection
  await mysqlConn.end();

  if (!verified) {
    console.log("\n⚠️  Migration completed with warnings. Please review logs.");
    process.exit(1);
  }

  console.log("\n🎉 User migration complete!\n");
  console.log("Next steps:");
  console.log("  1. Test login with migrated user accounts");
  console.log("  2. Verify 2FA settings (users must re-enable TOTP)");
  console.log("  3. Update application to use Supabase Auth");
  console.log("  4. Run data migration script (migrate-data.ts)");
}

// Run migration
main().catch(console.error);
