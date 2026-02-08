import { RegisterForm } from "@/components/auth/register-form";

export const dynamic = "force-dynamic";

export const metadata = {
  title: "Register | KRE8IV",
  description: "Create your account",
};

// Middleware handles redirecting authenticated users from "/register" to "/dashboard".
export default async function RegisterPage() {

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
