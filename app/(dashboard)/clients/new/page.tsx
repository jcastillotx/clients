import { createClient } from "@/lib/supabase/server";
import { ClientForm } from "@/components/clients/client-form";
import { redirect } from "next/navigation";

export const metadata = {
  title: "New Client | KRE8IV",
  description: "Create a new client",
};

/**
 * New client page (Server Component)
 *
 * Provides form for creating a new client.
 */
export default async function NewClientPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Fetch users for primary contact selection
  const { data: users } = await supabase.from("users").select("id, name, email").order("name");

  return (
    <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">New Client</h1>
        <p className="text-muted-foreground">Create a new client account</p>
      </div>

      <ClientForm users={users || []} />
    </div>
  );
}
