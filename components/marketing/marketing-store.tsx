"use client";
import { useState } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { TurnstileWidget } from "@/components/shared/turnstile-widget";
import { marketingToolGroups } from "@/lib/ai/marketing-skills/menu";

type Plan = { id: string; name: string; groups: string[]; price: number; currency: string };
export function MarketingStore({ plans, signedIn }: { plans: Plan[]; signedIn: boolean }) {
  const [selected, setSelected] = useState(plans[0]?.id ?? "");
  const [token, setToken] = useState("");
  const [notice, setNotice] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const next = "/marketing/plans?plan=" + selected;
  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError(""); setNotice("");
    const fields = Object.fromEntries(new FormData(event.currentTarget));
    try {
      const response = await fetch("/api/public/marketing-access", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ ...fields, planId: selected, turnstileToken: token }) });
      const result = await response.json();
      if (!response.ok) throw Error(result.error?.message || "Unable to continue.");
      setNotice(result.data.message);
    } catch (failure) { setError(failure instanceof Error ? failure.message : "Unable to continue."); }
    finally { setBusy(false); }
  }
  return <main className="mx-auto max-w-6xl space-y-8 px-5 py-12">
    <Link className="font-bold" href="https://www.kre8ivdesigns.com">Kre8ivDesigns</Link>
    <div className="space-y-3"><h1 className="text-4xl font-bold">Marketing tools for your next stage</h1><p className="max-w-3xl text-muted-foreground">Monthly software access and AI draft tools. Choose your package, verify your email, then complete secure Stripe checkout. Agency services, advertising spend and third-party subscriptions are separate.</p><p>Monthly billing. Manage or cancel your subscription in the portal; paid access continues through the current billing period when you cancel.</p></div>
    {!plans.length && <p role="status">Packages are currently unavailable. Please check back shortly.</p>}
    <div className="grid gap-5 md:grid-cols-3">{plans.map(plan => <section key={plan.id} className="space-y-5 rounded-xl border bg-card p-6 text-card-foreground"><h2 className="text-2xl font-semibold">{plan.name}</h2><p className="text-3xl font-bold">{new Intl.NumberFormat("en-US", { style: "currency", currency: plan.currency, minimumFractionDigits: plan.price % 100 ? 2 : 0, maximumFractionDigits: 2 }).format(plan.price / 100)}<span className="text-sm font-normal">/month</span></p><ul className="space-y-2">{plan.groups.map(group => <li key={group}>{marketingToolGroups.find(item => item.id === group)?.name ?? group}</li>)}</ul><a className="inline-block rounded-md bg-primary px-4 py-2 text-primary-foreground" href={signedIn ? "/marketing/plans?plan=" + plan.id : "#purchase-access"} onClick={() => setSelected(plan.id)}>Buy {plan.name}</a></section>)}</div>
    {!!plans.length && !signedIn && <section id="purchase-access" className="max-w-xl scroll-mt-8 space-y-5 rounded-xl border p-6"><h2 className="text-2xl font-semibold">Continue your purchase</h2><p>New customers receive a secure email invitation. Your account starts with no paid tools; checkout activates only the package you buy.</p><form onSubmit={submit} className="space-y-4"><label className="block">Package<select className="mt-1 w-full rounded border bg-background p-2" value={selected} onChange={event => setSelected(event.target.value)}>{plans.map(plan => <option key={plan.id} value={plan.id}>{plan.name}</option>)}</select></label><label className="block">Your name<Input name="name" required minLength={2} maxLength={100} autoComplete="name" /></label><label className="block">Company<Input name="company" required minLength={2} maxLength={150} autoComplete="organization" /></label><label className="block">Email<Input name="email" type="email" required maxLength={254} autoComplete="email" /></label><div className="hidden" aria-hidden="true"><input name="contactFax" tabIndex={-1} autoComplete="off" /></div><TurnstileWidget onVerify={setToken} onExpire={() => setToken("")} /><Button disabled={busy || (!token && (process.env.NODE_ENV === "production" || Boolean(process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY)))} type="submit">{busy ? "Sending…" : "Email my purchase link"}</Button>{notice && <p role="status">{notice}</p>}{error && <p role="alert">{error}</p>}</form><Link className="underline" href={"/login?next=" + encodeURIComponent(next)}>Already a client? Sign in to buy</Link></section>}
    <p className="text-sm text-muted-foreground">AI output is a draft for review. Publishing, ad spend and access to external accounts require your approval and connection. No performance or ranking result is guaranteed.</p>
  </main>;
}
