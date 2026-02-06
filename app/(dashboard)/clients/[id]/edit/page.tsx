import { createClient } from "@/lib/supabase/server";
import { ClientForm } from "@/components/clients/client-form";
import { notFound, redirect } from "next/navigation";

interface EditClientPageProps {
  params: Promise<{
    id: string;
  }>;
}

export const metadata = {
  title: "Edit Client | KRE8IV",
  description: "Edit client details",
};

/**
 * Edit client page (Server Component)
 *
 * Fetches existing client data and provides edit form.
 */
export default async function EditClientPage({ params }: EditClientPageProps) {
  const { id } = await params;
  const supabase = createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Fetch client data
  const { data: client, error } = await supabase.from("clients").select("*").eq("id", id).single();

  if (error || !client) {
    notFound();
  }

  // Fetch users for primary contact selection
  const { data: users } = await supabase.from("users").select("id, name, email").order("name");

  return (
    <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Edit Client</h1>
        <p className="text-muted-foreground">Update {client.company_name} details</p>
      </div>

      <ClientForm users={users || []} initialData={client} />
    </div>
  );
}
