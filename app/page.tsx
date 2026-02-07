import { redirect } from "next/navigation";
import { LoginForm } from "@/components/auth/login-form";
import { CheckCircle2 } from "lucide-react";

export const dynamic = "force-dynamic";

export const metadata = {
  title: "Sign In | KRE8IV",
  description: "Sign in to your KRE8IV account",
};

export default async function HomePage() {
  // Check if user is already authenticated — redirect to dashboard
  // Wrapped in try/catch so the page still renders if Supabase is unavailable
  try {
    const { createClient } = await import("@/lib/supabase/server");
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (user) {
      redirect("/dashboard");
    }
  } catch (error: unknown) {
    // Next.js redirect() works by throwing — let it propagate
    if (
      error &&
      typeof error === "object" &&
      "digest" in error &&
      typeof (error as Record<string, unknown>).digest === "string" &&
      ((error as Record<string, unknown>).digest as string).startsWith("NEXT_REDIRECT")
    ) {
      throw error;
    }
    // Supabase not ready — fall through and render the login form
  }

  return (
    <div className="grid min-h-screen bg-gradient-to-br from-background via-secondary/40 to-background lg:grid-cols-[1.05fr_0.95fr]">
      {/* Left side - Login Form */}
      <div className="relative flex flex-col justify-center overflow-y-auto px-6 py-12 lg:px-16 xl:px-24">
        <div className="pointer-events-none absolute inset-0 opacity-60 mix-blend-multiply">
          <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(95,95,130,0.12),transparent_32%),radial-gradient(circle_at_80%_0%,rgba(95,95,130,0.08),transparent_26%),radial-gradient(circle_at_40%_80%,rgba(64,77,102,0.12),transparent_30%)]" />
        </div>
        <div className="mx-auto w-full max-w-md relative">
          <LoginForm />
        </div>
      </div>

      {/* Right side - Marketing Portal Hero */}
      <div className="relative hidden lg:block">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.18),transparent_28%),radial-gradient(circle_at_78%_12%,rgba(255,255,255,0.1),transparent_20%),linear-gradient(140deg,#5F5F82_0%,#4d5b7a_52%,#2d3e57_100%)]" />
        <div className="absolute inset-0 opacity-40 bg-[linear-gradient(to_right,rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.08)_1px,transparent_1px)] bg-[size:52px_52px]" />

        {/* Floating analytics cards */}
        <div className="absolute left-10 top-12 w-[320px] rounded-2xl border border-white/20 bg-white/10 p-5 backdrop-blur-md">
          <p className="text-xs uppercase tracking-[0.16em] text-white/75">Campaign Performance</p>
          <div className="mt-4 flex items-end gap-2">
            <div className="h-10 w-6 rounded-t-md bg-white/35" />
            <div className="h-14 w-6 rounded-t-md bg-white/45" />
            <div className="h-8 w-6 rounded-t-md bg-white/30" />
            <div className="h-16 w-6 rounded-t-md bg-accent/90" />
            <div className="h-12 w-6 rounded-t-md bg-white/40" />
            <div className="h-20 w-6 rounded-t-md bg-white/60" />
          </div>
          <div className="mt-4 grid grid-cols-3 gap-3 text-white/90">
            <div>
              <p className="text-[11px] text-white/70">CTR</p>
              <p className="text-sm font-semibold">4.8%</p>
            </div>
            <div>
              <p className="text-[11px] text-white/70">Leads</p>
              <p className="text-sm font-semibold">1,248</p>
            </div>
            <div>
              <p className="text-[11px] text-white/70">ROAS</p>
              <p className="text-sm font-semibold">3.9x</p>
            </div>
          </div>
        </div>
        <div className="absolute right-10 top-24 w-[250px] rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur-md">
          <p className="text-xs uppercase tracking-[0.16em] text-white/75">Growth Signal</p>
          <div className="mt-3 h-1.5 rounded-full bg-white/25">
            <div className="h-1.5 w-[72%] rounded-full bg-accent" />
          </div>
          <p className="mt-3 text-sm text-white/90">Pipeline conversion up 27% this month.</p>
        </div>

        {/* Overlay content */}
        <div className="absolute inset-0 z-10 flex flex-col justify-end p-12 xl:p-16">
          <div className="flex items-center gap-2 text-background/80">
            <div className="h-[1px] flex-1 bg-background/30" />
            <span className="text-xs font-semibold tracking-[0.2em] uppercase">
              Powered Workflow
            </span>
            <div className="h-[1px] flex-1 bg-background/30" />
          </div>
          <h2 className="mt-6 text-4xl font-bold leading-tight text-background xl:text-5xl text-balance drop-shadow-md">
            Run campaigns, track growth, and scale client results.
          </h2>

          <div className="mt-10 grid grid-cols-2 gap-x-8 gap-y-5 text-background/90">
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-accent" />
              <span className="text-sm">Campaign Planning</span>
            </div>
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-accent" />
              <span className="text-sm">Performance Tracking</span>
            </div>
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-accent" />
              <span className="text-sm">Lead Intelligence</span>
            </div>
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-accent" />
              <span className="text-sm">Revenue Reporting</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
