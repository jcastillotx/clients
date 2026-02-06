import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { ResetPasswordForm } from "@/components/auth/reset-password-form";

export const metadata = {
  title: "Reset Password | KRE8IV",
  description: "Set your new password",
};

export default async function ResetPasswordPage() {
  const supabase = createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  // If no user session from reset link, redirect to forgot password
  if (!user) {
    redirect("/forgot-password");
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/40">
      <div className="w-full max-w-md space-y-8 px-4">
        <div className="text-center">
          <h1 className="text-3xl font-bold tracking-tight">Set new password</h1>
          <p className="mt-2 text-sm text-muted-foreground">Enter your new password below</p>
        </div>

        <ResetPasswordForm />
      </div>
    </div>
  );
}
