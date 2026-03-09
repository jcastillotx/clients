"use client";

import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import { useToast } from "@/hooks/use-toast";
import { Loader2, Save, Send, Eye, EyeOff, CheckCircle2, Info } from "lucide-react";

// ---------------------------------------------------------------------------
// Provider definitions
// ---------------------------------------------------------------------------

type Provider = "resend" | "sendgrid" | "mailgun" | "gmail" | "office365" | "smtp";

interface ProviderMeta {
  label: string;
  description: string;
  docsUrl: string;
  badge?: string;
}

const PROVIDERS: Record<Provider, ProviderMeta> = {
  resend: {
    label: "Resend",
    description: "Modern transactional email API. Recommended for most setups.",
    docsUrl: "https://resend.com/docs/introduction",
    badge: "Recommended",
  },
  sendgrid: {
    label: "SendGrid",
    description: "Twilio SendGrid — high-volume transactional and marketing email.",
    docsUrl: "https://docs.sendgrid.com/for-developers/sending-email/api-getting-started",
  },
  mailgun: {
    label: "Mailgun",
    description: "Mailgun email API. Popular for developer-centric transactional email.",
    docsUrl: "https://documentation.mailgun.com/en/latest/quickstart-sending.html",
  },
  gmail: {
    label: "Gmail (SMTP)",
    description: "Send via your Google Workspace or personal Gmail account using an App Password.",
    docsUrl: "https://support.google.com/accounts/answer/185833",
  },
  office365: {
    label: "Office 365 / Outlook (SMTP)",
    description: "Send via Microsoft 365 or Outlook using SMTP AUTH.",
    docsUrl: "https://learn.microsoft.com/en-us/exchange/clients-and-mobile-in-exchange-online/authenticated-client-smtp-submission",
  },
  smtp: {
    label: "Custom SMTP",
    description: "Any SMTP server — self-hosted, Postmark, Mailjet, etc.",
    docsUrl: "",
  },
};

const SMTP_PRESETS: Partial<Record<Provider, { host: string; port: string; encryption: string }>> = {
  gmail: { host: "smtp.gmail.com", port: "587", encryption: "starttls" },
  office365: { host: "smtp.office365.com", port: "587", encryption: "starttls" },
};

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

interface FormState {
  provider: Provider;
  from_email: string;
  from_name: string;
  // API-key providers
  api_key: string;
  mailgun_domain: string;
  // SMTP
  smtp_host: string;
  smtp_port: string;
  smtp_user: string;
  smtp_password: string;
  smtp_encryption: "none" | "starttls" | "ssl";
}

const DEFAULT_FORM: FormState = {
  provider: "resend",
  from_email: "",
  from_name: "",
  api_key: "",
  mailgun_domain: "",
  smtp_host: "",
  smtp_port: "587",
  smtp_user: "",
  smtp_password: "",
  smtp_encryption: "starttls",
};

