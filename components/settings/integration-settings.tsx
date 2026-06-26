"use client";

import { useState, useEffect, useCallback } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Loader2,
  Save,
  Trash2,
  Eye,
  EyeOff,
  CheckCircle2,
  AlertCircle,
  ExternalLink,
  Bot,
  CreditCard,
  Mail,
  Share2,
  BarChart3,
  Zap,
  CalendarDays,
  HardDrive,
  Palette,
} from "lucide-react";
import {
  PROVIDER_CONFIGS,
  type IntegrationCategory,
  type ProviderConfig,
} from "@/lib/db/schema/encrypted-settings";
import { fetchApi } from "@/lib/api/client";

interface IntegrationSettingsProps {
  clientId: string;
}

interface SavedSetting {
  id: string;
  provider: string;
  category: string;
  settingKey: string;
  maskedValue: string;
  isActive: boolean;
  label: string | null;
  lastRotatedAt: string | null;
  lastVerifiedAt: string | null;
  createdAt: string;
  updatedAt: string;
}

const CATEGORY_CONFIG: Record<IntegrationCategory, { label: string; icon: React.ReactNode; description: string }> = {
  ai: { label: "AI Providers", icon: <Bot className="h-4 w-4" />, description: "Configure AI model API keys" },
  payments: { label: "Payments", icon: <CreditCard className="h-4 w-4" />, description: "Payment processing credentials" },
  email: { label: "Email", icon: <Mail className="h-4 w-4" />, description: "Email service configuration" },
  social: { label: "Social Media", icon: <Share2 className="h-4 w-4" />, description: "Social platform OAuth credentials" },
  analytics: { label: "Analytics", icon: <BarChart3 className="h-4 w-4" />, description: "Analytics & tracking setup" },
  automation: { label: "Automation", icon: <Zap className="h-4 w-4" />, description: "Workflow automation integrations" },
  calendar: { label: "Calendar", icon: <CalendarDays className="h-4 w-4" />, description: "Calendar OAuth credentials for availability checks" },
  storage: { label: "Cloud Storage", icon: <HardDrive className="h-4 w-4" />, description: "Cloud storage OAuth credentials" },
  branding: { label: "Branding & SEO", icon: <Palette className="h-4 w-4" />, description: "Brand, ad, SEO, and creative platform integrations" },
};

export function IntegrationSettings({ clientId }: IntegrationSettingsProps) {
  const [savedSettings, setSavedSettings] = useState<SavedSetting[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeCategory, setActiveCategory] = useState<IntegrationCategory>("ai");

  const fetchSettings = useCallback(async () => {
    try {
      const body = await fetchApi<SavedSetting[]>(
        `/api/settings/integrations?clientId=${clientId}`,
        undefined,
        { fallbackMessage: "Failed to fetch settings" },
      );
      setSavedSettings(Array.isArray(body) ? body : []);
    } catch (err) {
      console.error("Failed to fetch settings:", err);
    } finally {
      setLoading(false);
    }
  }, [clientId]);

  useEffect(() => {
    fetchSettings();
  }, [fetchSettings]);

  const categories = Object.keys(CATEGORY_CONFIG) as IntegrationCategory[];

  const getProvidersForCategory = (category: IntegrationCategory) =>
    PROVIDER_CONFIGS.filter((p) => p.category === category);

  const getProviderStatus = (provider: string): "verified" | "saved" | "partial" | "not_configured" => {
    const providerSettings = savedSettings.filter((s) => s.provider === provider && s.isActive);
    const config = PROVIDER_CONFIGS.find((p) => p.provider === provider);
    if (!config) return "not_configured";
    const requiredFields = config.fields.filter((f) => f.required);
    const configuredRequired = requiredFields.filter((f) =>
      providerSettings.some((s) => s.settingKey === f.key),
    );
    if (configuredRequired.length === 0) return "not_configured";
    if (configuredRequired.length < requiredFields.length) return "partial";

    const allRequiredVerified = configuredRequired.every((field) => {
      const matchingSetting = providerSettings.find((s) => s.settingKey === field.key);
      return Boolean(matchingSetting?.lastVerifiedAt);
    });

    return allRequiredVerified ? "verified" : "saved";
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Integrations & API Keys</CardTitle>
          <CardDescription>
            Configure third-party service credentials. All values are encrypted at rest using AES-256-GCM.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Tabs value={activeCategory} onValueChange={(v) => setActiveCategory(v as IntegrationCategory)}>
            <TabsList className="flex flex-wrap h-auto gap-1">
              {categories.map((cat) => {
                const config = CATEGORY_CONFIG[cat];
                const providers = getProvidersForCategory(cat);
                const configuredCount = providers.filter(
                  (p) => getProviderStatus(p.provider) === "verified",
                ).length;
                return (
                  <TabsTrigger key={cat} value={cat} className="gap-1.5 text-xs">
                    {config.icon}
                    {config.label}
                    {configuredCount > 0 && (
                      <Badge variant="secondary" className="ml-1 text-[10px] px-1 py-0">
                        {configuredCount}
                      </Badge>
                    )}
                  </TabsTrigger>
                );
              })}
            </TabsList>

            {categories.map((cat) => (
              <TabsContent key={cat} value={cat} className="mt-6 space-y-4">
                <p className="text-sm text-muted-foreground">{CATEGORY_CONFIG[cat].description}</p>
                {getProvidersForCategory(cat).map((providerConfig) => (
                  <ProviderCard
                    key={providerConfig.provider}
                    config={providerConfig}
                    clientId={clientId}
                    savedSettings={savedSettings.filter((s) => s.provider === providerConfig.provider)}
                    status={getProviderStatus(providerConfig.provider)}
                    onSaved={fetchSettings}
                  />
                ))}
              </TabsContent>
            ))}
          </Tabs>
        </CardContent>
      </Card>
    </div>
  );
}

