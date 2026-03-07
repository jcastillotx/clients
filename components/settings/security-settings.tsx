"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { createClient } from "@/lib/supabase/client";
import { Loader2, Shield, Smartphone, Trash2 } from "lucide-react";

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

export function SecuritySettings() {
  const supabase = useMemo(() => createClient(), []);
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