export function EmailSettings() {
  const { toast } = useToast();
  const [form, setForm] = useState<FormState>(DEFAULT_FORM);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showApiKey, setShowApiKey] = useState(false);
  const [showSmtpPassword, setShowSmtpPassword] = useState(false);
  const [testEmail, setTestEmail] = useState("");
  const [testing, setTesting] = useState(false);

  // Load current settings
  useEffect(() => {
    fetch("/api/admin/email", { credentials: "same-origin" })
      .then((r) => r.json())
      .then((data: Record<string, string>) => {
        setForm((prev) => ({
          ...prev,
          provider: (data.provider as Provider) ?? "resend",
          from_email: data.from_email ?? "",
          from_name: data.from_name ?? "",
          api_key: data.api_key ?? "",
          mailgun_domain: data.mailgun_domain ?? "",
          smtp_host: data.smtp_host ?? "",
          smtp_port: data.smtp_port ?? "587",
          smtp_user: data.smtp_user ?? "",
          smtp_password: data.smtp_password ?? "",
          smtp_encryption: (data.smtp_encryption as FormState["smtp_encryption"]) ?? "starttls",
        }));
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  function set<K extends keyof FormState>(key: K, value: FormState[K]) {
    setForm((prev) => ({ ...prev, [key]: value }));
  }

  function handleProviderChange(p: Provider) {
    const preset = SMTP_PRESETS[p];
    setForm((prev) => ({
      ...prev,
      provider: p,
      smtp_host: preset?.host ?? prev.smtp_host,
      smtp_port: preset?.port ?? prev.smtp_port,
      smtp_encryption: (preset?.encryption as FormState["smtp_encryption"]) ?? prev.smtp_encryption,
    }));
  }

  async function handleSave() {
    setSaving(true);
    try {
      const res = await fetch("/api/admin/email", {
        method: "PUT",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
      });
      if (res.ok) {
        toast({ title: "Email settings saved" });
      } else {
        toast({ title: "Failed to save", variant: "destructive" });
      }
    } finally {
      setSaving(false);
    }
  }

  async function handleTest() {
    if (!testEmail) return;
    setTesting(true);
    try {
      const res = await fetch("/api/admin/email/test", {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ to: testEmail }),
      });
      const data = await res.json();
      if (res.ok) {
        toast({ title: "Test email sent", description: `Delivered to ${testEmail}` });
      } else {
        toast({ title: "Send failed", description: data.error ?? "Unknown error", variant: "destructive" });
      }
    } finally {
      setTesting(false);
    }
  }

  const isSmtp = ["gmail", "office365", "smtp"].includes(form.provider);
  const isApiKey = ["resend", "sendgrid"].includes(form.provider);
  const isMailgun = form.provider === "mailgun";
  const meta = PROVIDERS[form.provider];

  if (loading) {
    return (
      <div className="flex items-center gap-2 text-sm text-muted-foreground py-8 justify-center">
        <Loader2 className="h-4 w-4 animate-spin" />
        Loading email settings…
      </div>
    );
  }

  return (
    <div className="space-y-6">

      {/* Provider selection */}
      <div className="space-y-2">
        <Label>Email provider</Label>
        <Select value={form.provider} onValueChange={(v) => handleProviderChange(v as Provider)}>
          <SelectTrigger className="w-full">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {(Object.entries(PROVIDERS) as [Provider, ProviderMeta][]).map(([key, p]) => (
              <SelectItem key={key} value={key}>
                <span className="flex items-center gap-2">
                  {p.label}
                  {p.badge && (
                    <Badge variant="secondary" className="text-[10px] ml-1">
                      {p.badge}
                    </Badge>
                  )}
                </span>
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <p className="text-xs text-muted-foreground">{meta.description}</p>
        {meta.docsUrl && (
          <a
            href={meta.docsUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1 text-xs text-primary hover:underline"
          >
            <Info className="h-3 w-3" />
            {meta.label} documentation
          </a>
        )}
      </div>

      <Separator />

      {/* From address — all providers */}
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-1.5">
          <Label htmlFor="from_email">From email address</Label>
          <Input
            id="from_email"
            type="email"
            placeholder="noreply@yourdomain.com"
            value={form.from_email}
            onChange={(e) => set("from_email", e.target.value)}
          />
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="from_name">From name</Label>
          <Input
            id="from_name"
            placeholder="Your Company"
            value={form.from_name}
            onChange={(e) => set("from_name", e.target.value)}
          />
        </div>
      </div>

      {/* API key providers: Resend, SendGrid */}
      {isApiKey && (
        <div className="space-y-1.5">
          <Label htmlFor="api_key">API key</Label>
          <div className="relative">
            <Input
              id="api_key"
              type={showApiKey ? "text" : "password"}
              placeholder={form.provider === "resend" ? "re_..." : "SG...."}
              value={form.api_key}
              onChange={(e) => set("api_key", e.target.value)}
              className="pr-10"
            />
            <button
              type="button"
              onClick={() => setShowApiKey((v) => !v)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
            >
              {showApiKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
          </div>
        </div>
      )}

      {/* Mailgun */}
      {isMailgun && (
        <div className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="api_key">API key</Label>
            <div className="relative">
              <Input
                id="api_key"
                type={showApiKey ? "text" : "password"}
                placeholder="key-..."
                value={form.api_key}
                onChange={(e) => set("api_key", e.target.value)}
                className="pr-10"
              />
              <button
                type="button"
                onClick={() => setShowApiKey((v) => !v)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
              >
                {showApiKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
              </button>
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="mailgun_domain">Mailgun domain</Label>
            <Input
              id="mailgun_domain"
              placeholder="mg.yourdomain.com"
              value={form.mailgun_domain}
              onChange={(e) => set("mailgun_domain", e.target.value)}
            />
            <p className="text-xs text-muted-foreground">
              The sending domain you configured in your Mailgun account.
            </p>
          </div>
        </div>
      )}

      {/* SMTP providers: Gmail, Office 365, custom SMTP */}
      {isSmtp && (
        <div className="space-y-4">
          {form.provider === "gmail" && (
            <div className="rounded-lg border border-amber-500/20 bg-amber-500/5 p-3 text-xs text-amber-700 dark:text-amber-400">
              <strong>Gmail requires an App Password.</strong> Go to your Google Account →
              Security → 2-Step Verification → App passwords. Use the generated password below,
              not your regular Gmail password.{" "}
              <a
                href="https://support.google.com/accounts/answer/185833"
                target="_blank"
                rel="noopener noreferrer"
                className="underline"
              >
                Learn more
              </a>
            </div>
          )}
          {form.provider === "office365" && (
            <div className="rounded-lg border border-blue-500/20 bg-blue-500/5 p-3 text-xs text-blue-700 dark:text-blue-400">
              <strong>SMTP AUTH must be enabled</strong> for your Microsoft 365 account or tenant.
              Go to the Microsoft 365 admin centre → Settings → Org Settings → Modern
              authentication, or enable SMTP AUTH for the specific mailbox in Exchange admin.
            </div>
          )}
          <div className="grid grid-cols-3 gap-4">
            <div className="col-span-2 space-y-1.5">
              <Label htmlFor="smtp_host">SMTP host</Label>
              <Input
                id="smtp_host"
                placeholder="smtp.example.com"
                value={form.smtp_host}
                onChange={(e) => set("smtp_host", e.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="smtp_port">Port</Label>
              <Input
                id="smtp_port"
                placeholder="587"
                value={form.smtp_port}
                onChange={(e) => set("smtp_port", e.target.value)}
              />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="smtp_encryption">Encryption</Label>
            <Select
              value={form.smtp_encryption}
              onValueChange={(v) => set("smtp_encryption", v as FormState["smtp_encryption"])}
            >
              <SelectTrigger id="smtp_encryption">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="starttls">STARTTLS (port 587 — recommended)</SelectItem>
                <SelectItem value="ssl">SSL/TLS (port 465)</SelectItem>
                <SelectItem value="none">None (port 25 — not recommended)</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <Label htmlFor="smtp_user">
                {form.provider === "gmail" ? "Gmail address" : "Username / email"}
              </Label>
              <Input
                id="smtp_user"
                placeholder={form.provider === "gmail" ? "you@gmail.com" : "user@example.com"}
                value={form.smtp_user}
                onChange={(e) => set("smtp_user", e.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="smtp_password">
                {form.provider === "gmail" ? "App password" : "Password"}
              </Label>
              <div className="relative">
                <Input
                  id="smtp_password"
                  type={showSmtpPassword ? "text" : "password"}
                  placeholder="••••••••••••••••"
                  value={form.smtp_password}
                  onChange={(e) => set("smtp_password", e.target.value)}
                  className="pr-10"
                />
                <button
                  type="button"
                  onClick={() => setShowSmtpPassword((v) => !v)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                >
                  {showSmtpPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      <Separator />

      {/* Save */}
      <Button onClick={handleSave} disabled={saving} className="gap-2">
        {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
        Save settings
      </Button>

      {/* Test send */}
      <div className="space-y-3">
        <div>
          <p className="text-sm font-medium">Send a test email</p>
          <p className="text-xs text-muted-foreground">
            Verify your settings are working by sending a test message.
          </p>
        </div>
        <div className="flex gap-2">
          <Input
            type="email"
            placeholder="test@example.com"
            value={testEmail}
            onChange={(e) => setTestEmail(e.target.value)}
            className="max-w-xs"
          />
          <Button
            variant="outline"
            onClick={handleTest}
            disabled={testing || !testEmail}
            className="gap-2"
          >
            {testing ? (
              <Loader2 className="h-4 w-4 animate-spin" />
            ) : (
              <Send className="h-4 w-4" />
            )}
            Send test
          </Button>
        </div>
      </div>
    </div>
  );
}
