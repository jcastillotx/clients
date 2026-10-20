"use client";

import { useState, useEffect } from "react";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ScrollArea } from "@/components/ui/scroll-area";
import { EMAIL_TEMPLATE_CATALOG, getEmailTemplateDefinition } from "@/lib/email/catalog";
import { fetchApi } from "@/lib/api/client";

interface Template {
  id: string;
  name: string;
  description: string | null;
  type: string;
  subject: string;
  html_content: string;
  text_content: string | null;
  is_default: boolean;
  is_active: boolean;
}

interface EmailTemplateDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  template: Template | null;
  onSave: (data: {
    name: string;
    description: string | null;
    type: string;
    subject: string;
    htmlContent: string;
    textContent: string | null;
    isDefault: boolean;
    isActive: boolean;
  }) => Promise<void>;
}

export function EmailTemplateDialog({ open, onOpenChange, template, onSave }: EmailTemplateDialogProps) {
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [type, setType] = useState("auth_recovery");
  const [subject, setSubject] = useState("");
  const [htmlContent, setHtmlContent] = useState("");
  const [textContent, setTextContent] = useState("");
  const [isDefault, setIsDefault] = useState(false);
  const [isActive, setIsActive] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [preview, setPreview] = useState<{ subject: string; html: string } | null>(null);
  const [isPreviewing, setIsPreviewing] = useState(false);

  useEffect(() => {
    if (template) {
      setName(template.name);
      setDescription(template.description || "");
      setType(template.type);
      setSubject(template.subject);
      setHtmlContent(template.html_content);
      setTextContent(template.text_content || "");
      setIsDefault(template.is_default);
      setIsActive(template.is_active);
      setPreview(null);
    } else {
      setName("");
      setDescription("");
      setType("auth_recovery");
      setSubject("Reset your {{app_name}} password");
      setHtmlContent(`<h1>Reset your password</h1>
<p>We received a request to reset the password for <strong>{{email}}</strong>.</p>
<p><a href="{{confirmation_url}}" style="display:inline-block;background:{{brand_primary_color}};color:#fff;padding:12px 20px;text-decoration:none;border-radius:6px;">Set a new password</a></p>
<p>Or enter this code: <strong>{{token}}</strong></p>
<p style="color:#64748b;font-size:13px;">If you did not request this, you can ignore this email.</p>`);
      setTextContent(`Reset your password: {{confirmation_url}}\n\nCode: {{token}}`);
      setIsDefault(false);
      setIsActive(true);
    }
  }, [template]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);

    try {
      await onSave({
        name,
        description: description || null,
        type,
        subject,
        htmlContent,
        textContent: textContent || null,
        isDefault,
        isActive,
      });
    } finally {
      setIsSaving(false);
    }
  };

  const availableVars = getEmailTemplateDefinition(type)?.variables ?? [];
  const selectedDefinition = getEmailTemplateDefinition(type);

  const handlePreview = async () => {
    setIsPreviewing(true);
    try {
      const result = await fetchApi<{ subject: string; html: string }>(
        "/api/admin/templates/email/preview",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ type, subject, htmlContent, textContent: textContent || null }),
        },
        { fallbackMessage: "Failed to preview template" },
      );
      setPreview(result);
    } finally {
      setIsPreviewing(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[90vh]">
        <DialogHeader>
          <DialogTitle>{template ? "Edit Template" : "Create Template"}</DialogTitle>
          <DialogDescription>
            {template ? "Update the email template" : "Create a new email template for automated notifications"}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit}>
          <Tabs defaultValue="settings" className="w-full">
            <TabsList className="grid w-full grid-cols-4">
              <TabsTrigger value="settings">Settings</TabsTrigger>
              <TabsTrigger value="html">HTML</TabsTrigger>
              <TabsTrigger value="text">Plain Text</TabsTrigger>
              <TabsTrigger value="preview">Preview</TabsTrigger>
            </TabsList>

            <TabsContent value="settings" className="space-y-4 mt-4">
              <div className="space-y-2">
                <Label htmlFor="name">Template Name *</Label>
                <Input
                  id="name"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="My Email Template"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  placeholder="Optional description for this template"
                  rows={2}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="type">Email Type *</Label>
                <Select value={type} onValueChange={setType}>
                  <SelectTrigger id="type">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {EMAIL_TEMPLATE_CATALOG.map((t) => (
                      <SelectItem key={t.type} value={t.type}>
                        {t.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="subject">Subject Line *</Label>
                <Input
                  id="subject"
                  value={subject}
                  onChange={(e) => setSubject(e.target.value)}
                  placeholder="Invoice {{invoice_number}} from {{company_name}}"
                  required
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="is-default">Default Template</Label>
                  <p className="text-sm text-muted-foreground">Use this template by default for this email type</p>
                </div>
                <Switch id="is-default" checked={isDefault} onCheckedChange={setIsDefault} />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="is-active">Active</Label>
                  <p className="text-sm text-muted-foreground">Make this template available for use</p>
                </div>
                <Switch id="is-active" checked={isActive} onCheckedChange={setIsActive} />
              </div>

              <div className="rounded-lg bg-muted p-4">
                <h4 className="font-medium mb-2">
                  Available Variables for {selectedDefinition?.label ?? type}:
                </h4>
                <div className="grid grid-cols-2 gap-2 text-sm text-muted-foreground">
                  {availableVars.map((v) => (
                    <code key={v}>{`{{${v}}}`}</code>
                  ))}
                </div>
              </div>
            </TabsContent>

            <TabsContent value="html" className="mt-4">
              <div className="space-y-2">
                <Label htmlFor="html">HTML Content *</Label>
                <ScrollArea className="h-[400px] w-full rounded-md border">
                  <Textarea
                    id="html"
                    value={htmlContent}
                    onChange={(e) => setHtmlContent(e.target.value)}
                    placeholder="Enter HTML content..."
                    className="min-h-[400px] font-mono text-sm border-0 focus-visible:ring-0"
                    required
                  />
                </ScrollArea>
              </div>
            </TabsContent>

            <TabsContent value="text" className="mt-4">
              <div className="space-y-2">
                <Label htmlFor="text">Plain Text Content (Fallback)</Label>
                <ScrollArea className="h-[400px] w-full rounded-md border">
                  <Textarea
                    id="text"
                    value={textContent}
                    onChange={(e) => setTextContent(e.target.value)}
                    placeholder="Enter plain text version of the email..."
                    className="min-h-[400px] font-mono text-sm border-0 focus-visible:ring-0"
                  />
                </ScrollArea>
                <p className="text-sm text-muted-foreground">
                  Plain text version for email clients that do not support HTML
                </p>
              </div>
            </TabsContent>

            <TabsContent value="preview" className="mt-4 space-y-3">
              <div className="flex items-center justify-between">
                <p className="text-sm text-muted-foreground">Preview uses safe sample data and the current portal branding.</p>
                <Button type="button" variant="outline" onClick={handlePreview} disabled={isPreviewing}>
                  {isPreviewing ? "Rendering…" : "Render preview"}
                </Button>
              </div>
              {preview ? (
                <div className="space-y-2">
                  <p className="text-sm"><strong>Subject:</strong> {preview.subject}</p>
                  <iframe title="Email preview" srcDoc={preview.html} sandbox="" className="h-[480px] w-full rounded-md border bg-white" />
                </div>
              ) : (
                <div className="flex h-[480px] items-center justify-center rounded-md border bg-muted/30 text-sm text-muted-foreground">Render a preview to inspect the branded email.</div>
              )}
            </TabsContent>
          </Tabs>

          <div className="flex justify-end gap-2 mt-6">
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSaving}>
              {isSaving ? "Saving..." : template ? "Update Template" : "Create Template"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
