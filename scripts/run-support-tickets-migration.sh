#!/bin/bash

# Script to run the support_tickets migration
# Usage: ./scripts/run-support-tickets-migration.sh

set -e  # Exit on error

echo "========================================="
echo "Support Tickets Migration Runner"
echo "========================================="
echo ""

# Check if DATABASE_URL is set
if [ -z "$DATABASE_URL" ]; then
  echo "❌ ERROR: DATABASE_URL environment variable is not set"
  echo ""
  echo "Please set DATABASE_URL before running this script:"
  echo "  export DATABASE_URL='postgresql://postgres:[PASSWORD]@db.xxx.supabase.co:5432/postgres'"
  echo ""
  echo "Or add it to your .env.local file and source it:"
  echo "  source .env.local"
  exit 1
fi

echo "✓ Database connection string found"
echo ""

# Check if psql is installed
if ! command -v psql &> /dev/null; then
  echo "❌ ERROR: psql command not found"
  echo ""
  echo "Please install PostgreSQL client tools:"
  echo "  - macOS: brew install postgresql"
  echo "  - Ubuntu/Debian: sudo apt-get install postgresql-client"
  echo "  - Windows: Download from https://www.postgresql.org/download/"
  exit 1
fi

echo "✓ PostgreSQL client tools found"
echo ""

# Run the migration
MIGRATION_FILE="lib/db/migrations/004_create_support_tickets_tables.sql"

if [ ! -f "$MIGRATION_FILE" ]; then
  echo "❌ ERROR: Migration file not found: $MIGRATION_FILE"
  exit 1
fi

echo "📄 Running migration: $MIGRATION_FILE"
echo ""

if psql "$DATABASE_URL" -f "$MIGRATION_FILE"; then
  echo ""
  echo "========================================="
  echo "✅ Migration completed successfully!"
  echo "========================================="
  echo ""
  echo "The following tables have been created:"
  echo "  - public.support_tickets"
  echo "  - public.support_ticket_comments"
  echo ""
  echo "You can now access the support tickets feature at:"
  echo "  http://localhost:3000/support"
  echo ""
else
  echo ""
  echo "========================================="
  echo "❌ Migration failed!"
  echo "========================================="
  echo ""
  echo "Please check the error messages above for details."
  echo "Common issues:"
  echo "  - Database credentials are incorrect"
  echo "  - Tables already exist (check with \\dt in psql)"
  echo "  - Network connection issues"
  echo "  - Insufficient permissions"
  exit 1
fi

# Verify tables were created
echo "Verifying tables..."
echo ""

psql "$DATABASE_URL" -c "\dt public.support_tickets" -c "\dt public.support_ticket_comments"

echo ""
echo "========================================="
echo "Migration verification complete!"
echo "========================================="
