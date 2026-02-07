import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { RegisterForm } from "@/components/auth/register-form";

export const dynamic = "force-dynamic";

export const metadata = {
  title: "Register | KRE8IV",
  description: "Create your account",
};

export default async function RegisterPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  // If user is already logged in, redirect to dashboard
  if (user) {
    redirect("/dashboard");
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/40">
      <div className="w-full max-w-md space-y-8 px-4">
        <div className="text-center">
          <h1 className="text-3xl font-bold tracking-tight">Create an account</h1>
          <p className="mt-2 text-sm text-muted-foreground">Get started with your free account</p>
        </div>

        <RegisterForm />
      </div>
    </div>
  );
}
