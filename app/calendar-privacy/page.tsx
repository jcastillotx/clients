import type { Metadata } from "next";
import Link from "next/link";

export const metadata: Metadata = {
  title: "Calendar Privacy | KRE8IV Client Portal",
  description: "How KRE8IV Client Portal uses connected calendar information.",
};

export default function CalendarPrivacyPage() {
  return (
    <main className="mx-auto max-w-3xl space-y-8 px-6 py-12 text-foreground">
      <header className="space-y-3">
        <p className="text-sm font-semibold text-muted-foreground">KRE8IV Client Portal</p>
        <h1 className="text-3xl font-bold">Connected calendar privacy</h1>
        <p className="text-sm text-muted-foreground">Effective October 27, 2026</p>
        <p>
          Kre8ivDesigns Marketing, LLC operates clients.kre8ivdesigns.com. This
          notice explains the optional Google and Microsoft calendar connections
          used to check availability when scheduling meetings in the portal.
        </p>
      </header>
      <section className="space-y-3" aria-labelledby="calendar-data">
        <h2 id="calendar-data" className="text-xl font-semibold">Information we access</h2>
        <p>
          Google authorization requests read-only access to calendar properties
          and free/busy availability. The portal reads your primary calendar name
          to identify the connection and requests busy time intervals for the
          scheduling range being checked. It does not request permission to read
          Google event contents or to create, edit, or delete events.
        </p>
        <p>
          Microsoft authorization permits basic calendar access. The portal
          requests event start and end times and availability status, without
          requesting event titles, descriptions, attendees, or locations.
          Connecting a calendar does not grant the portal Gmail access.
        </p>
      </section>
      <section className="space-y-3" aria-labelledby="calendar-use">
        <h2 id="calendar-use" className="text-xl font-semibold">How the information is used</h2>
        <p>
          Authorized portal users can check availability for people they are
          allowed to see. They receive a free, busy, or unavailable status and,
          when busy, the relevant time intervals. Calendar data is used for this
          scheduling feature. It is not sold, used for advertising, or used to
          train artificial intelligence models.
        </p>
        <p>
          The portal communicates with Google or Microsoft to authorize the
          connection, refresh its access, and retrieve availability. Our hosting
          and database infrastructure processes the information to operate the
          feature. We do not share connected calendar data for unrelated purposes.
        </p>
        <p>
          KRE8IV Client Portal&apos;s use and transfer of information received from
          Google APIs adheres to the{" "}
          <a className="underline" href="https://developers.google.com/terms/api-services-user-data-policy">
            Google API Services User Data Policy
          </a>, including its Limited Use requirements.
        </p>
      </section>
      <section className="space-y-3" aria-labelledby="calendar-storage">
        <h2 id="calendar-storage" className="text-xl font-semibold">Storage and security</h2>
        <p>
          The portal stores the connection&apos;s provider, calendar identifier and
          name, your portal user identifier, access and refresh tokens, token
          expiry, and connection timestamps. Tokens are encrypted at rest using
          AES-256-GCM. Refresh tokens allow availability checks to continue without
          asking you to sign in for each check. Availability responses are
          processed for the requested scheduling check; the calendar integration
          does not store event contents or a copy of your calendar.
        </p>
      </section>
      <section className="space-y-3" aria-labelledby="calendar-control">
        <h2 id="calendar-control" className="text-xl font-semibold">Your choices and deletion</h2>
        <p>
          Connecting a calendar is optional. Use Disconnect in Settings → Calendar
          to remove the stored connection and its tokens from the active portal
          database. This does not delete or change your Google or Microsoft
          calendar. You can also revoke the app&apos;s access through your provider&apos;s
          account settings. Previously created backups may retain earlier records
          for recovery under our backup retention practices.
        </p>
        <p>
          To ask about your information, request a copy, correction, or deletion,
          contact{" "}
          <a className="underline" href="mailto:info@kre8ivdesigns.com">info@kre8ivdesigns.com</a>.
          We may need to verify your identity before acting on a request.
        </p>
      </section>
      <footer className="flex flex-wrap gap-5 border-t pt-6 text-sm">
        <Link className="underline" href="/login">Portal sign-in</Link>
        <a className="underline" href="https://www.kre8ivdesigns.com/privacy/">Website privacy policy</a>
        <a className="underline" href="https://www.kre8ivdesigns.com/terms/">Terms of service</a>
        <a className="underline" href="https://myaccount.google.com/connections">Manage Google access</a>
      </footer>
    </main>
  );
}
