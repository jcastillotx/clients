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
  onSave: (data: any) => Promise<void>;
}

const EMAIL_TYPES = [
  { value: "invoice_sent", label: "Invoice Sent" },
  { value: "invoice_reminder", label: "Invoice Reminder" },
  { value: "payment_received", label: "Payment Received" },
  { value: "payment_failed", label: "Payment Failed" },
  { value: "invoice_overdue", label: "Invoice Overdue" },
];

export function EmailTemplateDialog({ open, onOpenChange, template, onSave }: EmailTemplateDialogProps) {
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [type, setType] = useState("invoice_sent");
  const [subject, setSubject] = useState("");
  const [htmlContent, setHtmlContent] = useState("");
  const [textContent, setTextContent] = useState("");
  const [isDefault, setIsDefault] = useState(false);
  const [isActive, setIsActive] = useState(true);
  const [isSaving, setIsSaving] = useState(false);

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
    } else {
      setName("");
      setDescription("");
      setType("invoice_sent");
      setSubject("New Invoice {{invoice_number}}");
      setHtmlContent(`<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">
  <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2>Hello {{client_name}},</h2>
    <p>Your invoice is ready.</p>
    <p><strong>Invoice Number:</strong> {{invoice_number}}</p>
    <p><strong>Amount:</strong> ${{ amount }}</p>
    <p>Thank you for your business!</p>
  </div>
</body>
</html>`);
      setTextContent(`Hello {{client_name}},

Your invoice is ready.

Invoice Number: {{invoice_number}}
Amount: ${{ amount }}

Thank you for your business!`);
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

  const getAvailableVariables = (emailType: string) => {
    const common = ["client_name", "company_name"];

    const typeVars: Record<string, string[]> = {
      invoice_sent: [...common, "invoice_number", "invoice_date", "due_date", "amount", "invoice_url"],
      invoice_reminder: [...common, "invoice_number", "due_date", "amount", "days_until_due", "invoice_url"],
      payment_received: [...common, "invoice_number", "amount", "payment_date", "payment_method"],
      payment_failed: [...common, "invoice_number", "amount", "error_message", "invoice_url"],
      invoice_overdue: [...common, "invoice_number", "due_date", "amount", "days_overdue", "invoice_url"],
    };

    return typeVars[emailType] || common;
  };

  const availableVars = getAvailableVariables(type);

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
            <TabsList className="grid w-full grid-cols-3">
              <TabsTrigger value="settings">Settings</TabsTrigger>
              <TabsTrigger value="html">HTML</TabsTrigger>
              <TabsTrigger value="text">Plain Text</TabsTrigger>
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
                    {EMAIL_TYPES.map((t) => (
                      <SelectItem key={t.value} value={t.value}>
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
                  Available Variables for {EMAIL_TYPES.find((t) => t.value === type)?.label}:
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
                  Plain text version for email clients that don't support HTML
                </p>
              </div>
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
