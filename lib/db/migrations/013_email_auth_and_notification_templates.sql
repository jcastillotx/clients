-- Portal-editable auth + notification email templates (Resend / Send Email hook + in-app notifications)

-- Signup / email verification
INSERT INTO email_templates (
  name, description, type, subject, html_content, text_content,
  available_variables, is_default, is_active, preview_data
)
SELECT
  'Auth — Confirm signup',
  'Supabase Send Email hook: signup / email verification',
  'auth_signup',
  'Confirm your email for {{app_name}}',
  $html$
<!DOCTYPE html>
<html>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #111;">
  <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 20px;">Confirm your email</h1>
    <p>Thanks for signing up for <strong>{{app_name}}</strong>.</p>
    <p style="margin: 24px 0;">
      <a href="{{confirmation_url}}" style="display: inline-block; background: #2563eb; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 6px;">Confirm email</a>
    </p>
    <p>Or enter this code:</p>
    <p style="font-size: 22px; letter-spacing: 4px; font-weight: 600;">{{token}}</p>
    <p style="color: #64748b; font-size: 13px;">If you did not create an account, you can ignore this message.</p>
  </div>
</body>
</html>
$html$::text,
  $txt$
Confirm your email for {{app_name}}.

Open: {{confirmation_url}}

Or use code: {{token}}
$txt$::text,
  '["app_name","site_url","confirmation_url","token","email","redirect_to","email_action_type"]'::jsonb,
  true, true,
  '{"app_name":"Kre8iv Clients","token":"123456","confirmation_url":"https://example.com/auth/confirm","site_url":"https://example.com","email":"user@example.com"}'::jsonb
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE type = 'auth_signup' AND deleted_at IS NULL);

-- Password reset / forgot password
INSERT INTO email_templates (
  name, description, type, subject, html_content, text_content,
  available_variables, is_default, is_active, preview_data
)
SELECT
  'Auth — Reset password',
  'Supabase Send Email hook: recovery',
  'auth_recovery',
  'Reset your {{app_name}} password',
  $html$
<!DOCTYPE html>
<html>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #111;">
  <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 20px;">Reset your password</h1>
    <p>We received a request to reset the password for <strong>{{email}}</strong>.</p>
    <p style="margin: 24px 0;">
      <a href="{{confirmation_url}}" style="display: inline-block; background: #2563eb; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 6px;">Set a new password</a>
    </p>
    <p>Or enter this code:</p>
    <p style="font-size: 22px; letter-spacing: 4px; font-weight: 600;">{{token}}</p>
    <p style="color: #64748b; font-size: 13px;">If you did not request this, you can ignore this email.</p>
  </div>
</body>
</html>
$html$::text,
  $txt$
Reset your password: {{confirmation_url}}

Code: {{token}}
$txt$::text,
  '["app_name","site_url","confirmation_url","token","email","redirect_to","email_action_type"]'::jsonb,
  true, true,
  '{"app_name":"Kre8iv Clients","token":"123456","confirmation_url":"https://example.com/auth/confirm","email":"user@example.com"}'::jsonb
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE type = 'auth_recovery' AND deleted_at IS NULL);

-- Magic link
INSERT INTO email_templates (
  name, description, type, subject, html_content, text_content,
  available_variables, is_default, is_active, preview_data
)
SELECT
  'Auth — Magic link',
  'Supabase Send Email hook: magiclink / email OTP',
  'auth_magiclink',
  'Your sign-in link for {{app_name}}',
  $html$
<!DOCTYPE html>
<html>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #111;">
  <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 20px;">Sign in</h1>
    <p>Use the button below to sign in. This link expires shortly.</p>
    <p style="margin: 24px 0;">
      <a href="{{confirmation_url}}" style="display: inline-block; background: #2563eb; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 6px;">Sign in</a>
    </p>
    <p>Or code: <strong>{{token}}</strong></p>
  </div>
</body>
</html>
$html$::text,
  'Open: {{confirmation_url}} Code: {{token}}'::text,
  '["app_name","confirmation_url","token","email"]'::jsonb,
  true, true,
  '{"app_name":"Kre8iv Clients","token":"123456","confirmation_url":"https://example.com/auth/confirm"}'::jsonb
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE type = 'auth_magiclink' AND deleted_at IS NULL);

-- Invite
INSERT INTO email_templates (
  name, description, type, subject, html_content, text_content,
  available_variables, is_default, is_active, preview_data
)
SELECT
  'Auth — Invitation',
  'Supabase Send Email hook: invite',
  'auth_invite',
  'You are invited to {{app_name}}',
  $html$
<!DOCTYPE html>
<html>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #111;">
  <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 20px;">You are invited</h1>
    <p>Accept your invitation to join <strong>{{app_name}}</strong>.</p>
    <p style="margin: 24px 0;">
      <a href="{{confirmation_url}}" style="display: inline-block; background: #2563eb; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 6px;">Accept invite</a>
    </p>
    <p>Code: <strong>{{token}}</strong></p>
  </div>
</body>
</html>
$html$::text,
  'Invite: {{confirmation_url}} Code: {{token}}'::text,
  '["app_name","confirmation_url","token","email"]'::jsonb,
  true, true,
  '{"app_name":"Kre8iv Clients","token":"123456","confirmation_url":"https://example.com/auth/confirm"}'::jsonb
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE type = 'auth_invite' AND deleted_at IS NULL);

