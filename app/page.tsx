import { createClient } from "@/lib/supabase/server";
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
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (user) {
    redirect("/dashboard");
  }

  return (
    <div className="grid h-screen lg:grid-cols-2">
      {/* Left side - Login Form */}
      <div className="flex flex-col justify-center overflow-y-auto bg-background px-6 py-12 lg:px-16 xl:px-24">
        <div className="mx-auto w-full max-w-md">
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
        {/* Dark overlay */}
        <div className="absolute inset-0 bg-foreground/70" />

        {/* Overlay content */}
        <div className="absolute inset-0 z-10 flex flex-col justify-end p-12 xl:p-16">
          <h2 className="text-3xl font-bold leading-tight text-background xl:text-4xl text-balance">
            Manage your clients and projects with ease.
          </h2>

          <div className="mt-8 grid grid-cols-2 gap-x-8 gap-y-4">
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-success" />
              <span className="text-sm text-background/90">
                Client Management
              </span>
            </div>
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-success" />
              <span className="text-sm text-background/90">
                Project Tracking
              </span>
            </div>
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-success" />
              <span className="text-sm text-background/90">
                Invoice Generation
              </span>
            </div>
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-success" />
              <span className="text-sm text-background/90">
                Team Collaboration
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
