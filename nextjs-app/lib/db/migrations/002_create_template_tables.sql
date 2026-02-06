-- Create invoice templates table
CREATE TABLE IF NOT EXISTS invoice_templates (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  name TEXT NOT NULL,
  description TEXT,
  html_content TEXT NOT NULL,
  css_content TEXT,
  available_variables JSONB,
  is_default BOOLEAN DEFAULT false,
  is_active BOOLEAN DEFAULT true,
  preview_data JSONB,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create email templates table
CREATE TABLE IF NOT EXISTS email_templates (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  name TEXT NOT NULL,
  description TEXT,
  type TEXT NOT NULL,
  subject TEXT NOT NULL,
  html_content TEXT NOT NULL,
  text_content TEXT,
  available_variables JSONB,
  is_default BOOLEAN DEFAULT false,
  is_active BOOLEAN DEFAULT true,
  preview_data JSONB,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create indexes
CREATE INDEX idx_invoice_templates_is_default ON invoice_templates(is_default) WHERE is_default = true AND deleted_at IS NULL;
CREATE INDEX idx_invoice_templates_is_active ON invoice_templates(is_active) WHERE is_active = true AND deleted_at IS NULL;
CREATE INDEX idx_email_templates_type ON email_templates(type) WHERE deleted_at IS NULL;
CREATE INDEX idx_email_templates_is_default ON email_templates(is_default, type) WHERE is_default = true AND deleted_at IS NULL;

-- Insert default invoice template
INSERT INTO invoice_templates (name, description, html_content, css_content, available_variables, is_default, is_active, preview_data)
VALUES (
  'Default Invoice Template',
  'Standard invoice template with company branding',
  '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice {{invoice_number}}</title>
</head>
<body>
  <div class="invoice-container">
    <div class="header">
      <div class="company-info">
        <h1>{{company_name}}</h1>
        <p>{{company_address}}</p>
        <p>{{company_phone}}</p>
        <p>{{company_email}}</p>
      </div>
      <div class="invoice-info">
        <h2>INVOICE</h2>
        <p><strong>Invoice #:</strong> {{invoice_number}}</p>
        <p><strong>Date:</strong> {{invoice_date}}</p>
        <p><strong>Due Date:</strong> {{due_date}}</p>
      </div>
    </div>

    <div class="client-info">
      <h3>Bill To:</h3>
      <p><strong>{{client_name}}</strong></p>
      <p>{{client_address}}</p>
      <p>{{client_email}}</p>
    </div>

    <table class="items-table">
      <thead>
        <tr>
          <th>Description</th>
          <th>Quantity</th>
          <th>Rate</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        {{#each items}}
        <tr>
          <td>{{description}}</td>
          <td>{{quantity}}</td>
          <td>${{rate}}</td>
          <td>${{amount}}</td>
        </tr>
        {{/each}}
      </tbody>
    </table>

    <div class="totals">
      <div class="total-row">
        <span>Subtotal:</span>
        <span>${{subtotal}}</span>
      </div>
      <div class="total-row">
        <span>Tax ({{tax_rate}}%):</span>
        <span>${{tax_amount}}</span>
      </div>
      <div class="total-row total">
        <span><strong>Total:</strong></span>
        <span><strong>${{total}}</strong></span>
      </div>
    </div>

    <div class="footer">
      <p>{{payment_instructions}}</p>
      <p class="thank-you">Thank you for your business!</p>
    </div>
  </div>
</body>
</html>',
  '.invoice-container { max-width: 800px; margin: 0 auto; padding: 40px; font-family: Arial, sans-serif; }
.header { display: flex; justify-content: space-between; margin-bottom: 40px; }
.company-info h1 { margin: 0; color: #333; }
.invoice-info { text-align: right; }
.invoice-info h2 { margin: 0; color: #666; }
.client-info { margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 4px; }
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
.items-table th { background: #f5f5f5; padding: 12px; text-align: left; border-bottom: 2px solid #ddd; }
.items-table td { padding: 12px; border-bottom: 1px solid #eee; }
.totals { text-align: right; margin-bottom: 30px; }
.total-row { display: flex; justify-content: flex-end; gap: 100px; margin-bottom: 8px; }
.total-row.total { font-size: 1.2em; margin-top: 12px; padding-top: 12px; border-top: 2px solid #333; }
.footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; }
.thank-you { font-size: 1.1em; margin-top: 20px; }',
  '["invoice_number", "invoice_date", "due_date", "company_name", "company_address", "company_phone", "company_email", "client_name", "client_address", "client_email", "items", "subtotal", "tax_rate", "tax_amount", "total", "payment_instructions"]',
  true,
  true,
  '{"invoice_number": "INV-001", "invoice_date": "2024-01-01", "due_date": "2024-01-31", "company_name": "Your Company", "company_address": "123 Main St, City, State 12345", "company_phone": "(555) 123-4567", "company_email": "billing@company.com", "client_name": "Client Company", "client_address": "456 Client Ave, City, State 67890", "client_email": "client@example.com", "items": [{"description": "Web Development", "quantity": "10", "rate": "100.00", "amount": "1000.00"}], "subtotal": "1000.00", "tax_rate": "8.5", "tax_amount": "85.00", "total": "1085.00", "payment_instructions": "Please pay via bank transfer or credit card"}'
);

-- Insert default email templates
INSERT INTO email_templates (name, description, type, subject, html_content, text_content, available_variables, is_default, is_active, preview_data)
VALUES
(
  'Invoice Sent Email',
  'Email sent when a new invoice is created',
  'invoice_sent',
  'New Invoice {{invoice_number}} from {{company_name}}',
  '<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
  <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #2563eb;">New Invoice from {{company_name}}</h2>
    <p>Hello {{client_name}},</p>
    <p>We have sent you a new invoice for your review.</p>

    <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">
      <p><strong>Invoice Number:</strong> {{invoice_number}}</p>
      <p><strong>Invoice Date:</strong> {{invoice_date}}</p>
      <p><strong>Due Date:</strong> {{due_date}}</p>
      <p><strong>Amount:</strong> ${{amount}}</p>
    </div>

    <p>
      <a href="{{invoice_url}}" style="display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;">View Invoice</a>
    </p>

    <p>If you have any questions, please don''t hesitate to contact us.</p>

    <p>Best regards,<br>{{company_name}}</p>
  </div>
</body>
</html>',
  'Hello {{client_name}},

We have sent you a new invoice for your review.

Invoice Number: {{invoice_number}}
Invoice Date: {{invoice_date}}
Due Date: {{due_date}}
Amount: ${{amount}}

View your invoice: {{invoice_url}}

If you have any questions, please don''t hesitate to contact us.

Best regards,
{{company_name}}',
  '["client_name", "company_name", "invoice_number", "invoice_date", "due_date", "amount", "invoice_url"]',
  true,
  true,
  '{"client_name": "John Doe", "company_name": "Your Company", "invoice_number": "INV-001", "invoice_date": "2024-01-01", "due_date": "2024-01-31", "amount": "1085.00", "invoice_url": "https://app.example.com/invoices/123"}'
),
(
  'Invoice Reminder Email',
  'Email sent to remind clients of upcoming or overdue invoices',
  'invoice_reminder',
  'Reminder: Invoice {{invoice_number}} Due {{due_date}}',
  '<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
  <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #dc2626;">Invoice Reminder</h2>
    <p>Hello {{client_name}},</p>
    <p>This is a friendly reminder about invoice {{invoice_number}}.</p>

    <div style="background: #fef2f2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc2626;">
      <p><strong>Invoice Number:</strong> {{invoice_number}}</p>
      <p><strong>Due Date:</strong> {{due_date}}</p>
      <p><strong>Amount Due:</strong> ${{amount}}</p>
      <p><strong>Days {{overdue_status}}:</strong> {{days_until_due}}</p>
    </div>

    <p>
      <a href="{{invoice_url}}" style="display: inline-block; background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;">Pay Now</a>
    </p>

    <p>If you have already made the payment, please disregard this reminder.</p>

    <p>Best regards,<br>{{company_name}}</p>
  </div>
</body>
</html>',
  'Hello {{client_name}},

This is a friendly reminder about invoice {{invoice_number}}.

Invoice Number: {{invoice_number}}
Due Date: {{due_date}}
Amount Due: ${{amount}}
Days {{overdue_status}}: {{days_until_due}}

Pay now: {{invoice_url}}

If you have already made the payment, please disregard this reminder.

Best regards,
{{company_name}}',
  '["client_name", "company_name", "invoice_number", "due_date", "amount", "days_until_due", "overdue_status", "invoice_url"]',
  true,
  true,
  '{"client_name": "John Doe", "company_name": "Your Company", "invoice_number": "INV-001", "due_date": "2024-01-31", "amount": "1085.00", "days_until_due": "7", "overdue_status": "until due", "invoice_url": "https://app.example.com/invoices/123"}'
),
(
  'Payment Received Email',
  'Email sent when a payment is successfully received',
  'payment_received',
  'Payment Received for Invoice {{invoice_number}}',
  '<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
  <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #16a34a;">Payment Received</h2>
    <p>Hello {{client_name}},</p>
    <p>Thank you! We have received your payment.</p>

    <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
      <p><strong>Invoice Number:</strong> {{invoice_number}}</p>
      <p><strong>Payment Amount:</strong> ${{amount}}</p>
      <p><strong>Payment Date:</strong> {{payment_date}}</p>
      <p><strong>Payment Method:</strong> {{payment_method}}</p>
    </div>

    <p>A receipt has been sent to your email address.</p>

    <p>Thank you for your business!</p>

    <p>Best regards,<br>{{company_name}}</p>
  </div>
</body>
</html>',
  'Hello {{client_name}},

Thank you! We have received your payment.

Invoice Number: {{invoice_number}}
Payment Amount: ${{amount}}
Payment Date: {{payment_date}}
Payment Method: {{payment_method}}

A receipt has been sent to your email address.

Thank you for your business!

Best regards,
{{company_name}}',
  '["client_name", "company_name", "invoice_number", "amount", "payment_date", "payment_method"]',
  true,
  true,
  '{"client_name": "John Doe", "company_name": "Your Company", "invoice_number": "INV-001", "amount": "1085.00", "payment_date": "2024-01-15", "payment_method": "Credit Card"}'
);

-- Enable RLS
ALTER TABLE invoice_templates ENABLE ROW LEVEL SECURITY;
ALTER TABLE email_templates ENABLE ROW LEVEL SECURITY;

-- Create RLS policies (admin only for template management)
CREATE POLICY "Admin can manage invoice templates" ON invoice_templates
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

CREATE POLICY "Admin can manage email templates" ON email_templates
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

-- Create updated_at trigger function if it doesn't exist
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Add triggers for updated_at
CREATE TRIGGER update_invoice_templates_updated_at
  BEFORE UPDATE ON invoice_templates
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_email_templates_updated_at
  BEFORE UPDATE ON email_templates
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();
