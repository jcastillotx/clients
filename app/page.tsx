import { redirect } from "next/navigation";
import { LoginForm } from "@/components/auth/login-form";
import Image from "next/image";
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

      {/* Right side - Hero Image with overlay */}
      <div className="relative hidden lg:block">
        <Image
          src="/images/login-hero.jpg"
          alt="Creative workspace"
          fill
          className="object-cover"
          priority
        />
        {/* Gradient overlay */}
        <div className="absolute inset-0 bg-gradient-to-br from-primary/85 via-primary/75 to-background/10" />

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
            Manage clients and projects with clarity.
          </h2>

          <div className="mt-10 grid grid-cols-2 gap-x-8 gap-y-5 text-background/90">
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-accent" />
              <span className="text-sm">Client Management</span>
            </div>
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-accent" />
              <span className="text-sm">Project Tracking</span>
            </div>
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-accent" />
              <span className="text-sm">Invoice Generation</span>
            </div>
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-accent" />
              <span className="text-sm">Team Collaboration</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
