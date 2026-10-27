"use client";
import { useRouter, useSearchParams } from "next/navigation";
import { useEffect, useState, useCallback } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { marketingToolGroups } from "@/lib/ai/marketing-skills/menu";
type Plan = {
  id: string;
  name: string;
  tool_groups: string[];
  monthly_price_cents: number;
  currency: string;
  published: boolean;
};
export function MarketingPlans() {
  const router = useRouter(),
    query = useSearchParams();
  const [notice, setNotice] = useState("");
  const [billingMode, setBillingMode] = useState("");
  const [loaded, setLoaded] = useState(false);
  const [billing, setBilling] = useState<{
    status: string;
    cancel_at_period_end: boolean;
    ends_at: string;
  } | null>(null);
  const [plans, setPlans] = useState<Plan[]>([]),
    [isAdmin, setIsAdmin] = useState(false),
    [current, setCurrent] = useState<{ name: string } | null>(null),
    [error, setError] = useState(""),
    [busy, setBusy] = useState(false);
  const load = useCallback(async () => {
    const r = await fetch("/api/marketing/plans");
    const b = await r.json();
    if (!r.ok) throw Error(b.error?.message || "Unable to load plans");
    setPlans(b.data.plans);
    setIsAdmin(b.data.isAdmin);
    setCurrent(b.data.subscription);
    setBilling(b.data.billing);
    setBillingMode(b.data.billingMode);
    setLoaded(true);
    return Boolean(b.data.subscription);
  }, []);
  useEffect(() => {
    void load().catch((e) => setError(e.message));
  }, [load]);
  useEffect(() => {
    if (current) router.refresh();
  }, [current, router]);
  useEffect(() => {
    if (query.get("checkout") !== "complete" || current) return;
    let attempts = 0;
    const timer = setInterval(() => {
      if (++attempts > 10) {
        clearInterval(timer);
        return;
      }
      void load().catch(() => {});
    }, 2000);
    return () => clearInterval(timer);
  }, [query, current, load]);
  async function action(path: string, body?: unknown, method = "POST") {
    setBusy(true);
    setError("");
    setNotice("");
    try {
      const r = await fetch(path, {
        method,
        headers: { "Content-Type": "application/json" },
        body: body ? JSON.stringify(body) : undefined,
      });
      const b = await r.json();
      if (!r.ok) throw Error(b.error?.message || "Unable to complete request");
      if (b.data.url) window.location.assign(b.data.url);
      else {
        await load();
        setNotice("Plan saved.");
      }
    } catch (e) {
      setError(e instanceof Error ? e.message : "Unable to complete request");
    } finally {
      setBusy(false);
    }
  }
  return (
    <div className="space-y-6 p-6">
      <h1 className="text-3xl font-bold">Marketing Plans</h1>
      <p>
        Monthly access to marketing software and AI draft tools. Agency
        services, advertising spend and third-party subscriptions are separate.
      </p>
      {query.get("checkout") === "cancelled" && <p role="status">Checkout was cancelled. No new subscription was activated. You can resume checkout below.</p>}
      {query.get("checkout") === "complete" && !current && <p role="status">Confirming your payment. Tools unlock after Stripe verifies your subscription; this may take a moment.</p>}
      {billingMode === "test" && (
        <p role="status" className="rounded border p-3">
          Stripe sandbox: test payments only. No real charges.
        </p>
      )}
      {isAdmin && (
        <p className="rounded border p-3">
          Draft pricing for review. Unpublished plans cannot be purchased.
          Publishing enables the displayed monthly price for new subscriptions;
          existing Stripe subscriptions retain their agreed price.
        </p>
      )}
      {error && <p role="alert">{error}</p>}
      {notice && <p role="status">{notice}</p>}
      {!loaded && <p role="status">Loading marketing plans…</p>}
      {(current || billing) && (
        <div>
          <p>
            {current
              ? `Current plan: ${current.name}`
              : "Your subscription needs attention. Open billing to update or cancel it."}
          </p>
          {billing?.cancel_at_period_end && (
            <p>
              Cancellation scheduled. Access continues through{" "}
              {new Date(billing.ends_at).toLocaleDateString()}.
            </p>
          )}
          {billing && (
            <Button
              disabled={busy}
              onClick={() => void action("/api/marketing/plans/billing")}
            >
              Manage billing
            </Button>
          )}
        </div>
      )}
      {loaded && !plans.length && (
        <p>
          No marketing plans are currently available for purchase. Contact your
          account manager.
        </p>
      )}
      <div className="grid gap-4 lg:grid-cols-3">
        {plans.map((p) => (
          <Card key={p.id} className={query.get("plan") === p.id ? "ring-2 ring-primary" : undefined}>
            <CardHeader>
              <CardTitle>{p.name}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {isAdmin ? (
                <>
                  <label className="block">
                    Plan name
                    <Input
                      value={p.name}
                      onChange={(e) =>
                        setPlans(
                          plans.map((x) =>
                            x.id === p.id ? { ...x, name: e.target.value } : x,
                          ),
                        )
                      }
                    />
                  </label>
                  <label className="block">
                    Monthly price (USD)
                    <Input
                      type="number"
                      min="1"
                      step="1"
                      value={p.monthly_price_cents / 100}
                      onChange={(e) =>
                        setPlans(
                          plans.map((x) =>
                            x.id === p.id
                              ? {
                                  ...x,
                                  monthly_price_cents: Math.round(
                                    Number(e.target.value) * 100,
                                  ),
                                }
                              : x,
                          ),
                        )
                      }
                    />
                  </label>
                </>
              ) : (
                <p className="text-2xl">
                  ${(p.monthly_price_cents / 100).toFixed(2)} / month
                </p>
              )}
              <ul className="space-y-2">
                {marketingToolGroups
                  .filter((g) => isAdmin || p.tool_groups.includes(g.id))
                  .map((g) => (
                    <li key={g.id}>
                      {isAdmin ? (
                        <label>
                          <input
                            type="checkbox"
                            checked={p.tool_groups.includes(g.id)}
                            onChange={(e) =>
                              setPlans(
                                plans.map((x) =>
                                  x.id === p.id
                                    ? {
                                        ...x,
                                        tool_groups: e.target.checked
                                          ? [...x.tool_groups, g.id]
                                          : x.tool_groups.filter(
                                              (id) => id !== g.id,
                                            ),
                                      }
                                    : x,
                                ),
                              )
                            }
                          />{" "}
                          {g.name}
                        </label>
                      ) : (
                        g.name
                      )}
                    </li>
                  ))}
              </ul>
              {isAdmin ? (
                <>
                  <label className="block">
                    <input
                      type="checkbox"
                      checked={p.published}
                      onChange={(e) =>
                        setPlans(
                          plans.map((x) =>
                            x.id === p.id
                              ? { ...x, published: e.target.checked }
                              : x,
                          ),
                        )
                      }
                    />{" "}
                    Available for purchase
                  </label>
                  <Button
                    disabled={busy}
                    onClick={() =>
                      void action(
                        "/api/marketing/plans",
                        {
                          id: p.id,
                          name: p.name,
                          monthlyPriceCents: p.monthly_price_cents,
                          groups: p.tool_groups,
                          published: p.published,
                        },
                        "PATCH",
                      )
                    }
                  >
                    Save plan
                  </Button>
                </>
              ) : (
                <Button
                  disabled={
                    busy ||
                    Boolean(current) ||
                    Boolean(billing && billing.status !== "cancelled")
                  }
                  onClick={() =>
                    void action("/api/marketing/plans/checkout", {
                      planId: p.id,
                    })
                  }
                >
                  Subscribe · ${(p.monthly_price_cents / 100).toFixed(2)}/month
                </Button>
              )}
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
