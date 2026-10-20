# Portal welcome email

Creating a user through `POST /api/admin/users` sends a welcome email after the
auth account, profile, and role assignments have been saved. Updating a user does
not resend it. Self-registration continues to use the existing verification email.

The message uses the configured portal branding and email provider. It includes
the user's email address, a `/login` link, and `/forgot-password` instructions;
it never includes their password. Set `NEXT_PUBLIC_SITE_URL` to the public portal
origin so links point to the correct site.

The API returns `data.welcome_email_sent` to indicate provider acceptance. A failed
send preserves the created account and displays a warning in the user dialog.
Check email settings and use the user's password-reset action to send access
instructions. Provider acceptance does not establish recipient delivery.

The favicon uses the supplied KRE8IV logo (`kd.png`). The original image is
preserved in `public/favicon.png`, with 16px, 32px, and 48px ICO variants and
a 180px Apple touch icon generated from it.

## New client records

Creating a client through `POST /api/clients` sends a separate branded welcome
email to the saved client email address after the record is saved. It uses the
portal logo, brand name, colors, and configured email provider. The message
welcomes the company and explains that the team will follow up with next steps;
creating a client record does not create login access. Editing a client does not
send this message.

The response includes `data.welcome_email_sent` for provider acceptance. The
client form shows a warning if sending fails while preserving the saved client.
Recipient delivery has not been established by this flag.
