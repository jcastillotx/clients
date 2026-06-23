import { eq, sql, type SQL } from "drizzle-orm";

import { db } from "@/lib/db";
import { clients } from "@/lib/db/schema";

type DbTransaction = Parameters<typeof db.transaction>[0] extends (
  tx: infer T,
) => Promise<unknown>
  ? T
  : never;

export type ClientArchiveTableSummary = {
  table: string;
  updated: number;
};

const SOFT_ARCHIVABLE_DIRECT_CLIENT_TABLES = [
  "ad_accounts",
  "announcements",
  "campaigns",
  "content_calendar_items",
  "content_templates",
  "contracts",
  "documents",
  "invoices",
  "leads",
  "maintenance_plans",
  "meetings",
  "projects",
  "proposals",
  "requests",
  "social_accounts",
  "support_tickets",
  "users",
] as const;

const SOFT_ARCHIVABLE_RELATED_CLIENT_TABLES = [
  {
    table: "social_posts",
    clientPredicate: (clientId: string) => sql`
      account_id IN (
        SELECT id FROM public.social_accounts
        WHERE client_id = ${clientId}
      )
    `,
  },
  {
    table: "ad_campaigns",
    clientPredicate: (clientId: string) => sql`
      ad_account_id IN (
        SELECT id FROM public.ad_accounts
        WHERE client_id = ${clientId}
      )
    `,
  },
  {
    table: "ad_sets",
    clientPredicate: (clientId: string) => sql`
      campaign_id IN (
        SELECT ad_campaigns.id
        FROM public.ad_campaigns
        INNER JOIN public.ad_accounts
          ON ad_accounts.id = ad_campaigns.ad_account_id
        WHERE ad_accounts.client_id = ${clientId}
      )
    `,
  },
  {
    table: "ads",
    clientPredicate: (clientId: string) => sql`
      ad_set_id IN (
        SELECT ad_sets.id
        FROM public.ad_sets
        INNER JOIN public.ad_campaigns
          ON ad_campaigns.id = ad_sets.campaign_id
        INNER JOIN public.ad_accounts
          ON ad_accounts.id = ad_campaigns.ad_account_id
        WHERE ad_accounts.client_id = ${clientId}
      )
    `,
  },
  {
    table: "ad_creatives",
    clientPredicate: (clientId: string) => sql`
      ad_account_id IN (
        SELECT id FROM public.ad_accounts
        WHERE client_id = ${clientId}
      )
    `,
  },
  {
    table: "maintenance_plan_usage",
    clientPredicate: (clientId: string) => sql`
      plan_id IN (
        SELECT id FROM public.maintenance_plans
        WHERE client_id = ${clientId}
      )
    `,
  },
  {
    table: "support_ticket_comments",
    clientPredicate: (clientId: string) => sql`
      support_ticket_id IN (
        SELECT id FROM public.support_tickets
        WHERE client_id = ${clientId}
      )
    `,
  },
] as const satisfies ReadonlyArray<{
  table: string;
  clientPredicate: (clientId: string) => SQL;
}>;

function quotedPublicTable(tableName: string) {
  if (!/^[a-z_][a-z0-9_]*$/.test(tableName)) {
    throw new Error(`Unsafe table name: ${tableName}`);
  }

  return sql.raw(`public.${tableName}`);
}

async function archiveAssociatedTable(
  tx: DbTransaction,
  tableName: string,
  archivedAt: Date,
  clientPredicate: SQL,
) {
  const result = await tx.execute<{ count: number }>(sql`
    WITH updated AS (
      UPDATE ${quotedPublicTable(tableName)}
      SET deleted_at = ${archivedAt}
      WHERE deleted_at IS NULL
        AND ${clientPredicate}
      RETURNING 1
    )
    SELECT count(*)::int AS count FROM updated
  `);

  return Number(result[0]?.count ?? 0);
}

async function restoreAssociatedTable(
  tx: DbTransaction,
  tableName: string,
  archivedAt: Date,
  clientPredicate: SQL,
) {
  const result = await tx.execute<{ count: number }>(sql`
    WITH updated AS (
      UPDATE ${quotedPublicTable(tableName)}
      SET deleted_at = NULL
      WHERE deleted_at = ${archivedAt}
        AND ${clientPredicate}
      RETURNING 1
    )
    SELECT count(*)::int AS count FROM updated
  `);

  return Number(result[0]?.count ?? 0);
}

export async function archiveClientWithRecords(clientId: string) {
  return db.transaction(async (tx: DbTransaction) => {
    const [client] = await tx
      .select({
        id: clients.id,
        companyName: clients.companyName,
        deletedAt: clients.deletedAt,
      })
      .from(clients)
      .where(eq(clients.id, clientId))
      .limit(1);

    if (!client) {
      return { client: null, alreadyArchived: false, summaries: [] };
    }

    if (client.deletedAt) {
      return { client, alreadyArchived: true, summaries: [] };
    }

    const archivedAt = new Date();
    const summaries: ClientArchiveTableSummary[] = [];

    for (const tableName of SOFT_ARCHIVABLE_DIRECT_CLIENT_TABLES) {
      const updated = await archiveAssociatedTable(
        tx,
        tableName,
        archivedAt,
        sql`client_id = ${clientId}`,
      );
      summaries.push({ table: tableName, updated });
    }

    for (const {
      table,
      clientPredicate,
    } of SOFT_ARCHIVABLE_RELATED_CLIENT_TABLES) {
      const updated = await archiveAssociatedTable(
        tx,
        table,
        archivedAt,
        clientPredicate(clientId),
      );
      summaries.push({ table, updated });
    }

    const [archivedClient] = await tx
      .update(clients)
      .set({
        deletedAt: archivedAt,
        updatedAt: archivedAt,
      })
      .where(eq(clients.id, clientId))
      .returning({
        id: clients.id,
        companyName: clients.companyName,
        deletedAt: clients.deletedAt,
      });

    return {
      client: archivedClient ?? client,
      alreadyArchived: false,
      summaries,
    };
  });
}

export async function restoreClientWithRecords(clientId: string) {
  return db.transaction(async (tx: DbTransaction) => {
    const [client] = await tx
      .select({
        id: clients.id,
        companyName: clients.companyName,
        deletedAt: clients.deletedAt,
      })
      .from(clients)
      .where(eq(clients.id, clientId))
      .limit(1);

    if (!client) {
      return { client: null, alreadyRestored: false, summaries: [] };
    }

    if (!client.deletedAt) {
      return { client, alreadyRestored: true, summaries: [] };
    }

    const summaries: ClientArchiveTableSummary[] = [];

    for (const tableName of SOFT_ARCHIVABLE_DIRECT_CLIENT_TABLES) {
      const updated = await restoreAssociatedTable(
        tx,
        tableName,
        client.deletedAt,
        sql`client_id = ${clientId}`,
      );
      summaries.push({ table: tableName, updated });
    }

    for (const {
      table,
      clientPredicate,
    } of SOFT_ARCHIVABLE_RELATED_CLIENT_TABLES) {
      const updated = await restoreAssociatedTable(
        tx,
        table,
        client.deletedAt,
        clientPredicate(clientId),
      );
      summaries.push({ table, updated });
    }

    const restoredAt = new Date();
    const [restoredClient] = await tx
      .update(clients)
      .set({
        deletedAt: null,
        updatedAt: restoredAt,
      })
      .where(eq(clients.id, clientId))
      .returning({
        id: clients.id,
        companyName: clients.companyName,
        deletedAt: clients.deletedAt,
      });

    return {
      client: restoredClient ?? client,
      alreadyRestored: false,
      summaries,
    };
  });
}
