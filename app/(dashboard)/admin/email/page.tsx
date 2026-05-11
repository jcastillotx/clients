import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { isUserAdmin } from "@/lib/rbac/check";
import { EmailSettings } from "@/components/settings/email-settings";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Mail } from "lucide-react";

export const metadata = {
  title: "Email Settings | Admin | KRE8IV",
  description: "Configure the platform email provider (SMTP, Resend, SendGrid, etc.)",
};

export default async function AdminEmailPage() {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) redirect("/");
  if (!(await isUserAdmin(user.id))) redirect("/dashboard");

  return (
    <div className="flex flex-col gap-8 p-8 max-w-3xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight flex items-center gap-3">
          <Mail className="h-7 w-7 text-primary" />
          Email Settings
        </h1>
        <p className="mt-2 text-muted-foreground">
          Configure the email provider used to send invoices, notifications, and system alerts.
          Settings are encrypted at rest.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Email provider</CardTitle>
          <CardDescription>
            Choose your sending provider and enter the required credentials. Click{" "}
            <strong>Send test</strong> to verify the configuration before saving.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <EmailSettings />
        </CardContent>
      </Card>

      {/* Provider quick-reference */}
      <Card className="border-border/60 bg-muted/30">
        <CardContent className="pt-6">
          <p className="text-sm font-medium mb-3">Provider quick reference</p>
          <div className="space-y-3 text-xs text-muted-foreground">
            <div className="grid grid-cols-[120px_1fr] gap-2 items-start">
              <span className="font-medium text-foreground">Resend</span>
              <span>API key from resend.com/api-keys. Verify your sending domain under Domains.</span>
            </div>
            <div className="grid grid-cols-[120px_1fr] gap-2 items-start">
              <span className="font-medium text-foreground">SendGrid</span>
              <span>API key from app.sendgrid.com → Settings → API Keys. Grant &ldquo;Mail Send&rdquo; permission only.</span>
            </div>
            <div className="grid grid-cols-[120px_1fr] gap-2 items-start">
              <span className="font-medium text-foreground">Mailgun</span>
              <span>API key from app.mailgun.com → API Security. Use your verified sending domain.</span>
            </div>
            <div className="grid grid-cols-[120px_1fr] gap-2 items-start">
              <span className="font-medium text-foreground">Google Workspace</span>
              <span>Connect with Google OAuth, or enable 2FA and generate an App Password for SMTP fallback. Use smtp.gmail.com, port 587, STARTTLS.</span>
            </div>
            <div className="grid grid-cols-[120px_1fr] gap-2 items-start">
              <span className="font-medium text-foreground">Office 365</span>
              <span>Connect with Microsoft OAuth, or enable SMTP AUTH for the mailbox in Exchange admin centre. Use smtp.office365.com, port 587, STARTTLS.</span>
            </div>
            <div className="grid grid-cols-[120px_1fr] gap-2 items-start">
              <span className="font-medium text-foreground">Custom SMTP</span>
              <span>Works with Postmark (smtp.postmarkapp.com:587), Mailjet, Amazon SES, or any self-hosted MTA.</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
