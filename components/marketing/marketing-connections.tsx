"use client";
import { useEffect, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
type Provider = "google_analytics" | "google_ads";
type Item = {
  provider: Provider;
  permitted: boolean;
  connection: { resource_id: string | null } | null;
};
export function MarketingConnections() {
  const query = useSearchParams();
  const [loaded, setLoaded] = useState(false);
  const [items, setItems] = useState<Item[]>([]),
    [configured, setConfigured] = useState(false),
    [error, setError] = useState(""),
    [busy, setBusy] = useState(false),
    [resources, setResources] = useState<
      Partial<Record<Provider, Array<{ id: string; name: string }>>>
    >({}),
    [report, setReport] = useState("");
  async function load() {
    const r = await fetch("/api/marketing/connections");
    const b = await r.json();
    if (!r.ok) throw Error(b.error?.message || "Connections unavailable");
    setItems(b.data.providers);
    setConfigured(b.data.configured);
    setLoaded(true);
  }
  useEffect(() => {
    void load().catch((e) => setError(e.message));
  }, []);
  async function act(
    provider: Provider,
    kind: "connect" | "list" | "select" | "disconnect" | "report",
    resourceId?: string,
  ) {
    setBusy(true);
    setError("");
    try {
      const path =
        "/api/marketing/connections" +
        (kind === "connect" ? "/connect" : kind === "report" ? "/report" : "");
      const r = await fetch(path, {
        method: kind === "disconnect" ? "DELETE" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ provider, resourceId }),
      });
      const b = await r.json();
      if (!r.ok) throw Error(b.error?.message || "Request failed");
      if (b.data.url) window.location.assign(b.data.url);
      else if (kind === "report") setReport(JSON.stringify(b.data, null, 2));
      else {
        if (b.data.resources)
          setResources((x) => ({ ...x, [provider]: b.data.resources }));
        await load();
      }
    } catch (e) {
      setError(e instanceof Error ? e.message : "Request failed");
    } finally {
      setBusy(false);
    }
  }
  return (
    <div className="space-y-6 p-6">
      <h1 className="text-3xl font-bold">Marketing Connections</h1>
      <p>
        Connect your own Google accounts and select the property or advertising
        account to use. Connections belong to your user within this client
        account.
      </p>
      <p>
        GA4 requests read-only Analytics access. Google Ads uses Google&apos;s
        broader Ads permission; this portal only reads reports and does not
        change campaigns or spend money. You can remove this app&apos;s grant in
        your Google account at any time.
      </p>
      {query.get("error") === "connection_failed" && (
        <p role="alert">
          Google connection was not completed or the authorization session
          expired. Choose Connect Google account to start again.
        </p>
      )}
      {items.some((item) => item.provider === query.get("connected") && item.connection) && (
        <p role="status">
          Google authorization completed. Choose an account or property below.
        </p>
      )}
      {error && (
        <p role="alert">
          {error}{" "}
          <Link href="/marketing/plans" className="underline">
            View marketing plans
          </Link>
        </p>
      )}
      {loaded && !items.length && (
        <p>
          Your marketing plan does not include Google reporting connections.{" "}
          <Link className="underline" href="/marketing/plans">
            View marketing plans
          </Link>
        </p>
      )}
      {!loaded && !error && <p role="status">Loading connections…</p>}
      <div className="grid gap-4 md:grid-cols-2">
        {items.map((p) => (
          <Card key={p.provider}>
            <CardHeader>
              <CardTitle>
                {p.provider === "google_ads"
                  ? "Google Ads"
                  : "Google Analytics 4"}
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <p>
                {p.connection ? "Connected" : "Not connected"}
                {p.connection?.resource_id
                  ? " · " + p.connection.resource_id
                  : ""}
              </p>
              <Button
                disabled={busy || !configured || !p.permitted}
                onClick={() => void act(p.provider, "connect")}
              >
                {p.connection ? "Reconnect" : "Connect Google account"}
              </Button>
              {!configured && (
                <p>
                  Google marketing OAuth setup is required by your account
                  manager.
                </p>
              )}
              {!p.permitted && (
                <p>
                  Your plan does not include this connection. You can still
                  disconnect it.
                </p>
              )}
              {p.connection && (
                <>
                  <Button
                    variant="outline"
                    disabled={busy || !p.permitted}
                    onClick={() => void act(p.provider, "list")}
                  >
                    Choose account or property
                  </Button>
                  {resources[p.provider] && (
                    <label className="block">
                      Account or property
                      <select
                        aria-label={p.provider + " resource"}
                        className="block w-full rounded border p-2 bg-background"
                        value={p.connection.resource_id || ""}
                        onChange={(e) =>
                          void act(p.provider, "select", e.target.value)
                        }
                      >
                        <option value="">Select…</option>
                        {resources[p.provider]?.map((r) => (
                          <option key={r.id} value={r.id}>
                            {r.name}
                          </option>
                        ))}
                      </select>
                      {!resources[p.provider]?.length && (
                        <p>No accessible accounts or properties found.</p>
                      )}
                    </label>
                  )}
                  <Button
                    disabled={busy || !p.permitted || !p.connection.resource_id}
                    onClick={() => void act(p.provider, "report")}
                  >
                    Read performance summary
                  </Button>
                  <Button
                    variant="outline"
                    disabled={busy}
                    onClick={() => void act(p.provider, "disconnect")}
                  >
                    Disconnect
                  </Button>
                </>
              )}
            </CardContent>
          </Card>
        ))}
      </div>
      {report && (
        <section className="space-y-2">
          <h2 className="text-xl font-semibold">Performance summary</h2>
          <p>
            Review these aggregate metrics before copying them into a marketing
            tool&apos;s supporting data. Draft creation sends supplied data to
            the configured AI provider.
          </p>
          <pre className="overflow-auto whitespace-pre-wrap rounded border p-4 text-sm">
            {report}
          </pre>
        </section>
      )}
    </div>
  );
}
