import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { redirect } from "next/navigation";
import Link from "next/link";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight, Clock3 } from "lucide-react";
import { format } from "date-fns";

export const metadata = {
  title: "Activity Log | Admin",
  description: "System-wide audit trail of user and system activity.",
};

const PAGE_SIZE = 50;

interface ActivityRow {
  id: string;
  client_id: string | null;
  causer_id: string | null;
  subject_type: string;
  subject_id: string | null;
  description: string;
  properties: Record<string, unknown> | null;
  created_at: string;
  causer: { id: string; name: string | null; email: string | null } | null;
  client: { id: string; company_name: string | null } | null;
}

export default async function AdminActivityLogPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string; subject?: string }>;
}) {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) redirect("/");
  if (!(await isUserAdmin(user.id))) redirect("/dashboard");

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) {
    return (
      <div className="container mx-auto py-8">
        <Card>
          <CardContent className="py-12 text-center text-muted-foreground">
            Activity log requires the Supabase service role to be configured.
          </CardContent>
        </Card>
      </div>
    );
  }

  const { page: pageParam, subject } = await searchParams;
  const page = Math.max(1, parseInt(pageParam ?? "1", 10) || 1);
  const from = (page - 1) * PAGE_SIZE;
  const to = from + PAGE_SIZE - 1;

  let query = adminClient
    .from("activity_logs")
    .select(
      `
      *,
      causer:users!causer_id(id, name, email),
      client:clients(id, company_name)
    `,
      { count: "exact" },
    )
    .order("created_at", { ascending: false })
    .range(from, to);

  if (subject) query = query.eq("subject_type", subject);

  const { data, count } = await query;
  const rows = (data ?? []) as unknown as ActivityRow[];
  const total = count ?? 0;
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

  // Distinct subject types for the filter (best-effort, capped to recent rows)
  const { data: subjectRows } = await adminClient
    .from("activity_logs")
    .select("subject_type")
    .order("created_at", { ascending: false })
    .limit(500);
  const subjectTypes = Array.from(
    new Set((subjectRows ?? []).map((r) => r.subject_type as string)),
  ).sort();

  const buildHref = (overrides: Record<string, string | undefined>) => {
    const params = new URLSearchParams();
    if (subject && overrides.subject !== "") params.set("subject", overrides.subject ?? subject);
    const nextPage = overrides.page ?? String(page);
    if (nextPage !== "1") params.set("page", nextPage);
    const qs = params.toString();
    return qs ? `/admin/activity-log?${qs}` : "/admin/activity-log";
  };

  return (
    <div className="container mx-auto py-8 space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight flex items-center gap-3">
          <Clock3 className="h-7 w-7 text-primary" />
          Activity Log
        </h1>
        <p className="mt-2 text-muted-foreground">
          System-wide audit trail. Newest events first.
        </p>
      </div>

      {/* Subject filter */}
      {subjectTypes.length > 0 && (
        <div className="flex flex-wrap items-center gap-2">
          <span className="text-xs uppercase tracking-wide text-muted-foreground">Filter:</span>
          <Button
            asChild
            variant={subject ? "outline" : "secondary"}
            size="sm"
            className="h-7"
          >
            <Link href={buildHref({ subject: "", page: "1" })}>All</Link>
          </Button>
          {subjectTypes.map((s) => (
            <Button
              key={s}
              asChild
              variant={subject === s ? "secondary" : "outline"}
              size="sm"
              className="h-7"
            >
              <Link href={buildHref({ subject: s, page: "1" })}>{s}</Link>
            </Button>
          ))}
        </div>
      )}

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="text-base">
            {total.toLocaleString()} {total === 1 ? "event" : "events"}
            {subject ? ` · ${subject}` : ""}
          </CardTitle>
          <p className="text-xs text-muted-foreground">
            Page {page} of {totalPages}
          </p>
        </CardHeader>
        <CardContent className="p-0">
          {rows.length === 0 ? (
            <p className="px-6 py-12 text-center text-sm text-muted-foreground">
              No activity to show.
            </p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-xs uppercase text-muted-foreground">
                  <th className="px-4 py-3 font-medium">When</th>
                  <th className="px-4 py-3 font-medium">Actor</th>
                  <th className="px-4 py-3 font-medium">Client</th>
                  <th className="px-4 py-3 font-medium">Subject</th>
                  <th className="px-4 py-3 font-medium">Description</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => (
                  <tr key={row.id} className="border-b last:border-0 hover:bg-muted/40">
                    <td className="whitespace-nowrap px-4 py-3 text-muted-foreground tabular-nums">
                      {format(new Date(row.created_at), "MMM d, yyyy HH:mm")}
                    </td>
                    <td className="px-4 py-3">
                      {row.causer?.name || row.causer?.email || (
                        <span className="text-muted-foreground">System</span>
                      )}
                    </td>
                    <td className="px-4 py-3 text-muted-foreground">
                      {row.client?.company_name ?? "—"}
                    </td>
                    <td className="px-4 py-3">
                      <Badge variant="outline">{row.subject_type}</Badge>
                    </td>
                    <td className="px-4 py-3">{row.description}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </CardContent>
      </Card>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between">
          <Button asChild variant="outline" size="sm" disabled={page <= 1}>
            <Link href={buildHref({ page: String(page - 1) })}>
              <ChevronLeft className="mr-1 h-4 w-4" />
              Previous
            </Link>
          </Button>
          <p className="text-xs text-muted-foreground">
            Showing {from + 1}–{Math.min(from + PAGE_SIZE, total)} of {total.toLocaleString()}
          </p>
          <Button asChild variant="outline" size="sm" disabled={page >= totalPages}>
            <Link href={buildHref({ page: String(page + 1) })}>
              Next
              <ChevronRight className="ml-1 h-4 w-4" />
            </Link>
          </Button>
        </div>
      )}
    </div>
  );
}
