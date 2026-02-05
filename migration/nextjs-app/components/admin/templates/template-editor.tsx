"use client";

import { useState } from "react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { InvoiceTemplateList } from "./invoice-template-list";
import { EmailTemplateList } from "./email-template-list";

interface TemplateEditorProps {
  invoiceTemplates: any[];
  emailTemplates: any[];
}

export function TemplateEditor({
  invoiceTemplates: initialInvoiceTemplates,
  emailTemplates: initialEmailTemplates,
}: TemplateEditorProps) {
  const [invoiceTemplates, setInvoiceTemplates] = useState(initialInvoiceTemplates);
  const [emailTemplates, setEmailTemplates] = useState(initialEmailTemplates);

  return (
    <Tabs defaultValue="invoice" className="w-full">
      <TabsList className="grid w-full max-w-md grid-cols-2">
        <TabsTrigger value="invoice">Invoice Templates</TabsTrigger>
        <TabsTrigger value="email">Email Templates</TabsTrigger>
      </TabsList>

      <TabsContent value="invoice" className="mt-6">
        <InvoiceTemplateList templates={invoiceTemplates} onTemplatesChange={setInvoiceTemplates} />
      </TabsContent>

      <TabsContent value="email" className="mt-6">
        <EmailTemplateList templates={emailTemplates} onTemplatesChange={setEmailTemplates} />
      </TabsContent>
    </Tabs>
  );
}