-- Generic fallback for rare auth_action types
INSERT INTO email_templates (
  name, description, type, subject, html_content, text_content,
  available_variables, is_default, is_active, preview_data
)
SELECT
  'Auth — Generic',
  'Fallback for notification-style auth emails',
  'auth_generic',
  'Security update for {{app_name}}',
  $html$
<!DOCTYPE html>
<html>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #111;">
  <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
    <p><a href="{{confirmation_url}}">Continue</a></p>
    <p>Code: <strong>{{token}}</strong></p>
  </div>
</body>
</html>
$html$::text,
  '{{confirmation_url}} {{token}}'::text,
  '["app_name","confirmation_url","token","email","email_action_type"]'::jsonb,
  true, true,
  '{}'::jsonb
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE type = 'auth_generic' AND deleted_at IS NULL);

-- Email change (single OTP / non-secure flow)
INSERT INTO email_templates (
  name, description, type, subject, html_content, text_content,
  available_variables, is_default, is_active, preview_data
)
SELECT
  'Auth — Email change',
  'Supabase Send Email hook: email_change (single message)',
  'auth_email_change',
  'Confirm email change for {{app_name}}',
  $html$
<!DOCTYPE html>
<html>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #111;">
  <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
    <p>Confirm this change with the link or code below.</p>
    <p><a href="{{confirmation_url}}">Confirm</a></p>
    <p>Code: <strong>{{token}}</strong></p>
  </div>
</body>
</html>
$html$::text,
  'Confirm: {{confirmation_url}} Code: {{token}}'::text,
  '["confirmation_url","token","email","app_name"]'::jsonb,
  true, true,
  '{}'::jsonb
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE type = 'auth_email_change' AND deleted_at IS NULL);

-- Secure email change — message to current address
INSERT INTO email_templates (
  name, description, type, subject, html_content, text_content,
  available_variables, is_default, is_active, preview_data
)
SELECT
  'Auth — Email change (current address)',
  'Secure email change: sent to existing email',
  'auth_email_change_current',
  'Confirm email change for {{app_name}}',
  $html$
<!DOCTYPE html>
<html>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #111;">
  <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
    <p>Someone requested to change the email on this account.</p>
    <p><a href="{{confirmation_url}}">Confirm from this address</a></p>
    <p>Code: <strong>{{otp}}</strong></p>
  </div>
</body>
</html>
$html$::text,
  'Confirm: {{confirmation_url}} OTP: {{otp}}'::text,
  '["confirmation_url","otp","email","app_name"]'::jsonb,
  true, true,
  '{}'::jsonb
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE type = 'auth_email_change_current' AND deleted_at IS NULL);

-- Secure email change — message to new address
INSERT INTO email_templates (
  name, description, type, subject, html_content, text_content,
  available_variables, is_default, is_active, preview_data
)
SELECT
  'Auth — Email change (new address)',
  'Secure email change: sent to new email',
  'auth_email_change_new',
  'Verify your new email for {{app_name}}',
  $html$
<!DOCTYPE html>
<html>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #111;">
  <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
    <p>Confirm this address to complete your email change.</p>
    <p><a href="{{confirmation_url}}">Verify new email</a></p>
    <p>Code: <strong>{{otp}}</strong></p>
  </div>
</body>
</html>
$html$::text,
  'Verify: {{confirmation_url}} OTP: {{otp}}'::text,
  '["confirmation_url","otp","email","app_name"]'::jsonb,
  true, true,
  '{}'::jsonb
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE type = 'auth_email_change_new' AND deleted_at IS NULL);

-- New service request (API notification)
INSERT INTO email_templates (
  name, description, type, subject, html_content, text_content,
  available_variables, is_default, is_active, preview_data
)
SELECT
  'Notification — New service request',
  'Sent when a service request is created',
  'service_request_created',
  'New request: {{request_title}}',
  $html$
<!DOCTYPE html>
<html>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #111;">
  <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 18px;">New service request</h1>
    <p><strong>{{request_title}}</strong></p>
    <p>Priority: {{request_priority}} · Company: {{company_name}}</p>
    <p>Created by: {{created_by_name}}</p>
    <p><a href="{{request_url}}">Open request</a></p>
  </div>
</body>
</html>
$html$::text,
  'New request: {{request_title}}\n{{request_url}}'::text,
  '["request_title","request_url","request_priority","company_name","created_by_name","app_name"]'::jsonb,
  true, true,
  '{}'::jsonb
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE type = 'service_request_created' AND deleted_at IS NULL);

-- New staff task
INSERT INTO email_templates (
  name, description, type, subject, html_content, text_content,
  available_variables, is_default, is_active, preview_data
)
SELECT
  'Notification — New task',
  'Sent when a Kanban task is created',
  'staff_task_created',
  'New task: {{task_title}}',
  $html$
<!DOCTYPE html>
<html>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #111;">
  <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 18px;">New task</h1>
    <p><strong>{{task_title}}</strong></p>
    <p>Priority: {{task_priority}} · Created by {{created_by_name}}</p>
    <p><a href="{{task_url}}">Open tasks</a></p>
  </div>
</body>
</html>
$html$::text,
  'New task: {{task_title}}\n{{task_url}}'::text,
  '["task_title","task_url","task_priority","created_by_name","app_name"]'::jsonb,
  true, true,
  '{}'::jsonb
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE type = 'staff_task_created' AND deleted_at IS NULL);
