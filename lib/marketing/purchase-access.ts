import "server-only";
import { randomUUID } from "node:crypto";
import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { createAdminClient } from "@/lib/supabase/server";
import { getAuthBaseUrl } from "@/lib/supabase/redirect-url";
import { sendEmail } from "@/lib/email/client";

/** Purchase invitations create a new, empty tenant; they never attach an existing user. */
export async function inviteMarketingBuyer(input: { email: string; name: string; company: string; planId: string }) {
  const [existing] = await db.execute(sql`SELECT id FROM users WHERE lower(email)=${input.email} LIMIT 1`);
  if (existing) return;
  const admin = createAdminClient();
  const [role] = await db.execute(sql`SELECT id FROM roles WHERE name='client' LIMIT 1`);
  if (!role) throw Error("Purchase role unavailable");
  const generated = await admin.auth.admin.generateLink({ type: "invite", email: input.email, options: { data: { name: input.name } } });
  if (generated.error || !generated.data.user || !generated.data.properties?.hashed_token) {
    // Existing auth accounts must use normal sign-in/recovery, never reassignment.
    return;
  }
  const authUser = generated.data.user;
  if (authUser.email_confirmed_at || authUser.last_sign_in_at || Date.now() - Date.parse(authUser.created_at) > 30000) return;
  const clientId = randomUUID();
  await db.transaction(async tx => {
    const [profile] = await tx.execute(sql`SELECT id,client_id,is_super_admin FROM users WHERE id=${authUser.id}::uuid FOR UPDATE`);
    const [roles] = await tx.execute(sql`SELECT user_id FROM user_roles WHERE user_id=${authUser.id}::uuid LIMIT 1`);
    if (profile?.client_id || profile?.is_super_admin || roles) throw Error("Existing account cannot be reassigned");
    await tx.execute(sql`INSERT INTO clients(id,company_name,email,status) VALUES(${clientId}::uuid,${input.company},${input.email},'active')`);
    await tx.execute(sql`INSERT INTO users(id,name,email,client_id,is_active,status,is_super_admin) VALUES(${authUser.id}::uuid,${input.name},${input.email},${clientId}::uuid,true,'active',false)
      ON CONFLICT(id) DO UPDATE SET name=excluded.name,client_id=excluded.client_id,is_active=true,status='active' WHERE users.client_id IS NULL AND NOT users.is_super_admin`);
    await tx.execute(sql`INSERT INTO user_roles(user_id,role_id) VALUES(${authUser.id}::uuid,${role.id}::uuid)`);
    // Capability availability never substitutes for the paid-plan gate.
    await tx.execute(sql`INSERT INTO client_features(client_id,feature_id,is_enabled) SELECT ${clientId}::uuid,id,true FROM features WHERE name='marketing_skill_tools' ON CONFLICT DO NOTHING`);
  });
  const url = new URL("/auth/confirm", getAuthBaseUrl());
  url.searchParams.set("token_hash", generated.data.properties.hashed_token);
  url.searchParams.set("type", "invite");
  url.searchParams.set("next", "/marketing/plans?plan=" + input.planId);
  const result = await sendEmail({
    to: input.email,
    subject: "Continue your Kre8ivDesigns marketing purchase",
    text: `Verify your email and continue to the marketing plans:\n${url}\n\nNo payment has been collected. Choose your plan and complete Stripe checkout to activate tools. If you did not request this, ignore this email.`,
  });
  if (!result.success) throw Error("Purchase invitation delivery failed");
}
