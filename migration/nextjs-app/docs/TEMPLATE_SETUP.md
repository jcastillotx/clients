# Template System Setup Guide

This guide covers the invoice and email template management system for customizing how invoices and automated emails are rendered.

## Overview

The template system allows administrators to:

- Create custom invoice templates with HTML and CSS
- Design email templates for automated notifications
- Use variables to dynamically populate content
- Preview templates before activating them
- Set default templates for automatic use

## Features

### Invoice Templates

- **Custom HTML/CSS**: Full control over invoice appearance
- **Template Variables**: Dynamic placeholders for invoice data
- **Default Template**: Mark one template as the default for new invoices
- **Active/Inactive Status**: Enable or disable templates
- **Preview Functionality**: See how templates look with sample data

### Email Templates

- **Multiple Email Types**: Templates for different events (invoice sent, payment received, etc.)
- **Subject Line Customization**: Dynamic variables in subject lines
- **HTML + Plain Text**: Both versions for email client compatibility
- **Type-Specific Variables**: Different variables available per email type

## Database Schema

### Invoice Templates Table

```sql
CREATE TABLE invoice_templates (
  id UUID PRIMARY KEY,
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
```

### Email Templates Table

```sql
CREATE TABLE email_templates (
  id UUID PRIMARY KEY,
  name TEXT NOT NULL,
  description TEXT,
  type TEXT NOT NULL,  -- invoice_sent, invoice_reminder, etc.
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
```

## Invoice Template Variables

Available variables for invoice templates:

| Variable                   | Description           | Example               |
| -------------------------- | --------------------- | --------------------- |
| `{{invoice_number}}`       | Invoice number        | INV-001               |
| `{{invoice_date}}`         | Invoice creation date | 2024-01-01            |
| `{{due_date}}`             | Payment due date      | 2024-01-31            |
| `{{company_name}}`         | Your company name     | Acme Inc              |
| `{{company_address}}`      | Company address       | 123 Main St           |
| `{{company_phone}}`        | Company phone         | (555) 123-4567        |
| `{{company_email}}`        | Company email         | billing@acme.com      |
| `{{client_name}}`          | Client company name   | Client Corp           |
| `{{client_address}}`       | Client address        | 456 Client Ave        |
| `{{client_email}}`         | Client email          | client@example.com    |
| `{{subtotal}}`             | Subtotal before tax   | 1000.00               |
| `{{tax_rate}}`             | Tax rate percentage   | 8.5                   |
| `{{tax_amount}}`           | Tax amount            | 85.00                 |
| `{{total}}`                | Total amount due      | 1085.00               |
| `{{payment_instructions}}` | Payment instructions  | Pay via bank transfer |

### Item Iteration

Use `{{#each items}}...{{/each}}` to loop through invoice items:

```html
<table>
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
```

## Email Template Types

### 1. Invoice Sent (`invoice_sent`)

Sent when a new invoice is created and sent to the client.

**Available Variables:**

- `{{client_name}}`
- `{{company_name}}`
- `{{invoice_number}}`
- `{{invoice_date}}`
- `{{due_date}}`
- `{{amount}}`
- `{{invoice_url}}`

### 2. Invoice Reminder (`invoice_reminder`)

Sent to remind clients about upcoming or overdue invoices.

**Available Variables:**

- `{{client_name}}`
- `{{company_name}}`
- `{{invoice_number}}`
- `{{due_date}}`
- `{{amount}}`
- `{{days_until_due}}`
- `{{overdue_status}}` (e.g., "until due" or "overdue")
- `{{invoice_url}}`

### 3. Payment Received (`payment_received`)

Sent when a payment is successfully processed.

**Available Variables:**

- `{{client_name}}`
- `{{company_name}}`
- `{{invoice_number}}`
- `{{amount}}`
- `{{payment_date}}`
- `{{payment_method}}`

### 4. Payment Failed (`payment_failed`)

Sent when a payment attempt fails.

**Available Variables:**

- `{{client_name}}`
- `{{company_name}}`
- `{{invoice_number}}`
- `{{amount}}`
- `{{error_message}}`
- `{{invoice_url}}`

### 5. Invoice Overdue (`invoice_overdue`)

Sent when an invoice becomes overdue.

**Available Variables:**

- `{{client_name}}`
- `{{company_name}}`
- `{{invoice_number}}`
- `{{due_date}}`
- `{{amount}}`
- `{{days_overdue}}`
- `{{invoice_url}}`

## API Routes

### Invoice Templates

```typescript
GET    /api/admin/templates/invoice         // List all invoice templates
POST   /api/admin/templates/invoice         // Create new invoice template
GET    /api/admin/templates/invoice/:id     // Get specific invoice template
PATCH  /api/admin/templates/invoice/:id     // Update invoice template
DELETE /api/admin/templates/invoice/:id     // Delete invoice template
POST   /api/admin/templates/invoice/:id/preview  // Preview invoice template
```

### Email Templates

