"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { PasswordInput } from "@/components/ui/password-input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { createClient } from "@/lib/supabase/client";
import { AlertTriangle, Loader2, Shield, Smartphone, Trash2 } from "lucide-react";

/** Human label for `next` when middleware sends admins here for MFA (AAL2). */
function friendlyMfaRedirectTarget(nextRaw: string): string | null {
  const trimmed = nextRaw.trim();
  if (!trimmed.startsWith("/") || trimmed.startsWith("//")) return null;
  const path = (trimmed.split("?")[0] ?? trimmed).replace(/\/$/, "") || "/";
  const known: Record<string, string> = {
    "/admin/maintenance-plans": "Maintenance plan templates",
    "/admin/service-templates": "Service templates",
    "/admin/template-forms": "Form templates",
    "/admin/email": "Email provider",
  };
  if (known[path]) return known[path];
  if (path.startsWith("/admin/")) {
    const slug = path.slice("/admin/".length);
    if (!slug) return "Admin";
    return slug
      .split("/")
      .map((seg) =>
        seg
          .split("-")
          .filter(Boolean)
          .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
          .join(" "),
      )
      .join(" › ");
  }
  return null;
}

const passwordSchema = z
  .object({
    currentPassword: z.string().min(8, "Password must be at least 8 characters"),
    newPassword: z.string().min(8, "Password must be at least 8 characters"),
    confirmPassword: z.string().min(8, "Password must be at least 8 characters"),
  })
  .refine((data) => data.newPassword === data.confirmPassword, {
    message: "Passwords don't match",
    path: ["confirmPassword"],
  });

type PasswordFormInput = z.infer<typeof passwordSchema>;

type MfaFactor = {
  id: string;
  factor_type?: string;
  friendly_name?: string | null;
  status?: string;
};

type EnrolledFactor = MfaFactor & {
  totp?: {
    qr_code?: string;
    secret?: string;
    uri?: string;
  };
};

interface SecuritySettingsProps {
  user?: {
    id: string;
    email?: string;
  };
}

