"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Loader2, Upload } from "lucide-react";

interface BrandingSettingsProps {
  clientId: string | null;
  isPortalBrandingScope?: boolean;
  initialLogoUrl?: string | null;
  initialDomain?: string | null;
}

export function BrandingSettings({
  clientId,
  isPortalBrandingScope = false,
  initialLogoUrl,
  initialDomain,
}: BrandingSettingsProps) {
  const router = useRouter();
  const [logoUrl, setLogoUrl] = useState(initialLogoUrl || "");
  const [domain, setDomain] = useState(initialDomain || "");
  const [isSaving, setIsSaving] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);

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
        body: JSON.stringify({ clientId, logoUrl: logoUrl || null, domain: domain || null }),
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
      setSuccess(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to upload logo");
    } finally {
      setIsUploading(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Portal Branding</CardTitle>
        <CardDescription>
          {isPortalBrandingScope
            ? "Update the default branding for the main portal organization."
            : "Set the company logo shown on the Home and Login pages."}
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {error && <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</div>}
        {success && <div className="rounded-md bg-green-50 p-3 text-sm text-green-800">Branding saved successfully.</div>}
        {isPortalBrandingScope ? (
          <div className="rounded-md border border-primary/30 bg-primary/5 p-3 text-xs text-primary">
            You are editing the global branding used by the main portal.
          </div>
        ) : null}

        <div className="space-y-2">
          <Label htmlFor="logo-url">Company Logo URL</Label>
          <Input
            id="logo-url"
            placeholder="https://your-cdn.com/company-logo.png"
            value={logoUrl}
            onChange={(e) => setLogoUrl(e.target.value)}
          />
          <p className="text-xs text-muted-foreground">Use a square PNG/SVG (recommended: 256x256).</p>
        </div>

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
            {selectedFile ? <p className="text-xs text-muted-foreground">{selectedFile.name}</p> : null}
          </div>
        </div>

        <div className="space-y-2">
          <Label htmlFor="branding-domain">Domain (optional)</Label>
          <Input
            id="branding-domain"
            placeholder="clients.yourcompany.com"
            value={domain}
            onChange={(e) => setDomain(e.target.value)}
          />
          <p className="text-xs text-muted-foreground">If set, branding is tied to this exact host.</p>
        </div>

        {logoUrl ? (
          <div className="rounded-lg border border-border/70 bg-muted/30 p-4">
            <p className="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Preview</p>
            <img
              src={logoUrl}
              alt="Company logo preview"
              className="h-16 w-16 rounded-xl border border-border/60 bg-background object-contain p-1"
            />
          </div>
        ) : null}

        <Button onClick={handleSave} disabled={isSaving}>
          {isSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          Save Branding
        </Button>
      </CardContent>
    </Card>
  );
}
