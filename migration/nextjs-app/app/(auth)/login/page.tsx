import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { ShieldCheck, Database, Globe, LineChart } from "lucide-react";
import { LoginForm } from "@/components/auth/login-form";

export const metadata = {
  title: "Login | KRE8IV",
  description: "Sign in to your account",
};

const features = [
  {
    title: "Custom CMS",
    icon: Database,
  },
  {
    title: "Enterprise Security",
    icon: ShieldCheck,
  },
  {
    title: "Custom Domains",
    icon: Globe,
  },
  {
    title: "Real Time Analytics",
    icon: LineChart,
  },
];

export default async function LoginPage() {
  const supabase = createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  // If user is already logged in, redirect to dashboard
  if (user) {
    redirect("/dashboard");
  }

  return (
    <div className="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(59,130,246,0.18),transparent_45%),radial-gradient(circle_at_85%_15%,rgba(14,165,233,0.15),transparent_45%)]" />
      <div className="absolute inset-y-0 left-0 hidden w-1/2 bg-[linear-gradient(130deg,rgba(15,23,42,0.45),rgba(15,23,42,0.92)),url('https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1600&q=80')] bg-cover bg-center lg:block" />
      <div className="absolute inset-y-0 left-0 hidden w-1/2 bg-slate-950/30 backdrop-blur-[1px] lg:block" />

      <div className="relative mx-auto grid min-h-screen w-full max-w-7xl grid-cols-1 lg:grid-cols-2">
        <section className="flex flex-col justify-center px-6 py-14 sm:px-10 lg:px-14">
          <div className="max-w-xl space-y-8">
            <div className="space-y-4">
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-sky-300">Kre8ivTech</p>
              <h1 className="text-4xl font-bold tracking-tight text-white sm:text-5xl">Launch Modern Website with Kre8ivTech</h1>
              <p className="max-w-lg text-sm text-slate-300 sm:text-base">
                Build, manage, and scale your digital presence from one reliable platform.
              </p>
            </div>

            <ul className="space-y-4">
              {features.map(({ title, icon: Icon }) => (
                <li key={title} className="flex items-center gap-3 rounded-lg border border-slate-800/80 bg-slate-900/60 px-4 py-3 backdrop-blur-sm">
                  <span className="flex h-8 w-8 items-center justify-center rounded-md border border-sky-500/40 bg-sky-500/10 text-sky-300">
                    <Icon className="h-4 w-4" />
                  </span>
                  <span className="text-sm font-medium text-slate-100 sm:text-base">{title}</span>
                </li>
              ))}
            </ul>
          </div>
        </section>

        <section className="flex items-center justify-center px-6 py-10 sm:px-10 lg:px-14">
          <div className="w-full max-w-md rounded-2xl border border-slate-800/80 bg-slate-950/80 p-1 shadow-2xl backdrop-blur-md">
            <div className="rounded-xl bg-slate-950/80 p-1">
              <div className="px-4 pb-2 pt-4 text-center">
                <h2 className="text-3xl font-bold tracking-tight text-white">Welcome back</h2>
                <p className="mt-2 text-sm text-slate-400">Sign in to your dashboard</p>
              </div>
              <LoginForm />
            </div>
          </div>
        </section>
      </div>
    </div>
  );
}
