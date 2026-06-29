import { ProjectTemplatePhase } from "@/lib/db/schema/project-templates";

export const wordPressCarePlanTemplate = {
  name: "WordPress Care Plan Checklist",
  description:
    "Recurring WordPress maintenance workflow for updates, backups, security checks, performance review, reporting, and client follow-up.",
  category: "maintenance" as const,
  icon: "Wrench",
  color: "#16a34a",
  estimatedHours: 18,
  isSystem: true,
  metadata: {
    tags: ["wordpress", "maintenance", "care-plan", "security"],
    version: "1.0",
    source: "kre8iv",
  },
  phases: [
    {
      name: "Intake & Access",
      description: "Confirm site access and current support baseline.",
      sortOrder: 0,
      tasks: [
        {
          title: "Verify WordPress and hosting access",
          description: "Confirm admin, hosting, DNS, backup, and analytics access before maintenance begins.",
          priority: "high" as const,
          estimatedHours: 2,
          sortOrder: 0,
          labels: ["access", "wordpress"],
          checklist: [
            { title: "Confirm WordPress administrator login", sortOrder: 0 },
            { title: "Confirm hosting or cPanel access", sortOrder: 1 },
            { title: "Confirm DNS and domain registrar access", sortOrder: 2 },
            { title: "Confirm analytics and Search Console access", sortOrder: 3 },
          ],
        },
        {
          title: "Document current site baseline",
          description: "Capture versions, plugins, theme, active integrations, and known issues.",
          priority: "normal" as const,
          estimatedHours: 2,
          sortOrder: 1,
          labels: ["documentation"],
          checklist: [
            { title: "Record WordPress, theme, and PHP versions", sortOrder: 0 },
            { title: "Export active plugin list", sortOrder: 1 },
            { title: "Capture homepage and key page screenshots", sortOrder: 2 },
            { title: "Log current open issues", sortOrder: 3 },
          ],
        },
      ],
    },
    {
      name: "Monthly Maintenance",
      description: "Perform routine update and quality checks.",
      sortOrder: 1,
      tasks: [
        {
          title: "Run backup and update cycle",
          description: "Create a restore point, update software, and verify the site still behaves correctly.",
          priority: "high" as const,
          estimatedHours: 4,
          sortOrder: 0,
          labels: ["updates", "backup"],
          checklist: [
            { title: "Run full files and database backup", sortOrder: 0 },
            { title: "Update WordPress core when available", sortOrder: 1 },
            { title: "Update plugins and theme", sortOrder: 2 },
            { title: "Smoke test homepage, forms, checkout, and critical pages", sortOrder: 3 },
          ],
        },
        {
          title: "Security and uptime review",
          description: "Review security alerts, suspicious users, malware scans, and uptime incidents.",
          priority: "high" as const,
          estimatedHours: 3,
          sortOrder: 1,
          labels: ["security"],
          checklist: [
            { title: "Run malware/security scan", sortOrder: 0 },
            { title: "Review failed login and firewall activity", sortOrder: 1 },
            { title: "Audit administrator accounts", sortOrder: 2 },
            { title: "Confirm SSL certificate status", sortOrder: 3 },
          ],
        },
        {
          title: "Performance and SEO health check",
          description: "Check speed, broken links, metadata, and indexing signals.",
          priority: "normal" as const,
          estimatedHours: 4,
          sortOrder: 2,
          labels: ["performance", "seo"],
          checklist: [
            { title: "Run performance test on key pages", sortOrder: 0 },
            { title: "Review cache and image optimization status", sortOrder: 1 },
            { title: "Check broken links or 404s", sortOrder: 2 },
            { title: "Review Search Console errors", sortOrder: 3 },
          ],
        },
      ],
    },
    {
      name: "Reporting",
      description: "Summarize maintenance work and next steps.",
      sortOrder: 2,
      tasks: [
        {
          title: "Prepare client maintenance report",
          description: "Document completed work, updates, risks, and recommendations.",
          priority: "normal" as const,
          estimatedHours: 3,
          sortOrder: 0,
          labels: ["reporting"],
          checklist: [
            { title: "Summarize completed updates and checks", sortOrder: 0 },
            { title: "List issues found and resolved", sortOrder: 1 },
            { title: "Add recommendations for next month", sortOrder: 2 },
            { title: "Send report to client", sortOrder: 3 },
          ],
        },
      ],
    },
  ] satisfies ProjectTemplatePhase[],
};
