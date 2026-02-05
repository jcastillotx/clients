import { createClient } from "@/lib/supabase/server";
import { renderTemplate } from "@/lib/templates/template-engine";

// Fetch and render email template
export async function renderEmailTemplate(
  type: string,
  data: Record<string, any>,
): Promise<{ subject: string; html: string; plainText: string } | null> {
  const supabase = createClient();

  // Fetch template from database
  const { data: template, error } = await supabase
    .from("email_templates")
    .select("*")
    .eq("email_type", type)
    .eq("is_active", true)
    .order("is_default", { ascending: false })
    .limit(1)
    .single();

  if (error || !template) {
    console.error(`Email template not found for type: ${type}`, error);
    return null;
  }

  // Render subject, HTML, and plain text
  const subject = renderTemplate(template.subject || "", data);
  const html = renderTemplate(template.html_content, data);
  const plainText = template.plain_text_content ? renderTemplate(template.plain_text_content, data) : stripHtml(html);

  return { subject, html, plainText };
}

// Simple HTML stripper for plain text fallback
function stripHtml(html: string): string {
  return html
    .replace(/<style[^>]*>.*?<\/style>/gi, "")
    .replace(/<script[^>]*>.*?<\/script>/gi, "")
    .replace(/<[^>]+>/g, "")
    .replace(/\s+/g, " ")
    .trim();
}

// Invoice reminder email data
export interface InvoiceReminderData {
  invoice: {
    invoice_number: string;
    amount: number;
    currency: string;
    due_date: string;
  };
  client: {
    company_name: string;
    contact_name?: string;
  };
  days_until_due: number;
  payment_url: string;
}

// Request notification data
export interface RequestNotificationData {
  request: {
    title: string;
    description?: string;
    status: string;
    request_number: string;
  };
  client: {
    company_name: string;
  };
  assigned_to?: {
    name: string;
  };
  request_url: string;
}

// SLA breach warning data
export interface SLABreachData {
  request: {
    title: string;
    request_number: string;
    priority: string;
  };
  sla: {
    response_time_hours: number;
    resolution_time_hours: number;
  };
  time_remaining_hours: number;
  request_url: string;
}

// Contract expiration data
export interface ContractExpirationData {
  contract: {
    title: string;
    contract_number: string;
    end_date: string;
  };
  client: {
    company_name: string;
  };
  days_until_expiration: number;
  auto_renew: boolean;
  contract_url: string;
}
