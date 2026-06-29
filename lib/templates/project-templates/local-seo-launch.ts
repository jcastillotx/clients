import { ProjectTemplatePhase } from "@/lib/db/schema/project-templates";

export const localSeoLaunchTemplate = {
  name: "Local SEO Launch Checklist",
  description:
    "Local SEO setup workflow for Google Business Profile, location pages, on-page optimization, citations, tracking, and launch reporting.",
  category: "seo" as const,
  icon: "Search",
  color: "#0ea5e9",
  estimatedHours: 40,
  isSystem: true,
  metadata: {
    tags: ["seo", "local-seo", "google-business-profile", "launch"],
    version: "1.0",
    source: "kre8iv",
  },
  phases: [
    {
      name: "Audit & Strategy",
      description: "Collect business details and identify local SEO priorities.",
      sortOrder: 0,
      tasks: [
        {
          title: "Collect local SEO intake details",
          description: "Gather service areas, target keywords, business categories, locations, and competitors.",
          priority: "high" as const,
          estimatedHours: 3,
          sortOrder: 0,
          labels: ["intake", "strategy"],
          checklist: [
            { title: "Confirm business name, address, phone, and website", sortOrder: 0 },
            { title: "Confirm service areas and primary services", sortOrder: 1 },
            { title: "Identify top local competitors", sortOrder: 2 },
            { title: "Collect brand and business descriptions", sortOrder: 3 },
          ],
        },
        {
          title: "Keyword and competitor research",
          description: "Build a focused keyword set and review local competitors.",
          priority: "high" as const,
          estimatedHours: 5,
          sortOrder: 1,
          labels: ["keywords"],
          checklist: [
            { title: "Research primary service keywords", sortOrder: 0 },
            { title: "Map keywords to target pages", sortOrder: 1 },
            { title: "Review competitor page structure and backlinks", sortOrder: 2 },
            { title: "Prioritize quick-win opportunities", sortOrder: 3 },
          ],
        },
      ],
    },
    {
      name: "Optimization",
      description: "Build the core local SEO assets.",
      sortOrder: 1,
      tasks: [
        {
          title: "Optimize Google Business Profile",
          description: "Update Google Business Profile fields, services, photos, and conversion actions.",
          priority: "high" as const,
          estimatedHours: 4,
          sortOrder: 0,
          labels: ["gbp"],
          checklist: [
            { title: "Confirm primary and secondary categories", sortOrder: 0 },
            { title: "Update services and business description", sortOrder: 1 },
            { title: "Upload current logo and photos", sortOrder: 2 },
            { title: "Confirm appointment, phone, and website links", sortOrder: 3 },
          ],
        },
        {
          title: "Optimize website local landing pages",
          description: "Improve page titles, headings, copy, internal links, and schema markup.",
          priority: "high" as const,
          estimatedHours: 12,
          sortOrder: 1,
          labels: ["on-page", "schema"],
          checklist: [
            { title: "Write title tags and meta descriptions", sortOrder: 0 },
            { title: "Update H1/H2 structure", sortOrder: 1 },
            { title: "Add location and service copy", sortOrder: 2 },
            { title: "Add LocalBusiness schema", sortOrder: 3 },
            { title: "Add clear calls to action", sortOrder: 4 },
          ],
        },
        {
          title: "Citation and directory cleanup",
          description: "Create or correct core citations with consistent business details.",
          priority: "normal" as const,
          estimatedHours: 8,
          sortOrder: 2,
          labels: ["citations"],
          checklist: [
            { title: "Audit major citation sources", sortOrder: 0 },
            { title: "Correct inconsistent NAP entries", sortOrder: 1 },
            { title: "Create missing priority directory listings", sortOrder: 2 },
            { title: "Document login/access details", sortOrder: 3 },
          ],
        },
      ],
    },
    {
      name: "Tracking & Report",
      description: "Validate tracking and deliver the launch summary.",
      sortOrder: 2,
      tasks: [
        {
          title: "Configure local SEO tracking",
          description: "Confirm analytics, rank tracking, phone/click tracking, and Search Console visibility.",
          priority: "normal" as const,
          estimatedHours: 4,
          sortOrder: 0,
          labels: ["analytics"],
          checklist: [
            { title: "Confirm GA4 conversion events", sortOrder: 0 },
            { title: "Confirm Search Console property", sortOrder: 1 },
            { title: "Set baseline keyword rankings", sortOrder: 2 },
            { title: "Capture baseline GBP metrics", sortOrder: 3 },
          ],
        },
        {
          title: "Send local SEO launch report",
          description: "Summarize completed work, baseline metrics, and next recommendations.",
          priority: "normal" as const,
          estimatedHours: 4,
          sortOrder: 1,
          labels: ["reporting"],
          checklist: [
            { title: "Summarize completed optimizations", sortOrder: 0 },
            { title: "Attach baseline ranking and profile metrics", sortOrder: 1 },
            { title: "List next-month priorities", sortOrder: 2 },
          ],
        },
      ],
    },
  ] satisfies ProjectTemplatePhase[],
};
