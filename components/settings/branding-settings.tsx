"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Loader2, Upload } from "lucide-react";
import type { BrandingSettings as BrandingSettingsType } from "@/lib/branding/get-branding";

interface BrandingSettingsProps {
  clientId: string | null;
  isPortalBrandingScope?: boolean;
  initialLogoUrl?: string | null;
  initialDomain?: string | null;
  initialPrimaryColor?: string | null;
  initialSecondaryColor?: string | null;
  initialSettings?: Partial<BrandingSettingsType>;
}

const DEFAULT_SETTINGS: BrandingSettingsType = {
  sidebarBgColor: "#1E1B4B",
  sidebarBgColorDark: "#0F0C2B",
  sidebarTextColor: "#E8E5FF",
  sidebarTextColorDark: "#E8E5FF",
  sidebarWidth: "standard",
  brandText: "",
  siteTitle: "Client Portal",
  loginImageUrl: null,
  fontSize: "md",
  primaryColor: "#7C3AED",
  primaryColorDark: "#A78BFA",
  paddingSize: "standard",
};

type SidebarWidth = "narrow" | "standard" | "wide";
type FontSize = "sm" | "md" | "lg";
type PaddingSize = "compact" | "standard" | "spacious";

function RadioGroup<T extends string>({
  label,
  options,
  value,
  onChange,
}: {
  label: string;
  options: { value: T; label: string }[];
  value: T;
  onChange: (v: T) => void;
}) {
  return (
    <div className="space-y-2">
      <Label>{label}</Label>
      <div className="flex gap-2 flex-wrap">
        {options.map((opt) => (
          <Button
            key={opt.value}
            type="button"
            variant={value === opt.value ? "default" : "outline"}
            size="sm"
            onClick={() => onChange(opt.value)}
          >
            {opt.label}
          </Button>
        ))}
      </div>
    </div>
  );
}

function ColorField({ label, id, value, onChange }: { label: string; id: string; value: string; onChange: (v: string) => void }) {
  return (
    <div className="space-y-1">
      <Label htmlFor={id}>{label}</Label>
      <div className="flex items-center gap-3">
        <input
          type="color"
          id={id}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          className="h-10 w-20 cursor-pointer rounded border p-1"
        />
        <Input
          value={value}
          onChange={(e) => onChange(e.target.value)}
          className="w-32 font-mono text-sm"
          maxLength={7}
        />
      </div>
    </div>
  );
}

