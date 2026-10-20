export type EmailTemplateDefinition = {
  type: string;
  label: string;
  description: string;
  variables: string[];
  sampleData: Record<string, string>;
};

const common = {
  app_name: "Kre8iv Clients",
  brand_name: "Kre8ivDesigns",
  brand_logo_url: "https://www.kre8ivdesigns.com/wp-content/uploads/2020/02/logo-copy-2-300x57.png",
  brand_primary_color: "#7C3AED",
  brand_secondary_color: "#ffffff",
  support_email: "support@example.com",
  site_url: "https://clients.example.com",
};

function definition(
  type: string,
  label: string,
  description: string,
  variables: string[],
  sampleData: Record<string, string> = {},
): EmailTemplateDefinition {
  return { type, label, description, variables: [...new Set([...Object.keys(common), ...variables])], sampleData: { ...common, ...sampleData } };
}

export const EMAIL_TEMPLATE_CATALOG: EmailTemplateDefinition[] = [
  definition("auth_signup", "Auth — Verify account", "Email verification after registration", ["confirmation_url", "token", "email", "redirect_to", "email_action_type"]),
  definition("auth_recovery", "Auth — Reset password", "Password reset link and code", ["confirmation_url", "token", "email", "redirect_to", "email_action_type"]),
  definition("auth_magiclink", "Auth — Magic link", "Passwordless sign-in link", ["confirmation_url", "token", "email"]),
  definition("auth_invite", "Auth — Invitation", "New account invitation", ["confirmation_url", "token", "email"]),
  definition("auth_email_change", "Auth — Email change", "Single-message email change confirmation", ["confirmation_url", "token", "email"]),
  definition("auth_email_change_current", "Auth — Email change (current)", "Confirmation sent to the current address", ["confirmation_url", "otp", "email"]),
  definition("auth_email_change_new", "Auth — Email change (new)", "Confirmation sent to the new address", ["confirmation_url", "otp", "email"]),
  definition("auth_generic", "Auth — Security notification", "Fallback for Supabase security notifications", ["confirmation_url", "token", "email", "email_action_type"]),
  definition("invoice_sent", "Invoice — Sent", "Invoice delivery email", ["client_name", "company_name", "invoice_number", "invoice_date", "due_date", "amount", "invoice_url"]),
  definition("invoice_reminder", "Invoice — Reminder", "Upcoming invoice reminder", ["client_name", "company_name", "invoice_number", "due_date", "amount", "days_until_due", "invoice_url"]),
  definition("invoice_overdue", "Invoice — Overdue", "Overdue invoice reminder", ["client_name", "company_name", "invoice_number", "due_date", "amount", "days_overdue", "invoice_url"]),
  definition("payment_received", "Invoice — Payment received", "Payment confirmation", ["client_name", "company_name", "invoice_number", "amount", "payment_date", "payment_method"]),
  definition("payment_failed", "Invoice — Payment failed", "Payment failure notice", ["client_name", "company_name", "invoice_number", "amount", "error_message", "invoice_url"]),
  definition("invoice_paid", "Invoice — Paid", "Invoice paid notification", ["invoice_number", "amount", "company_name"]),
  definition("invoice_payment_failed", "Invoice — Payment failed (internal)", "Internal payment failure notification", ["invoice_number", "amount"]),
  definition("invoice_refunded", "Invoice — Refunded", "Refund notification", ["invoice_number", "amount"]),
  definition("service_request_created", "Notification — Service request", "New service request notification", ["request_title", "request_description", "request_priority", "request_url", "company_name", "created_by_name"]),
  definition("project_request_created", "Notification — Project request", "New project request notification", ["request_title", "request_description", "request_url", "company_name", "created_by_name"]),
  definition("project_request_feedback_created", "Notification — Project request feedback", "New project request feedback notification", ["request_title", "feedback", "request_url", "company_name", "created_by_name"]),
  definition("proposal_sent", "Notification — Proposal sent", "Proposal sent notification", ["proposal_title", "proposal_url", "company_name"]),
  definition("proposal_accepted", "Notification — Proposal accepted", "Proposal acceptance notification", ["proposal_title", "proposal_url", "company_name"]),
  definition("proposal_rejected", "Notification — Proposal rejected", "Proposal rejection notification", ["proposal_title", "proposal_url", "company_name"]),
  definition("proposal_feedback_created", "Notification — Proposal feedback", "New proposal feedback notification", ["proposal_title", "feedback", "proposal_url", "company_name"]),
  definition("support_ticket_created", "Notification — Support ticket", "New support ticket notification", ["ticket_subject", "ticket_description", "ticket_url", "company_name", "created_by_name"]),
  definition("staff_task_created", "Notification — Staff task", "New staff task notification", ["task_title", "task_description", "task_priority", "task_url", "created_by_name"]),
  definition("subscription_updated", "Notification — Subscription updated", "Subscription change notification", ["company_name", "subscription_status"]),
  definition("contract_expiring", "Contract — Expiring", "Contract expiration reminder", ["contract_title", "contract_number", "end_date", "company_name", "days_until_expiration", "contract_url"]),
  definition("contract_expiring_urgent", "Contract — Expiring soon", "Urgent contract expiration reminder", ["contract_title", "contract_number", "end_date", "company_name", "days_until_expiration", "contract_url"]),
  definition("sla_warning", "SLA — Warning", "SLA deadline warning", ["request_title", "request_number", "request_priority", "time_remaining_hours", "request_url"]),
  definition("sla_breach", "SLA — Breach", "SLA breach notification", ["request_title", "request_number", "request_priority", "response_time_hours", "resolution_time_hours", "request_url"]),
];

export const EMAIL_TEMPLATE_TYPES = EMAIL_TEMPLATE_CATALOG.map((item) => item.type);
export const EMAIL_TEMPLATE_BY_TYPE = Object.fromEntries(EMAIL_TEMPLATE_CATALOG.map((item) => [item.type, item]));

export function getEmailTemplateDefinition(type: string): EmailTemplateDefinition | undefined {
  return EMAIL_TEMPLATE_BY_TYPE[type];
}
