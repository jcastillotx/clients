import { ProjectTemplatePhase } from "@/lib/db/schema/project-templates";

export const websiteRedesignSprintTemplate = {
  name: "Website Redesign Sprint",
  description:
    "Fast redesign workflow for auditing an existing website, refreshing core pages, QA testing, and launching updates.",
  category: "web_development" as const,
  icon: "Rocket",
  color: "#7c3aed",
  estimatedHours: 64,
  isSystem: true,
  metadata: {
    tags: ["website", "redesign", "sprint", "launch"],
    version: "1.0",
    source: "kre8iv",
  },
  phases: [
    {
      name: "Audit & Plan",
      description: "Assess the existing site and lock the redesign scope.",
      sortOrder: 0,
      tasks: [
        {
          title: "Existing website audit",
          description: "Review content, structure, design, performance, analytics, and conversion points.",
          priority: "high" as const,
          estimatedHours: 5,
          sortOrder: 0,
          labels: ["audit"],
          checklist: [
            { title: "Audit top traffic pages", sortOrder: 0 },
            { title: "Review navigation and page hierarchy", sortOrder: 1 },
            { title: "Identify outdated or duplicate content", sortOrder: 2 },
            { title: "Capture design and UX issues", sortOrder: 3 },
          ],
        },
        {
          title: "Redesign scope and sitemap",
          description: "Define the pages, content updates, and visual direction included in the sprint.",
          priority: "high" as const,
          estimatedHours: 4,
          sortOrder: 1,
          labels: ["planning"],
          checklist: [
            { title: "Confirm pages included in redesign", sortOrder: 0 },
            { title: "Define content owner for each page", sortOrder: 1 },
            { title: "Approve visual direction", sortOrder: 2 },
            { title: "Set launch target date", sortOrder: 3 },
          ],
        },
      ],
    },
    {
      name: "Design & Build",
      description: "Create updated layouts and implement approved changes.",
      sortOrder: 1,
      tasks: [
        {
          title: "Homepage redesign",
          description: "Refresh homepage structure, messaging, visuals, and conversion paths.",
          priority: "high" as const,
          estimatedHours: 12,
          sortOrder: 0,
          labels: ["design", "build"],
          checklist: [
            { title: "Update hero section and primary CTA", sortOrder: 0 },
            { title: "Refresh services or offer sections", sortOrder: 1 },
            { title: "Add trust proof and testimonials", sortOrder: 2 },
            { title: "Build responsive mobile layout", sortOrder: 3 },
          ],
        },
        {
          title: "Interior page updates",
          description: "Refresh the agreed interior pages and reusable sections.",
          priority: "high" as const,
          estimatedHours: 18,
          sortOrder: 1,
          labels: ["content", "build"],
          checklist: [
            { title: "Update about/company page", sortOrder: 0 },
            { title: "Update services or product pages", sortOrder: 1 },
            { title: "Update contact page and forms", sortOrder: 2 },
            { title: "Update header, footer, and shared components", sortOrder: 3 },
          ],
        },
        {
          title: "Content and media pass",
          description: "Clean up copy, imagery, links, and supporting assets.",
          priority: "normal" as const,
          estimatedHours: 8,
          sortOrder: 2,
          labels: ["content"],
          checklist: [
            { title: "Replace outdated images", sortOrder: 0 },
            { title: "Proofread key pages", sortOrder: 1 },
            { title: "Optimize image sizes and alt text", sortOrder: 2 },
            { title: "Confirm forms and contact details", sortOrder: 3 },
          ],
        },
      ],
    },
    {
      name: "QA & Launch",
      description: "Test the updated site and launch approved changes.",
      sortOrder: 2,
      tasks: [
        {
          title: "Responsive and browser QA",
          description: "Test the redesign across desktop, tablet, mobile, and key browsers.",
          priority: "high" as const,
          estimatedHours: 6,
          sortOrder: 0,
          labels: ["qa"],
          checklist: [
            { title: "Test desktop and mobile breakpoints", sortOrder: 0 },
            { title: "Test Chrome, Safari, and Firefox", sortOrder: 1 },
            { title: "Verify buttons, links, and forms", sortOrder: 2 },
            { title: "Fix visual regressions", sortOrder: 3 },
          ],
        },
        {
          title: "Launch and handoff",
          description: "Publish changes, verify production, and send handoff notes.",
          priority: "high" as const,
          estimatedHours: 7,
          sortOrder: 1,
          labels: ["launch"],
          checklist: [
            { title: "Back up production site", sortOrder: 0 },
            { title: "Deploy approved redesign", sortOrder: 1 },
            { title: "Run post-launch smoke test", sortOrder: 2 },
            { title: "Send client launch summary", sortOrder: 3 },
          ],
        },
      ],
    },
  ] satisfies ProjectTemplatePhase[],
};