export function BrandingSettings({
  clientId,
  isPortalBrandingScope = false,
  initialLogoUrl,
  initialDomain,
  initialPrimaryColor,
  initialSecondaryColor,
  initialSettings,
}: BrandingSettingsProps) {
  const router = useRouter();

  const merged: BrandingSettingsType = { ...DEFAULT_SETTINGS, ...initialSettings };

  const [logoUrl, setLogoUrl] = useState(initialLogoUrl || "");
  const [domain, setDomain] = useState(initialDomain || "");
  const [primaryColor, setPrimaryColor] = useState(initialPrimaryColor || merged.primaryColor);
  const [secondaryColor, setSecondaryColor] = useState(initialSecondaryColor || "#ffffff");

  // Extended settings state
  const [sidebarBgColor, setSidebarBgColor] = useState(merged.sidebarBgColor);
  const [sidebarBgColorDark, setSidebarBgColorDark] = useState(merged.sidebarBgColorDark);
  const [sidebarTextColor, setSidebarTextColor] = useState(merged.sidebarTextColor);
  const [sidebarTextColorDark, setSidebarTextColorDark] = useState(merged.sidebarTextColorDark);
  const [sidebarWidth, setSidebarWidth] = useState<SidebarWidth>(merged.sidebarWidth);
  const [brandText, setBrandText] = useState(merged.brandText);
  const [siteTitle, setSiteTitle] = useState(merged.siteTitle);
  const [loginImageUrl, setLoginImageUrl] = useState(merged.loginImageUrl || "");
  const [fontSize, setFontSize] = useState<FontSize>(merged.fontSize);
  const [primaryColorDark, setPrimaryColorDark] = useState(merged.primaryColorDark);
  const [paddingSize, setPaddingSize] = useState<PaddingSize>(merged.paddingSize);

  const [isSaving, setIsSaving] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);

  const buildSettings = (): BrandingSettingsType => ({
    sidebarBgColor,
    sidebarBgColorDark,
    sidebarTextColor,
    sidebarTextColorDark,
    sidebarWidth,
    brandText,
    siteTitle,
    loginImageUrl: loginImageUrl || null,
    fontSize,
    primaryColor,
    primaryColorDark,
    paddingSize,
  });

  const handleSave = async () => {
    if (!clientId) {
      setError("No branding target found. Contact support.");
      return;
    }

    setIsSaving(true);
    setError(null);
    setSuccess(false);

    try {
      const res = await fetch("/api/branding", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          clientId,
          logoUrl: logoUrl || null,
          domain: domain || null,
          primaryColor: primaryColor || null,
          secondaryColor: secondaryColor || null,
          settings: buildSettings(),
        }),
      });
      const payload: { error?: string } = await res.json();
      if (!res.ok) throw new Error(payload.error || "Failed to save branding");

      setSuccess(true);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to save branding");
    } finally {
      setIsSaving(false);
    }
  };

  const handleUploadLogo = async () => {
    if (!selectedFile) {
      setError("Select a logo file first.");
      return;
    }

    setIsUploading(true);
    setError(null);
    setSuccess(false);

    try {
      const formData = new FormData();
      formData.append("file", selectedFile);

      const res = await fetch("/api/branding/logo", { method: "POST", body: formData });
      const payload: { logoUrl?: string; error?: string } = await res.json();
      if (!res.ok || !payload.logoUrl) throw new Error(payload.error || "Failed to upload logo");

      setLogoUrl(payload.logoUrl);
      setSelectedFile(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to upload logo");
    } finally {
      setIsUploading(false);
    }
  };

  return (
    <div className="space-y-6">
      {error && <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</div>}
      {success && <div className="rounded-md bg-green-50 p-3 text-sm text-green-800">Branding saved successfully.</div>}

      {isPortalBrandingScope && (
        <div className="rounded-md border border-primary/30 bg-primary/5 p-3 text-xs text-primary">
          You are editing the global branding used by the main portal.
        </div>
      )}

      {/* Section: Logo */}
      <Card>
        <CardHeader>
          <CardTitle>Logo &amp; Brand Identity</CardTitle>
          <CardDescription>Upload your company logo or set a brand text fallback.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="logo-file">Upload Logo</Label>
            <Input
              id="logo-file"
              type="file"
              accept=".png,.jpg,.jpeg,.svg,.webp"
              onChange={(e) => setSelectedFile(e.target.files?.[0] || null)}
            />
            <div className="flex items-center gap-3">
              <Button type="button" variant="outline" onClick={handleUploadLogo} disabled={isUploading || !selectedFile}>
                {isUploading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Upload className="mr-2 h-4 w-4" />}
                Upload Logo
              </Button>
              {selectedFile && <p className="text-xs text-muted-foreground">{selectedFile.name}</p>}
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="logo-url">Logo URL (direct link)</Label>
            <Input
              id="logo-url"
              placeholder="https://your-cdn.com/logo.png"
              value={logoUrl}
              onChange={(e) => setLogoUrl(e.target.value)}
            />
          </div>

          {logoUrl && (
            <div className="rounded-lg border border-border/70 bg-muted/30 p-4">
              <p className="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Logo Preview</p>
              <img
                src={logoUrl}
                alt="Logo preview"
                className="h-16 w-16 rounded-xl border border-border/60 bg-background object-contain p-1"
              />
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="brand-text">Brand Name (shown in sidebar when no logo)</Label>
            <Input
              id="brand-text"
              placeholder="e.g. ACME Corp"
              value={brandText}
              onChange={(e) => setBrandText(e.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="site-title">Site Title (portal subtitle in sidebar)</Label>
            <Input
              id="site-title"
              placeholder="e.g. Client Portal"
              value={siteTitle}
              onChange={(e) => setSiteTitle(e.target.value)}
            />
            <p className="text-xs text-muted-foreground">Shown below the brand name in the sidebar. Also used as the browser tab title.</p>
          </div>

          <div className="space-y-2">
            <Label htmlFor="branding-domain">Custom Domain (optional)</Label>
            <Input
              id="branding-domain"
              placeholder="clients.yourcompany.com"
              value={domain}
              onChange={(e) => setDomain(e.target.value)}
            />
            <p className="text-xs text-muted-foreground">If set, branding is tied to this exact host.</p>
          </div>
        </CardContent>
      </Card>

      {/* Section: Colors */}
      <Card>
        <CardHeader>
          <CardTitle>Brand Colors</CardTitle>
          <CardDescription>Set your primary brand color for light and dark mode.</CardDescription>
        </CardHeader>
        <CardContent className="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <ColorField label="Primary Color (light)" id="primary-color" value={primaryColor} onChange={setPrimaryColor} />
          <ColorField label="Primary Color (dark mode)" id="primary-color-dark" value={primaryColorDark} onChange={setPrimaryColorDark} />
        </CardContent>
      </Card>

      {/* Section: Sidebar */}
      <Card>
        <CardHeader>
          <CardTitle>Sidebar</CardTitle>
          <CardDescription>Customize sidebar background, text colors, and width.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-5">
          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <ColorField label="Sidebar Background (light)" id="sidebar-bg" value={sidebarBgColor} onChange={setSidebarBgColor} />
            <ColorField label="Sidebar Background (dark)" id="sidebar-bg-dark" value={sidebarBgColorDark} onChange={setSidebarBgColorDark} />
            <ColorField label="Sidebar Text (light)" id="sidebar-text" value={sidebarTextColor} onChange={setSidebarTextColor} />
            <ColorField label="Sidebar Text (dark)" id="sidebar-text-dark" value={sidebarTextColorDark} onChange={setSidebarTextColorDark} />
          </div>
          <RadioGroup<SidebarWidth>
            label="Sidebar Width"
            value={sidebarWidth}
            onChange={setSidebarWidth}
            options={[
              { value: "narrow", label: "Narrow" },
              { value: "standard", label: "Standard" },
              { value: "wide", label: "Wide" },
            ]}
          />
        </CardContent>
      </Card>

      {/* Section: Typography & Spacing */}
      <Card>
        <CardHeader>
          <CardTitle>Typography &amp; Spacing</CardTitle>
          <CardDescription>Adjust font size and content padding defaults.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-5">
          <RadioGroup<FontSize>
            label="Font Size"
            value={fontSize}
            onChange={setFontSize}
            options={[
              { value: "sm", label: "Small" },
              { value: "md", label: "Medium" },
              { value: "lg", label: "Large" },
            ]}
          />
          <RadioGroup<PaddingSize>
            label="Content Padding"
            value={paddingSize}
            onChange={setPaddingSize}
            options={[
              { value: "compact", label: "Compact" },
              { value: "standard", label: "Standard" },
              { value: "spacious", label: "Spacious" },
            ]}
          />
        </CardContent>
      </Card>

      {/* Section: Login Page */}
      <Card>
        <CardHeader>
          <CardTitle>Login Page</CardTitle>
          <CardDescription>Set a custom image for the login/signup page.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="login-image-url">Login Page Image URL</Label>
            <Input
              id="login-image-url"
              placeholder="https://your-cdn.com/hero.jpg"
              value={loginImageUrl}
              onChange={(e) => setLoginImageUrl(e.target.value)}
            />
            <p className="text-xs text-muted-foreground">Recommended: 1200x800px or larger.</p>
          </div>
          {loginImageUrl && (
            <div className="rounded-lg border border-border/70 bg-muted/30 p-4">
              <p className="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Login Image Preview</p>
              <img
                src={loginImageUrl}
                alt="Login image preview"
                className="max-h-40 rounded-lg border border-border/60 object-cover"
              />
            </div>
          )}
        </CardContent>
      </Card>

      <Button onClick={handleSave} disabled={isSaving} size="lg">
        {isSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
        Save Branding
      </Button>
    </div>
  );
}
