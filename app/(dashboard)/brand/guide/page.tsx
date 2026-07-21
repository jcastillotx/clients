import Link from "next/link";
import { redirect } from "next/navigation";
import { BookOpen, Building2, Files, Palette } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  createAdminClientIfAvailable,
  createClient,
} from "@/lib/supabase/server";

export const metadata = {
  title: "Client Brand Guides",
  description: "Manage client brand guidelines and approved assets",
};

interface ClientRow {
  id: string;
  company_name: string;
  logo_url: string | null;
}

interface GuideRow {
  id: string;
  client_id: string | null;
  status: string;
  updated_at: string;
  meta: unknown;
}

function getGuideSummary(meta: unknown): string | null {
  if (!meta || typeof meta !== "object" || Array.isArray(meta)) {
    return null;
  }

  const summary = (meta as Record<string, unknown>).summary;
  return typeof summary === "string" && summary.trim()
    ? summary.trim()
    : null;
}

export default async function BrandGuideDirectoryPage() {
  const access = await resolveStaffAccess();

  if (!access) {
    redirect("/login");
  }

  if (!access.isStaff) {
    redirect("/dashboard");
  }

  const supabase = await createClient();
  const dbClient = createAdminClientIfAvailable() ?? supabase;
  const [clientsResult, guidesResult] = await Promise.all([
    dbClient
      .from("clients")
      .select("id, company_name, logo_url")
      .is("deleted_at", null)
      .order("company_name"),
    dbClient
      .from("brand_guides")
      .select("id, client_id, status, updated_at, meta")
      .not("client_id", "is", null)
      .order("updated_at", { ascending: false }),
  ]);

  if (clientsResult.error) {
    console.error("Error loading clients for brand guides:", clientsResult.error);
  }

  if (guidesResult.error) {
    console.error("Error loading client brand guides:", guidesResult.error);
  }

  const clients = (clientsResult.data ?? []) as ClientRow[];
  const guides = (guidesResult.data ?? []) as GuideRow[];
  const guideByClient = new Map<string, GuideRow>();

  for (const guide of guides) {
    if (guide.client_id && !guideByClient.has(guide.client_id)) {
      guideByClient.set(guide.client_id, guide);
    }
  }

  const publishedCount = Array.from(guideByClient.values()).filter(
    (guide) => guide.status === "published",
  ).length;
  const draftCount = guideByClient.size - publishedCount;
  const unassignedCount = Math.max(clients.length - guideByClient.size, 0);

  return (
    <div className="space-y-8 p-8">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div className="mb-2 flex items-center gap-2 text-sm font-medium text-muted-foreground">
            <Palette className="h-4 w-4" aria-hidden />
            Brand workspace
          </div>
          <h1 className="text-3xl font-bold tracking-tight">
            Client brand guides
          </h1>
          <p className="mt-1 max-w-2xl text-muted-foreground">
            Open a client to manage their logo, color system, typography,
            messaging, and approved brand files.
          </p>
        </div>
        <Button asChild variant="outline">
          <Link href="/clients">
            <Building2 className="mr-2 h-4 w-4" aria-hidden />
            View all clients
          </Link>
        </Button>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <SummaryCard label="Published" value={publishedCount} />
        <SummaryCard label="Draft" value={draftCount} />
        <SummaryCard label="Not started" value={unassignedCount} />
      </div>

      {clients.length ? (
        <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
          {clients.map((client) => {
            const guide = guideByClient.get(client.id);
            const summary = getGuideSummary(guide?.meta);

            return (
              <Card key={client.id} className="flex h-full flex-col">
                <CardHeader>
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex min-w-0 items-center gap-3">
                      <div className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-white p-2">
                        {client.logo_url ? (
                          // eslint-disable-next-line @next/next/no-img-element
                          <img
                            src={client.logo_url}
                            alt={`${client.company_name} logo`}
                            className="max-h-full max-w-full object-contain"
                          />
                        ) : (
                          <Building2
                            className="h-6 w-6 text-muted-foreground"
                            aria-hidden
                          />
                        )}
                      </div>
                      <div className="min-w-0">
                        <CardTitle className="truncate text-lg">
                          {client.company_name}
                        </CardTitle>
                        <CardDescription className="mt-1">
                          Client brand workspace
                        </CardDescription>
                      </div>
                    </div>
                    <Badge
                      variant={
                        guide?.status === "published" ? "default" : "secondary"
                      }
                      className="shrink-0 capitalize"
                    >
                      {guide?.status === "published"
                        ? "Published"
                        : guide
                          ? "Draft"
                          : "Not started"}
                    </Badge>
                  </div>
                </CardHeader>
                <CardContent className="flex-1">
                  <p className="line-clamp-3 text-sm leading-6 text-muted-foreground">
                    {summary ??
                      (guide
                        ? "Continue building this client’s identity, usage guidance, and approved assets."
                        : "Start this client’s guide with their logo, palette, typography, voice, and applications.")}
                  </p>
                </CardContent>
                <CardFooter className="flex flex-wrap gap-2 border-t pt-5">
                  <Button asChild size="sm">
                    <Link href={`/clients/${client.id}/brand`}>
                      <BookOpen className="mr-2 h-4 w-4" aria-hidden />
                      {guide ? "Open guide" : "Create guide"}
                    </Link>
                  </Button>
                  <Button asChild size="sm" variant="outline">
                    <Link href={`/clients/${client.id}/files`}>
                      <Files className="mr-2 h-4 w-4" aria-hidden />
                      Files
                    </Link>
                  </Button>
                </CardFooter>
              </Card>
            );
          })}
        </div>
      ) : (
        <Card className="border-dashed">
          <CardContent className="py-14 text-center">
            <Building2
              className="mx-auto h-9 w-9 text-muted-foreground"
              aria-hidden
            />
            <h2 className="mt-4 text-lg font-semibold">No clients found</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              Add a client before creating a brand guide.
            </p>
            <Button asChild className="mt-5">
              <Link href="/clients/new">Add client</Link>
            </Button>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

function SummaryCard({ label, value }: { label: string; value: number }) {
  return (
    <Card>
      <CardContent className="p-5">
        <p className="text-sm font-medium text-muted-foreground">{label}</p>
        <p className="mt-2 text-3xl font-semibold tracking-tight">{value}</p>
      </CardContent>
    </Card>
  );
}