export function SecuritySettings({ user }: SecuritySettingsProps = {}) {
  const supabase = useMemo(() => createClient(), []);
  const router = useRouter();
  const searchParams = useSearchParams();
  const mfaRequired = searchParams.get("mfa_required") === "1";
  const redirectAfterMfa = searchParams.get("next") || "/dashboard";
  const friendlyMfaTarget = useMemo(
    () => friendlyMfaRedirectTarget(redirectAfterMfa),
    [redirectAfterMfa],
  );
  // Also support legacy ?tab=security&mfa_required=1 redirects

  // Password change state
  const [pwSuccess, setPwSuccess] = useState(false);
  const [pwError, setPwError] = useState<string | null>(null);
  const [isSubmittingPw, setIsSubmittingPw] = useState(false);

  const {
    register: registerPw,
    handleSubmit: handlePwSubmit,
    setValue: setPwValue,
    formState: { errors: pwErrors },
    reset: resetPw,
  } = useForm<PasswordFormInput>({ resolver: zodResolver(passwordSchema) });

  const onPasswordSubmit = async (data: PasswordFormInput) => {
    setIsSubmittingPw(true);
    setPwError(null);
    setPwSuccess(false);
    try {
      const { error: signInError } = await supabase.auth.signInWithPassword({
        email: user?.email ?? "",
        password: data.currentPassword,
      });
      if (signInError) throw new Error("Current password is incorrect");
      const { error: updateError } = await supabase.auth.updateUser({ password: data.newPassword });
      if (updateError) throw updateError;
      setPwSuccess(true);
      resetPw();
    } catch (err) {
      setPwError(err instanceof Error ? err.message : "Failed to update password");
    } finally {
      setIsSubmittingPw(false);
    }
  };
  const [isLoading, setIsLoading] = useState(true);
  const [isEnrolling, setIsEnrolling] = useState(false);
  const [isVerifying, setIsVerifying] = useState(false);
  const [isRemoving, setIsRemoving] = useState<string | null>(null);
  const [factors, setFactors] = useState<MfaFactor[]>([]);
  const [aal, setAal] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [verificationCode, setVerificationCode] = useState("");
  const [pendingFactor, setPendingFactor] = useState<EnrolledFactor | null>(null);

  const loadMfaState = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      const [{ data: factorData, error: factorsError }, { data: aalData, error: aalError }] = await Promise.all([
        supabase.auth.mfa.listFactors(),
        supabase.auth.mfa.getAuthenticatorAssuranceLevel(),
      ]);

      if (factorsError) {
        throw factorsError;
      }

      if (aalError) {
        throw aalError;
      }

      const allFactors = [
        ...(factorData?.all ?? []),
      ].filter((factor, index, collection) => collection.findIndex((item) => item.id === factor.id) === index);

      setFactors(allFactors as MfaFactor[]);
      setAal(aalData?.currentLevel ?? null);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Failed to load two-factor settings");
    } finally {
      setIsLoading(false);
    }
  }, [supabase]);

  useEffect(() => {
    void loadMfaState();
  }, [loadMfaState]);

  useEffect(() => {
    if (mfaRequired && aal === "aal2") {
      router.replace(redirectAfterMfa.startsWith("/") ? redirectAfterMfa : "/dashboard");
    }
  }, [aal, mfaRequired, redirectAfterMfa, router]);

  const handleEnroll = async () => {
    setIsEnrolling(true);
    setError(null);
    setSuccess(null);

    try {
      const { data, error: enrollError } = await supabase.auth.mfa.enroll({
        factorType: "totp",
        friendlyName: "Authenticator App",
      });

      if (enrollError) {
        throw enrollError;
      }

      setPendingFactor(data as EnrolledFactor);
      setVerificationCode("");
    } catch (enrollError) {
      setError(enrollError instanceof Error ? enrollError.message : "Failed to start two-factor enrollment");
    } finally {
      setIsEnrolling(false);
    }
  };

  const handleVerify = async () => {
    if (!pendingFactor?.id || !verificationCode.trim()) {
      setError("Enter the 6-digit code from your authenticator app.");
      return;
    }

    setIsVerifying(true);
    setError(null);
    setSuccess(null);

    try {
      const { data: challengeData, error: challengeError } = await supabase.auth.mfa.challenge({
        factorId: pendingFactor.id,
      });

      if (challengeError) {
        throw challengeError;
      }

      const { error: verifyError } = await supabase.auth.mfa.verify({
        factorId: pendingFactor.id,
        challengeId: challengeData.id,
        code: verificationCode.trim(),
      });

      if (verifyError) {
        throw verifyError;
      }

      setPendingFactor(null);
      setVerificationCode("");
      setSuccess("Two-factor authentication has been enabled.");
      await loadMfaState();
    } catch (verifyError) {
      setError(verifyError instanceof Error ? verifyError.message : "Failed to verify authenticator code");
    } finally {
      setIsVerifying(false);
    }
  };

  const handleRemove = async (factorId: string) => {
    setIsRemoving(factorId);
    setError(null);
    setSuccess(null);

    try {
      const { error: unenrollError } = await supabase.auth.mfa.unenroll({ factorId });
      if (unenrollError) {
        throw unenrollError;
      }

      setSuccess("Two-factor authentication method removed.");
      await loadMfaState();
    } catch (removeError) {
      setError(removeError instanceof Error ? removeError.message : "Failed to remove two-factor method");
    } finally {
      setIsRemoving(null);
    }
  };

  const qrMarkup = pendingFactor?.totp?.qr_code?.trim() ?? "";

  return (
    <div className="space-y-6">
      {/* MFA required banner — shown when middleware redirected an admin here */}
      {mfaRequired && (
        <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
          <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
          <div>
            <p className="font-semibold">Multi-factor authentication required</p>
            <p className="mt-0.5 text-amber-800 dark:text-amber-300">
              Admin-only URLs (including from Settings) require an authenticator step before they load.{" "}
              {friendlyMfaTarget ? (
                <>
                  You were opening <strong>{friendlyMfaTarget}</strong>. Set up an authenticator app below; once
                  this session reaches AAL2, you will be sent there automatically.
                </>
              ) : (
                <>Set up an authenticator app below to continue.</>
              )}
            </p>
          </div>
        </div>
      )}

      {/* Change Password */}
      <Card>
        <CardHeader>
          <CardTitle>Change Password</CardTitle>
          <CardDescription>Update your password to keep your account secure</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handlePwSubmit(onPasswordSubmit)} className="space-y-4">
            {pwError && <div className="rounded-md bg-destructive/10 p-4 text-sm text-destructive">{pwError}</div>}
            {pwSuccess && <div className="rounded-md bg-green-50 p-4 text-sm text-green-800">Password updated successfully!</div>}
            <div className="space-y-2">
              <Label htmlFor="currentPassword">Current Password</Label>
              <Input id="currentPassword" type="password" {...registerPw("currentPassword")} />
              {pwErrors.currentPassword && <p className="text-sm text-destructive">{pwErrors.currentPassword.message}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="newPassword">New Password</Label>
              <PasswordInput
                id="newPassword"
                {...registerPw("newPassword")}
                onGeneratePassword={(pw) => {
                  setPwValue("newPassword", pw, { shouldValidate: true, shouldDirty: true });
                  setPwValue("confirmPassword", pw, { shouldValidate: true, shouldDirty: true });
                }}
              />
              {pwErrors.newPassword && <p className="text-sm text-destructive">{pwErrors.newPassword.message}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="confirmPassword">Confirm New Password</Label>
              <Input id="confirmPassword" type="password" {...registerPw("confirmPassword")} />
              {pwErrors.confirmPassword && <p className="text-sm text-destructive">{pwErrors.confirmPassword.message}</p>}
            </div>
            <Button type="submit" disabled={isSubmittingPw}>
              {isSubmittingPw && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Update Password
            </Button>
          </form>
        </CardContent>
      </Card>

      {/* Two-Factor Authentication */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Shield className="h-5 w-5" />
            Two-Factor Authentication
          </CardTitle>
          <CardDescription>
            Protect your account with an authenticator app and verification codes during sign-in.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {error ? <div className="rounded-md bg-destructive/10 p-4 text-sm text-destructive">{error}</div> : null}
          {success ? <div className="rounded-md bg-green-50 p-4 text-sm text-green-800">{success}</div> : null}

          <div className="flex flex-wrap items-center gap-3">
            <Badge variant={factors.length > 0 ? "default" : "secondary"}>
              {factors.length > 0 ? "2FA enabled" : "2FA disabled"}
            </Badge>
            <Badge variant="outline">AAL: {aal ?? "unknown"}</Badge>
          </div>

          {isLoading ? (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
              Loading security settings...
            </div>
          ) : (
            <>
              <div className="space-y-3">
                <h3 className="text-sm font-medium">Configured factors</h3>
                {factors.length === 0 ? (
                  <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    No authenticator app configured yet.
                  </div>
                ) : (
                  factors.map((factor) => (
                    <div key={factor.id} className="flex items-center justify-between rounded-lg border p-4">
                      <div>
                        <div className="flex items-center gap-2 font-medium">
                          <Smartphone className="h-4 w-4" />
                          {factor.friendly_name || "Authenticator App"}
                        </div>
                        <p className="text-sm text-muted-foreground">
                          Type: {factor.factor_type || "totp"} · Status: {factor.status || "verified"}
                        </p>
                      </div>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => void handleRemove(factor.id)}
                        disabled={isRemoving === factor.id}
                      >
                        {isRemoving === factor.id ? (
                          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        ) : (
                          <Trash2 className="mr-2 h-4 w-4" />
                        )}
                        Remove
                      </Button>
                    </div>
                  ))
                )}
              </div>

              {pendingFactor ? (
                <div className="space-y-4 rounded-lg border p-4">
                  <div>
                    <h3 className="font-medium">Finish setup</h3>
                    <p className="text-sm text-muted-foreground">
                      Scan the QR code with your authenticator app, then enter the 6-digit code to verify.
                    </p>
                  </div>

                  {qrMarkup ? (
                    <div
                      className="max-w-[220px] rounded-lg border bg-white p-3"
                      dangerouslySetInnerHTML={{ __html: qrMarkup }}
                    />
                  ) : null}

                  {pendingFactor.totp?.secret ? (
                    <div className="space-y-1">
                      <p className="text-sm font-medium">Manual setup code</p>
                      <p className="rounded bg-muted px-3 py-2 font-mono text-sm break-all">{pendingFactor.totp.secret}</p>
                    </div>
                  ) : null}

                  <div className="space-y-2">
                    <Label htmlFor="totp-code">Verification code</Label>
                    <Input
                      id="totp-code"
                      inputMode="numeric"
                      autoComplete="one-time-code"
                      placeholder="123456"
                      value={verificationCode}
                      onChange={(event) => setVerificationCode(event.target.value.replace(/\D/g, "").slice(0, 6))}
                    />
                  </div>

                  <div className="flex gap-3">
                    <Button onClick={() => void handleVerify()} disabled={isVerifying || verificationCode.length !== 6}>
                      {isVerifying ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                      Verify & Enable
                    </Button>
                    <Button
                      variant="outline"
                      onClick={() => {
                        setPendingFactor(null);
                        setVerificationCode("");
                        setError(null);
                      }}
                      disabled={isVerifying}
                    >
                      Cancel
                    </Button>
                  </div>
                </div>
              ) : (
                <Button onClick={() => void handleEnroll()} disabled={isEnrolling}>
                  {isEnrolling ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Shield className="mr-2 h-4 w-4" />}
                  Enable Authenticator App
                </Button>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
