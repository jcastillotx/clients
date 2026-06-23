"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { z } from "zod";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { createClient } from "@/lib/supabase/client";
import { getAuthConfirmUrl } from "@/lib/supabase/redirect-url";
import { Loader2, User, Lock, Mail } from "lucide-react";

const emailSchema = z.object({
  email: z.string().email("Invalid email address"),
});

const loginSchema = emailSchema.extend({
  password: z.string().min(8, "Password must be at least 8 characters"),
});

type LoginFormInput = z.infer<typeof loginSchema>;
type AuthMode = "password" | "magic_link";

interface LoginFormProps {
  logoUrl?: string | null;
}

export function LoginForm({ logoUrl }: LoginFormProps) {
  const router = useRouter();
  const [authMode, setAuthMode] = useState<AuthMode>("password");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [authError, setAuthError] = useState<string | null>(null);
  const [magicLinkSent, setMagicLinkSent] = useState(false);

  const {
    register,
    handleSubmit,
    getValues,
    setError,
    clearErrors,
    formState: { errors },
  } = useForm<LoginFormInput>();

  const onPasswordSubmit = async (data: LoginFormInput) => {
    const parsed = loginSchema.safeParse(data);
    if (!parsed.success) {
      for (const issue of parsed.error.issues) {
        const field = issue.path[0];
        if (field === "email" || field === "password") {
          setError(field, { type: "manual", message: issue.message });
        }
      }
      return;
    }

    setIsSubmitting(true);
    setAuthError(null);
    setMagicLinkSent(false);
    clearErrors();

    try {
      const supabase = createClient();

      const { error: signInError } = await supabase.auth.signInWithPassword({
        email: parsed.data.email,
        password: parsed.data.password,
      });

      if (signInError) {
        throw signInError;
      }

      router.push("/dashboard");
      router.refresh();
    } catch (err) {
      setAuthError(err instanceof Error ? err.message : "Failed to sign in");
    } finally {
      setIsSubmitting(false);
    }
  };

  const onMagicLinkSubmit = async () => {
    const data = { email: getValues("email") };
    const parsed = emailSchema.safeParse(data);
    if (!parsed.success) {
      setError("email", {
        type: "manual",
        message: parsed.error.issues[0]?.message || "Email is required",
      });
      return;
    }

    setIsSubmitting(true);
    setAuthError(null);
    setMagicLinkSent(false);
    clearErrors();

    try {
      const supabase = createClient();
      const { error } = await supabase.auth.signInWithOtp({
        email: parsed.data.email,
        options: {
          emailRedirectTo: getAuthConfirmUrl("/dashboard"),
        },
      });

      if (error) {
        throw error;
      }

      setMagicLinkSent(true);
    } catch (err) {
      setAuthError(
        err instanceof Error ? err.message : "Failed to send magic link",
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="flex flex-col gap-8 rounded-2xl border border-border/60 bg-card/80 p-8 shadow-xl backdrop-blur">
      {/* Brand */}
      <div className="flex items-center gap-3">
        {logoUrl ? (
          <img
            src={logoUrl}
            alt="Company logo"
            className="h-11 w-11 rounded-xl border border-border/60 bg-background/80 object-cover p-1"
          />
        ) : (
          <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-lg font-semibold text-primary">
            K
          </div>
        )}
        <div>
          <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
            Client Portal
          </p>
          <h2 className="text-2xl font-bold tracking-tight text-foreground">
            KRE8IV
          </h2>
        </div>
      </div>

      {/* Heading */}
      <div className="flex flex-col gap-2">
        <h1 className="text-2xl font-bold text-foreground">Sign in</h1>
        <p className="text-sm text-muted-foreground">
          {"Don't have an account? "}
          <Link
            href="/register"
            className="font-medium text-primary underline-offset-4 hover:underline"
          >
            Sign up
          </Link>
        </p>
      </div>

      <div className="grid grid-cols-2 rounded-lg border border-border/70 bg-background/70 p-1">
        <Button
          type="button"
          variant={authMode === "password" ? "default" : "ghost"}
          className="h-9 rounded-md"
          onClick={() => {
            setAuthMode("password");
            setAuthError(null);
            setMagicLinkSent(false);
          }}
        >
          Email + Password
        </Button>
        <Button
          type="button"
          variant={authMode === "magic_link" ? "default" : "ghost"}
          className="h-9 rounded-md"
          onClick={() => {
            setAuthMode("magic_link");
            setAuthError(null);
            setMagicLinkSent(false);
          }}
        >
          Magic Link
        </Button>
      </div>

      {/* Form */}
      <form
        onSubmit={
          authMode === "password"
            ? handleSubmit(onPasswordSubmit)
            : (e) => e.preventDefault()
        }
        className="flex flex-col gap-5"
      >
        {authError && (
          <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
            {authError}
          </div>
        )}
        {magicLinkSent && (
          <div className="rounded-lg border border-success/30 bg-success/10 p-4 text-sm text-success">
            Magic link sent. Check your email to continue.
          </div>
        )}

        {/* Email field */}
        <div className="flex flex-col gap-2">
          <Label
            htmlFor="email"
            className="text-sm font-medium text-foreground"
          >
            Email
          </Label>
          <div className="relative">
            <User className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              id="email"
              type="email"
              placeholder="Enter email to get started"
              className="h-12 rounded-lg border-border/70 bg-background/70 pl-10 shadow-sm transition focus:ring-2 focus:ring-primary/50"
              {...register("email")}
            />
          </div>
          {errors.email && (
            <p className="text-sm text-destructive">{errors.email.message}</p>
          )}
        </div>

        {authMode === "password" ? (
          <>
            {/* Password field */}
            <div className="flex flex-col gap-2">
              <div className="flex items-center justify-between">
                <Label
                  htmlFor="password"
                  className="text-sm font-medium text-foreground"
                >
                  Password
                </Label>
                <Link
                  href="/forgot-password"
                  className="text-sm font-medium text-primary hover:underline"
                >
                  Forgot Password
                </Link>
              </div>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  id="password"
                  type="password"
                  autoComplete="current-password"
                  placeholder="Enter your password"
                  className="h-12 rounded-lg border-border/70 bg-background/70 pl-10 shadow-sm transition focus:ring-2 focus:ring-primary/50"
                  {...register("password")}
                />
              </div>
              {errors.password && (
                <p className="text-sm text-destructive">
                  {errors.password.message}
                </p>
              )}
            </div>

            {/* Sign In button */}
            <Button
              type="submit"
              className="h-12 w-full rounded-lg text-base font-semibold shadow-lg shadow-primary/10 transition hover:-translate-y-[1px] hover:shadow-primary/20"
              disabled={isSubmitting}
            >
              {isSubmitting && (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              )}
              Sign In
            </Button>
          </>
        ) : (
          <div className="flex flex-col gap-4">
            <p className="text-sm text-muted-foreground">
              We'll email you a secure login link.
            </p>
            <Button
              type="button"
              className="h-12 w-full rounded-lg text-base font-semibold shadow-lg shadow-primary/10 transition hover:-translate-y-[1px] hover:shadow-primary/20"
              disabled={isSubmitting}
              onClick={onMagicLinkSubmit}
            >
              {isSubmitting ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : (
                <Mail className="mr-2 h-4 w-4" />
              )}
              Send Magic Link
            </Button>
          </div>
        )}
      </form>
    </div>
  );
}
