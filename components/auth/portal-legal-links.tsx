import Link from "next/link";

export function PortalLegalLinks() {
  return (
    <section
      className="mt-6 space-y-3 text-center text-sm text-muted-foreground"
      aria-label="About KRE8IV Client Portal"
    >
      <p>
        KRE8IV Client Portal by Kre8ivDesigns Marketing, LLC provides invited
        clients with project collaboration, calendar availability, and
        plan-based marketing tools and connected-account reporting.
      </p>
      <nav
        className="flex flex-wrap justify-center gap-4"
        aria-label="Portal legal and support links"
      >
        <Link className="underline" href="/calendar-privacy">
          Connected account privacy
        </Link>
        <a className="underline" href="https://www.kre8ivdesigns.com/terms/">
          Terms of service
        </a>
        <a className="underline" href="mailto:info@kre8ivdesigns.com">
          Contact support
        </a>
      </nav>
    </section>
  );
}