```typescript
GET    /api/admin/templates/email           // List all email templates
GET    /api/admin/templates/email?type=invoice_sent  // Filter by type
POST   /api/admin/templates/email           // Create new email template
GET    /api/admin/templates/email/:id       // Get specific email template
PATCH  /api/admin/templates/email/:id       // Update email template
DELETE /api/admin/templates/email/:id       // Delete email template
POST   /api/admin/templates/email/:id/preview  // Preview email template
```

## Using Templates in Code

### Getting Default Invoice Template

```typescript
const { data: template } = await supabase
    .from("invoice_templates")
    .select("*")
    .eq("is_default", true)
    .eq("is_active", true)
    .single();
```

### Getting Default Email Template by Type

```typescript
const { data: template } = await supabase
    .from("email_templates")
    .select("*")
    .eq("type", "invoice_sent")
    .eq("is_default", true)
    .eq("is_active", true)
    .single();
```

### Rendering a Template

```typescript
import { renderTemplate } from "@/lib/templates/template-engine";

const rendered = renderTemplate(template.html_content, {
    invoice_number: "INV-001",
    client_name: "John Doe",
    amount: "1085.00",
    // ... other variables
});
```

### Rendering Invoice Template with CSS

```typescript
import { previewTemplate } from "@/lib/templates/template-engine";

const rendered = previewTemplate(
    template.html_content,
    template.css_content,
    invoiceData,
);
```

## Admin UI

Access the template editor at:

```
/admin/settings/templates
```

### Creating a New Template

1. Navigate to **Admin > Settings > Templates**
2. Click **New Template** button
3. Fill in template details:
    - **Name**: Descriptive name for the template
    - **Description**: Optional description
    - **HTML Content**: Template HTML with variables
    - **CSS Styles** (invoice only): Styles for the template
    - **Email Type** (email only): Select the email event type
    - **Subject Line** (email only): Subject with variables
    - **Plain Text** (email only): Fallback text version
4. Toggle **Default Template** to use automatically
5. Toggle **Active** to enable the template
6. Click **Create Template**

### Editing a Template

1. Click **Edit** on the template card
2. Update any fields
3. Click **Update Template**

### Previewing a Template

1. Click **Preview** on the template card (invoice templates)
2. Template opens in new tab with sample data

### Deleting a Template

1. Click **Delete** on the template card
2. Confirm deletion

**Note**: Templates are soft-deleted (deleted_at is set) and can be restored from the database if needed.

## Security

- **Row-Level Security (RLS)**: Enabled on both tables
- **Admin Only**: Only super_admin and admin roles can manage templates
- **Permission Check**: `settings.manage` permission required
- **Soft Delete**: Templates are never permanently deleted

## Database Migration

Run the template migration:

```bash
psql $DATABASE_URL < lib/db/migrations/002_create_template_tables.sql
```

This will:

1. Create `invoice_templates` and `email_templates` tables
2. Set up indexes for performance
3. Insert default templates
4. Enable RLS and create policies
5. Add update triggers

## Best Practices

1. **Test First**: Always preview templates before setting as default
2. **Use Descriptive Names**: Make templates easy to identify
3. **Keep One Default**: Only one template should be default per type
4. **Include Variables**: Use all relevant variables for dynamic content
5. **Maintain Plain Text**: Always provide plain text version for emails
6. **Version Templates**: Create new versions rather than editing active defaults
7. **Style Consistently**: Keep branding consistent across templates

## Troubleshooting

### Template Not Rendering Variables

**Problem**: Variables show as `{{variable_name}}` instead of values

**Solution**:

- Verify variable names match exactly (case-sensitive)
- Check that data is being passed to `renderTemplate()`
- Ensure variables are in the `availableVariables` array

### CSS Not Applied in Invoice

**Problem**: Invoice styles not showing

**Solution**:

- CSS must be in `css_content` column
- Use `previewTemplate()` instead of `renderTemplate()`
- Check for CSS syntax errors

### Email Template Not Being Used

**Problem**: Default template not applied

**Solution**:

- Verify `is_default = true` for that email type
- Confirm `is_active = true`
- Check that correct `type` matches the email event

## Example Templates

### Simple Invoice Template

```html
<!DOCTYPE html>
<html>
    <body>
        <div style="max-width: 800px; margin: 0 auto; padding: 40px;">
            <h1>{{company_name}}</h1>
            <h2>Invoice {{invoice_number}}</h2>
            <p>Bill To: {{client_name}}</p>
            <p>Amount Due: ${{total}}</p>
            <p>Due Date: {{due_date}}</p>
        </div>
    </body>
</html>
```

### Simple Email Template

```html
<!DOCTYPE html>
<html>
    <body style="font-family: Arial, sans-serif;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
            <h2>Hello {{client_name}},</h2>
            <p>Your invoice {{invoice_number}} is ready.</p>
            <p>Amount: ${{amount}}</p>
            <a href="{{invoice_url}}">View Invoice</a>
        </div>
    </body>
</html>
```

## Next Steps

1. **Run Database Migration**: Apply the SQL migration
2. **Configure Permissions**: Ensure admin roles have `settings.manage`
3. **Customize Templates**: Edit default templates to match your branding
4. **Test Email Delivery**: Send test emails using different templates
5. **Train Team**: Show admins how to create and manage templates
