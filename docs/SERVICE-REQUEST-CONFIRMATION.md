# Public service-request confirmation

The public `/service-request` and `/request-project` forms acknowledge successful
submissions by email after the organization and request have been saved.

The receipt uses the existing portal email branding and configured provider. It
includes the server-generated request reference, review/follow-up expectations,
and the configured support reply address. It does not promise approval, pricing,
timing, or portal account access. Because the supplied address is unverified, the
receipt does not repeat arbitrary public form text or include internal workspace
links. It is sent only to the submitting contact; internal owner/platform alerts
remain a separate message.

CAPTCHA, honeypot, validation, and public-intake rate limits run before persistence
or email. Failed persistence never sends a receipt. Receipt delivery failure does
not undo the saved request or suppress internal alerts. The API returns
`confirmationEmailSent` to indicate provider acceptance; the success screen keeps
the reference and explains an email failure without encouraging resubmission.
Provider acceptance is distinct from recipient receipt, which must be verified in
the mailbox during live end-to-end testing.

No schema migration or new mail-provider configuration is required. The subject
is `Service request received | <brand name>`.
