#!/usr/bin/env node

/**
 * Apply Support Tickets Migration
 * 
 * This script applies the support_tickets table migration to your database.
 * 
 * Usage:
 *   node scripts/apply-migration.js
 * 
 * Prerequisites:
 *   - DATABASE_URL environment variable set
 *   - OR .env.local file with DATABASE_URL
 */

const fs = require('fs');
const path = require('path');

async function main() {
  console.log('========================================');
  console.log('Support Tickets Migration Applicator');
  console.log('========================================\n');

  // Try to load .env.local
  const envPath = path.join(process.cwd(), '.env.local');
  if (fs.existsSync(envPath)) {
    console.log('✓ Found .env.local, loading environment variables...');
    const envContent = fs.readFileSync(envPath, 'utf-8');
    envContent.split('\n').forEach(line => {
      const match = line.match(/^([^#][^=]+)=(.+)$/);
      if (match) {
        const key = match[1].trim();
        const value = match[2].trim().replace(/^["']|["']$/g, '');
        if (!process.env[key]) {
          process.env[key] = value;
        }
      }
    });
  }

  // Check for DATABASE_URL
  const databaseUrl = process.env.DATABASE_URL || 
                      process.env.POSTGRES_URL_NON_POOLING || 
                      process.env.POSTGRES_URL;

  if (!databaseUrl) {
    console.error('❌ ERROR: No database connection string found\n');
    console.error('Please set one of the following environment variables:');
    console.error('  - DATABASE_URL');
    console.error('  - POSTGRES_URL_NON_POOLING');
    console.error('  - POSTGRES_URL\n');
    console.error('Or create .env.local with DATABASE_URL set.\n');
    console.error('Example:');
    console.error('  DATABASE_URL="postgresql://postgres:password@db.xxx.supabase.co:5432/postgres"\n');
    process.exit(1);
  }

  console.log('✓ Database connection string found');
  
  // Mask password in output
  const maskedUrl = databaseUrl.replace(/:([\w-]+)@/, ':****@');
  console.log(`  ${maskedUrl}\n`);

  // Check if postgres module is available
  let postgres;
  try {
    postgres = require('postgres');
  } catch (err) {
    console.error('❌ ERROR: postgres module not found\n');
    console.error('Please install dependencies first:');
    console.error('  pnpm install\n');
    process.exit(1);
  }

  console.log('✓ Dependencies found\n');

  // Read all migration files in order
  const migrationFiles = [
    '000_create_core_tables.sql',
    '001_create_rbac_tables.sql',
    '001.5_add_rbac_policies.sql',
    '002_create_template_tables.sql',
    '003_create_document_tables.sql',
    '004_create_support_tickets_tables.sql',
    '005_create_application_tables.sql',
    '006_create_announcements_table.sql',
    '010_feature_flags.sql'
  ];

  console.log('📁 Checking for required migration files:\n');
  const migrations = [];
  const missingFiles = [];
  
  for (const file of migrationFiles) {
    const migrationPath = path.join(process.cwd(), 'lib/db/migrations', file);
    if (fs.existsSync(migrationPath)) {
      console.log(`  ✓ ${file}`);
      migrations.push({
        name: file,
        path: migrationPath,
        sql: fs.readFileSync(migrationPath, 'utf-8')
      });
    } else {
      console.log(`  ❌ ${file} (MISSING!)`);
      missingFiles.push(file);
    }
  }
  
  // Hard error if any migration file is missing
  if (missingFiles.length > 0) {
    console.error('\n❌ ERROR: Required migration files are missing!\n');
    console.error('Missing files:');
    missingFiles.forEach(file => console.error(`  - ${file}`));
    console.error('\nMigrations have strict dependencies and must be run in order.');
    console.error('A missing migration file will cause later migrations to fail.');
    console.error('\nPlease ensure all migration files are present in lib/db/migrations/\n');
    process.exit(1);
  }
  
  console.log(`\n✓ All ${migrations.length} required migration files found\n`);

  // Connect to database
  console.log('Connecting to database...');
  const sql = postgres(databaseUrl, {
    max: 1,
    idle_timeout: 20,
    connect_timeout: 10,
  });

  try {
    // Test connection
    await sql`SELECT 1 as test`;
    console.log('✓ Connected to database\n');

    // Check if tables already exist
    console.log('Checking if tables already exist...');
    const existingTables = await sql`
      SELECT table_name 
      FROM information_schema.tables 
      WHERE table_schema = 'public' 
      AND table_name IN ('support_tickets', 'support_ticket_comments')
    `;

    if (existingTables.length > 0) {
      console.log('\n⚠️  WARNING: Tables already exist:');
      existingTables.forEach(t => console.log(`  - ${t.table_name}`));
      console.log('\nThe migration uses "CREATE TABLE IF NOT EXISTS" so it should be safe to continue.');
      console.log('Existing data will not be affected.\n');
    }

    // Apply migrations in order
    console.log('Applying migrations...');
    console.log('This may take 30-60 seconds...\n');

    for (const migration of migrations) {
      console.log(`📝 Running: ${migration.name}...`);
      try {
        await sql.unsafe(migration.sql);
        console.log(`   ✓ ${migration.name} completed\n`);
      } catch (err) {
        if (err.message.includes('already exists') || err.code === '42P07') {
          console.log(`   ⚠️  ${migration.name} - tables already exist (skipping)\n`);
        } else {
          throw new Error(`Failed in ${migration.name}: ${err.message}`);
        }
      }
    }

    console.log('✓ All migrations applied successfully!\n');

    // Verify tables exist
    console.log('Verifying tables...');
    const tables = await sql`
      SELECT table_name, 
             (SELECT COUNT(*) FROM information_schema.columns 
              WHERE table_schema = 'public' 
              AND table_name = t.table_name) as column_count
      FROM information_schema.tables t
      WHERE table_schema = 'public' 
      AND table_name IN ('support_tickets', 'support_ticket_comments')
      ORDER BY table_name
    `;

    if (tables.length === 2) {
      console.log('✓ Tables verified:\n');
      tables.forEach(t => {
        console.log(`  ✓ ${t.table_name} (${t.column_count} columns)`);
      });
      console.log('');
    } else {
      console.error('⚠️  WARNING: Expected 2 tables but found', tables.length);
    }

    // Check indexes
    const indexes = await sql`
      SELECT indexname 
      FROM pg_indexes 
      WHERE schemaname = 'public' 
      AND tablename IN ('support_tickets', 'support_ticket_comments')
      ORDER BY indexname
    `;

    console.log(`✓ Indexes created: ${indexes.length}`);

    // Check RLS policies
    const policies = await sql`
      SELECT tablename, COUNT(*) as policy_count
      FROM pg_policies
      WHERE schemaname = 'public'
      AND tablename IN ('support_tickets', 'support_ticket_comments')
      GROUP BY tablename
      ORDER BY tablename
    `;

    if (policies.length > 0) {
      console.log('✓ RLS policies created:');
      policies.forEach(p => {
        console.log(`  - ${p.tablename}: ${p.policy_count} policies`);
      });
    }

    console.log('\n========================================');
    console.log('✅ Migration Complete!');
    console.log('========================================\n');
    console.log('Next steps:');
    console.log('1. Restart your Next.js development server (if running)');
    console.log('2. Visit: http://localhost:3000/support');
    console.log('3. You should see the support tickets page load successfully\n');

  } catch (error) {
    console.error('\n❌ Migration failed!\n');
    console.error('Error:', error.message);
    
    if (error.code === 'ECONNREFUSED') {
      console.error('\nDatabase connection refused. Please check:');
      console.error('  - Database server is running');
      console.error('  - Connection string is correct');
      console.error('  - Firewall allows connection');
    } else if (error.code === '42P07') {
      console.error('\nTable already exists. This is usually fine.');
      console.error('The migration uses CREATE TABLE IF NOT EXISTS.');
    } else if (error.message.includes('password authentication failed')) {
      console.error('\nAuthentication failed. Please check:');
      console.error('  - Database password is correct');
      console.error('  - User has necessary permissions');
    }
    
    console.error('\nFull error details:');
    console.error(error);
    process.exit(1);
  } finally {
    await sql.end();
  }
}

main().catch(err => {
  console.error('Unexpected error:', err);
  process.exit(1);
});
