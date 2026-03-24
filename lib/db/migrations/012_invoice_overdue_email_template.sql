-- Default template for overdue invoices (used by Inngest sendInvoiceReminders)
INSERT INTO email_templates (
  name,
  description,
  type,
  subject,
  html_content,
  text_content,
  available_variables,
  is_default,
  is_active,
  preview_data
)
SELECT
  'Invoice Overdue Email',
  'Notice when an invoice is past due',
  'invoice_overdue',
  'Overdue: Invoice {{invoice_number}}',
  $html$
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
  <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #b91c1c;">Invoice overdue</h2>
    <p>Hello {{client_name}},</p>
    <p>Invoice <strong>{{invoice_number}}</strong> is now overdue.</p>
    <div style="background: #fef2f2; padding: 16px; border-radius: 8px; margin: 16px 0;">
      <p><strong>Due date:</strong> {{due_date}}</p>
      <p><strong>Amount:</strong> ${{amount}}</p>
      <p><strong>Days overdue:</strong> {{days_overdue}}</p>
    </div>
    <p><a href="{{invoice_url}}" style="display: inline-block; background: #b91c1c; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px;">Pay now</a></p>
    <p>Best regards,<br>{{company_name}}</p>
  </div>
</body>
</html>
$html$::text,
  $txt$
Hello {{client_name}},

Invoice {{invoice_number}} is overdue.

Due date: {{due_date}}
Amount: ${{amount}}
Days overdue: {{days_overdue}}

Pay now: {{invoice_url}}

Best regards,
{{company_name}}
$txt$::text,
  '["client_name", "company_name", "invoice_number", "due_date", "amount", "days_overdue", "invoice_url"]'::jsonb,
  true,
  true,
  '{"client_name": "Jane", "company_name": "Your Company", "invoice_number": "INV-001", "due_date": "Jan 1, 2025", "amount": "100.00", "days_overdue": "3", "invoice_url": "https://example.com/pay-invoice?invoice=INV-001"}'::jsonb
WHERE NOT EXISTS (
  SELECT 1 FROM email_templates WHERE type = 'invoice_overdue' AND deleted_at IS NULL
);
