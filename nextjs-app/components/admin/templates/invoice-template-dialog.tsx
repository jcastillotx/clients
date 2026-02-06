"use client";

import { useState, useEffect } from "react";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ScrollArea } from "@/components/ui/scroll-area";

interface Template {
  id: string;
  name: string;
  description: string | null;
  html_content: string;
  css_content: string | null;
  is_default: boolean;
  is_active: boolean;
}

interface InvoiceTemplateDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  template: Template | null;
  onSave: (data: any) => Promise<void>;
}

export function InvoiceTemplateDialog({ open, onOpenChange, template, onSave }: InvoiceTemplateDialogProps) {
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [htmlContent, setHtmlContent] = useState("");
  const [cssContent, setCssContent] = useState("");
  const [isDefault, setIsDefault] = useState(false);
  const [isActive, setIsActive] = useState(true);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    if (template) {
      setName(template.name);
      setDescription(template.description || "");
      setHtmlContent(template.html_content);
      setCssContent(template.css_content || "");
      setIsDefault(template.is_default);
      setIsActive(template.is_active);
    } else {
      setName("");
      setDescription("");
      setHtmlContent(`<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice {{invoice_number}}</title>
</head>
<body>
  <div class="invoice-container">
    <h1>Invoice {{invoice_number}}</h1>
    <p>Amount: ${{ total }}</p>
  </div>
</body>
</html>`);
      setCssContent(`.invoice-container { max-width: 800px; margin: 0 auto; padding: 40px; }`);
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
        htmlContent,
        cssContent: cssContent || null,
        isDefault,
        isActive,
      });
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[90vh]">
        <DialogHeader>
          <DialogTitle>{template ? "Edit Template" : "Create Template"}</DialogTitle>
          <DialogDescription>
            {template ? "Update the invoice template" : "Create a new invoice template with custom HTML and CSS"}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit}>
          <Tabs defaultValue="settings" className="w-full">
            <TabsList className="grid w-full grid-cols-3">
              <TabsTrigger value="settings">Settings</TabsTrigger>
              <TabsTrigger value="html">HTML</TabsTrigger>
              <TabsTrigger value="css">CSS</TabsTrigger>
            </TabsList>

            <TabsContent value="settings" className="space-y-4 mt-4">
              <div className="space-y-2">
                <Label htmlFor="name">Template Name *</Label>
                <Input
                  id="name"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="My Invoice Template"
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
                  rows={3}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="is-default">Default Template</Label>
                  <p className="text-sm text-muted-foreground">Use this template by default for new invoices</p>
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
                <h4 className="font-medium mb-2">Available Variables:</h4>
                <div className="grid grid-cols-2 gap-2 text-sm text-muted-foreground">
                  <code>{"{{invoice_number}}"}</code>
                  <code>{"{{invoice_date}}"}</code>
                  <code>{"{{due_date}}"}</code>
                  <code>{"{{company_name}}"}</code>
                  <code>{"{{client_name}}"}</code>
                  <code>{"{{total}}"}</code>
                  <code>{"{{subtotal}}"}</code>
                  <code>{"{{tax_amount}}"}</code>
                  <code>{"{{#each items}}...{{/each}}"}</code>
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

            <TabsContent value="css" className="mt-4">
              <div className="space-y-2">
                <Label htmlFor="css">CSS Styles</Label>
                <ScrollArea className="h-[400px] w-full rounded-md border">
                  <Textarea
                    id="css"
                    value={cssContent}
                    onChange={(e) => setCssContent(e.target.value)}
                    placeholder="Enter CSS styles..."
                    className="min-h-[400px] font-mono text-sm border-0 focus-visible:ring-0"
                  />
                </ScrollArea>
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