interface ProviderCardProps {
  config: ProviderConfig;
  clientId: string;
  savedSettings: SavedSetting[];
  status: "verified" | "saved" | "partial" | "not_configured";
  onSaved: () => void | Promise<void>;
}

function ProviderCard({ config, clientId, savedSettings, status, onSaved }: ProviderCardProps) {
  const [isExpanded, setIsExpanded] = useState(false);
  const [fieldValues, setFieldValues] = useState<Record<string, string>>({});
  const [visibleFields, setVisibleFields] = useState<Set<string>>(new Set());
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [verifying, setVerifying] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const statusBadge = {
    verified: {
      label: "Verified",
      variant: "default" as const,
      icon: <CheckCircle2 className="h-3 w-3" />,
    },
    saved: { label: "Saved", variant: "secondary" as const, icon: <AlertCircle className="h-3 w-3" /> },
    partial: { label: "Incomplete", variant: "secondary" as const, icon: <AlertCircle className="h-3 w-3" /> },
    not_configured: { label: "Not configured", variant: "outline" as const, icon: null },
  }[status];

  const lastVerifiedAt = savedSettings.reduce<string | null>((latest, setting) => {
    if (!setting.lastVerifiedAt) {
      return latest;
    }

    if (!latest) {
      return setting.lastVerifiedAt;
    }

    return new Date(setting.lastVerifiedAt) > new Date(latest) ? setting.lastVerifiedAt : latest;
  }, null);

  const getSavedValueForField = (key: string): string | null => {
    const setting = savedSettings.find((s) => s.settingKey === key);
    return setting?.maskedValue ?? null;
  };

  const handleSave = async () => {
    const settingsToSave = Object.entries(fieldValues)
      .filter(([, value]) => value.trim().length > 0)
      .map(([key, value]) => {
        const field = config.fields.find((f) => f.key === key);
        return { key, value: value.trim(), label: field?.label };
      });

    if (settingsToSave.length === 0) {
      setError("Enter at least one value to save.");
      return;
    }

    // Check required fields
    const requiredMissing = config.fields
      .filter((f) => f.required)
      .filter((f) => !settingsToSave.some((s) => s.key === f.key) && !getSavedValueForField(f.key));

    if (requiredMissing.length > 0) {
      setError(`Required fields missing: ${requiredMissing.map((f) => f.label).join(", ")}`);
      return;
    }

    setSaving(true);
    setError(null);
    setSuccess(null);

    try {
      await fetchApi(
        "/api/settings/integrations",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            clientId,
            provider: config.provider,
            category: config.category,
            settings: settingsToSave,
          }),
        },
        { fallbackMessage: "Failed to save settings" },
      );

      setSuccess("Settings saved. Run verification to confirm the connection.");
      setFieldValues({});
      setVisibleFields(new Set());
      await Promise.resolve(onSaved());
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to save settings");
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!confirm(`Remove all ${config.displayName} credentials? This cannot be undone.`)) return;

    setDeleting(true);
    setError(null);
    setSuccess(null);

    try {
      await fetchApi(
        `/api/settings/integrations?clientId=${clientId}&provider=${config.provider}`,
        { method: "DELETE" },
        { fallbackMessage: "Failed to delete settings" },
      );

      setFieldValues({});
      await Promise.resolve(onSaved());
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to delete settings");
    } finally {
      setDeleting(false);
    }
  };

  const handleVerify = async () => {
    setVerifying(true);
    setError(null);
    setSuccess(null);

    try {
      const payload = await fetchApi<Record<string, unknown>>(
        "/api/settings/integrations/verify",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            clientId,
            provider: config.provider,
          }),
        },
        { raw: true, fallbackMessage: "Failed to verify integration" },
      );

      setSuccess(
        (typeof payload.message === "string" && payload.message) ||
          "Connection verified successfully.",
      );
      await Promise.resolve(onSaved());
    } catch (verifyError) {
      setError(verifyError instanceof Error ? verifyError.message : "Failed to verify integration");
    } finally {
      setVerifying(false);
    }
  };

  const toggleFieldVisibility = (key: string) => {
    setVisibleFields((prev) => {
      const next = new Set(prev);
      if (next.has(key)) next.delete(key);
      else next.add(key);
      return next;
    });
  };

  return (
    <Card className="border-border/60">
      <CardHeader
        className="cursor-pointer select-none"
        onClick={() => setIsExpanded(!isExpanded)}
      >
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <CardTitle className="text-base">{config.displayName}</CardTitle>
            <Badge variant={statusBadge.variant} className="gap-1 text-xs">
              {statusBadge.icon}
              {statusBadge.label}
            </Badge>
          </div>
          <span className="text-xs text-muted-foreground">{isExpanded ? "▲" : "▼"}</span>
        </div>
        <CardDescription className="text-xs">{config.description}</CardDescription>
      </CardHeader>

      {isExpanded && (
        <CardContent className="space-y-4 pt-0">
          {error && (
            <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</div>
          )}
          {success && (
            <div className="rounded-md bg-green-50 dark:bg-green-950/30 p-3 text-sm text-green-800 dark:text-green-300">
              {success}
            </div>
          )}

          <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
            <span>
              Status: <span className="font-medium text-foreground">{statusBadge.label}</span>
            </span>
            <span>
              Last rotated:{" "}
              <span className="font-medium text-foreground">
                {savedSettings[0]?.lastRotatedAt ? new Date(savedSettings[0].lastRotatedAt).toLocaleString() : "Never"}
              </span>
            </span>
            <span>
              Last verified:{" "}
              <span className="font-medium text-foreground">
                {lastVerifiedAt ? new Date(lastVerifiedAt).toLocaleString() : "Not verified"}
              </span>
            </span>
          </div>

          {config.fields.map((field) => {
            const savedMasked = getSavedValueForField(field.key);
            const isSecret = field.type === "secret";
            const isVisible = visibleFields.has(field.key);

            return (
              <div key={field.key} className="space-y-1.5">
                <div className="flex items-center gap-2">
                  <Label htmlFor={`${config.provider}-${field.key}`} className="text-sm">
                    {field.label}
                    {field.required && <span className="text-destructive ml-0.5">*</span>}
                  </Label>
                  {savedMasked && (
                    <span className="text-xs text-muted-foreground font-mono">
                      Current: {savedMasked}
                    </span>
                  )}
                </div>
                <div className="flex gap-2">
                  <div className="relative flex-1">
                    <Input
                      id={`${config.provider}-${field.key}`}
                      type={isSecret && !isVisible ? "password" : "text"}
                      placeholder={savedMasked ? "Enter new value to update..." : field.placeholder}
                      value={fieldValues[field.key] || ""}
                      onChange={(e) =>
                        setFieldValues((prev) => ({ ...prev, [field.key]: e.target.value }))
                      }
                      autoComplete="off"
                    />
                  </div>
                  {isSecret && (
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      onClick={() => toggleFieldVisibility(field.key)}
                      title={isVisible ? "Hide" : "Show"}
                    >
                      {isVisible ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </Button>
                  )}
                </div>
              </div>
            );
          })}

          <div className="flex items-center justify-between pt-2">
            <div className="flex gap-2">
              <Button onClick={handleSave} disabled={saving} size="sm">
                {saving ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                Save
              </Button>
              {status !== "not_configured" && (
                <Button onClick={handleVerify} disabled={verifying || saving || deleting} variant="outline" size="sm">
                  {verifying ? (
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  ) : (
                    <CheckCircle2 className="mr-2 h-4 w-4" />
                  )}
                  Verify
                </Button>
              )}
              {status !== "not_configured" && (
                <Button
                  onClick={handleDelete}
                  disabled={deleting || verifying}
                  variant="destructive"
                  size="sm"
                >
                  {deleting ? (
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  ) : (
                    <Trash2 className="mr-2 h-4 w-4" />
                  )}
                  Remove
                </Button>
              )}
            </div>
            {config.docsUrl && (
              <a
                href={config.docsUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="text-xs text-muted-foreground hover:text-foreground flex items-center gap-1"
              >
                Docs <ExternalLink className="h-3 w-3" />
              </a>
            )}
          </div>
        </CardContent>
      )}
    </Card>
  );
}
