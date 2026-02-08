import { LoginForm } from "@/components/auth/login-form";
import { headers } from "next/headers";
import { getPortalBranding } from "@/lib/branding/get-branding";

export const dynamic = "force-dynamic";

export const metadata = {
  title: "Login | KRE8IV",
  description: "Sign in to your account",
};

// Middleware handles redirecting authenticated users from "/login" to "/dashboard".
export default async function LoginPage() {
  const headerList = await headers();
  const host = headerList.get("x-forwarded-host") || headerList.get("host") || undefined;
  const branding = await getPortalBranding(host);

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/40">
      <div className="w-full max-w-md space-y-8 px-4">
        <div className="text-center">
          <h1 className="text-3xl font-bold tracking-tight">Welcome back</h1>
          <p className="mt-2 text-sm text-muted-foreground">Sign in to your account to continue</p>
        </div>

        <LoginForm logoUrl={branding.logoUrl} />
      </div>
    </div>
  );
}
