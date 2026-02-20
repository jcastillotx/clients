import type { SupabaseClient, User as SupabaseAuthUser } from "@supabase/supabase-js";

const AUTH_USERS_PAGE_SIZE = 200;
const AUTH_USERS_MAX_PAGES = 50;
const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

type UserProfilePayload = {
  id: string;
  name: string;
  email: string;
  phone?: string;
  avatar?: string;
  client_id?: string;
  is_super_admin?: boolean;
  updated_at: string;
};

function asObject(value: unknown): Record<string, unknown> {
  if (value && typeof value === "object") {
    return value as Record<string, unknown>;
  }

  return {};
}

function asNonEmptyString(value: unknown): string | null {
  if (typeof value !== "string") {
    return null;
  }

  const trimmed = value.trim();
  return trimmed.length > 0 ? trimmed : null;
}

function asUuid(value: unknown): string | null {
  const stringValue = asNonEmptyString(value);
  if (!stringValue) {
    return null;
  }

  return UUID_PATTERN.test(stringValue) ? stringValue : null;
}

function buildUserProfilePayload(authUser: SupabaseAuthUser): UserProfilePayload | null {
  const email = asNonEmptyString(authUser.email);
  if (!email) {
    return null;
  }

  const metadata = asObject(authUser.user_metadata);
  const name = asNonEmptyString(metadata.name) ?? email;
  const phone = asNonEmptyString(metadata.phone);
  const avatar = asNonEmptyString(metadata.avatar);
  const clientId = asUuid(metadata.client_id);
  const isSuperAdmin = metadata.is_super_admin === true;

  return {
    id: authUser.id,
    name,
    email,
    ...(phone ? { phone } : {}),
    ...(avatar ? { avatar } : {}),
    ...(clientId ? { client_id: clientId } : {}),
    ...(isSuperAdmin ? { is_super_admin: true } : {}),
    updated_at: new Date().toISOString(),
  };
}

async function listAllAuthUsers(adminClient: SupabaseClient): Promise<SupabaseAuthUser[]> {
  const users: SupabaseAuthUser[] = [];
  let page = 1;

  while (page <= AUTH_USERS_MAX_PAGES) {
    const { data, error } = await adminClient.auth.admin.listUsers({
      page,
      perPage: AUTH_USERS_PAGE_SIZE,
    });

    if (error) {
      console.error("Failed to list auth users for profile sync:", error);
      return users;
    }

    const currentPageUsers = data?.users ?? [];
    users.push(...currentPageUsers);

    if (currentPageUsers.length < AUTH_USERS_PAGE_SIZE) {
      break;
    }

    page += 1;
  }

  return users;
}

export async function ensureAuthUserProfile(adminClient: SupabaseClient, authUser: SupabaseAuthUser) {
  const payload = buildUserProfilePayload(authUser);
  if (!payload) {
    return;
  }

  const { error } = await adminClient
    .from("users")
    .upsert(payload, { onConflict: "id", ignoreDuplicates: true });

  if (error) {
    console.error(`Failed to ensure profile for auth user ${authUser.id}:`, error);
  }
}

export async function syncMissingAuthUsers(adminClient: SupabaseClient) {
  const authUsers = await listAllAuthUsers(adminClient);
  if (authUsers.length === 0) {
    return;
  }

  const payloads = authUsers
    .map((authUser) => buildUserProfilePayload(authUser))
    .filter((payload): payload is UserProfilePayload => payload !== null);

  if (payloads.length === 0) {
    return;
  }

  const authUserIds = payloads.map((payload) => payload.id);
  const { data: existingRows, error: existingRowsError } = await adminClient.from("users").select("id").in("id", authUserIds);

  if (existingRowsError) {
    console.error("Failed to load existing user IDs during auth profile sync:", existingRowsError);
    return;
  }

  const existingIds = new Set(((existingRows ?? []) as Array<{ id: string }>).map((row) => row.id));
  const missingPayloads = payloads.filter((payload) => !existingIds.has(payload.id));

  if (missingPayloads.length === 0) {
    return;
  }

  const { error: insertError } = await adminClient.from("users").insert(missingPayloads);
  if (!insertError) {
    return;
  }

  console.warn("Bulk user profile sync failed; retrying users one by one:", insertError);

  for (const payload of missingPayloads) {
    const { error } = await adminClient
      .from("users")
      .upsert(payload, { onConflict: "id", ignoreDuplicates: true });

    if (error) {
      console.error(`Failed to backfill auth user ${payload.id} in public.users:`, error);
    }
  }
}
