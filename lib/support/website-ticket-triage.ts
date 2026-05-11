import type { TicketPriority } from "@/lib/db/schema/support-tickets";

export const websiteSupportPlatformOptions = [
  "WordPress",
  "Elementor",
  "Divi",
  "WooCommerce",
  "Custom Theme",
  "Custom Plugin",
  "Form Plugin",
  "Unknown",
] as const;

export const websiteSupportAffectedAreaOptions = [
  "forms",
  "checkout",
  "login",
  "users",
  "payments",
  "seo",
  "security",
  "database",
  "dns",
  "smtp",
  "woocommerce",
] as const;

export type WebsiteSupportPlatform = (typeof websiteSupportPlatformOptions)[number];
export type WebsiteSupportAffectedArea = (typeof websiteSupportAffectedAreaOptions)[number];
export type WebsiteSupportRiskLevel = "Low Risk" | "Medium Risk" | "High Risk";
export type WebsiteSupportRouting =
  | "Codex eligible"
  | "Codex eligible with human review"
  | "Human developer required"
  | "Client clarification required"
  | "Account or billing review required"
  | "Hosting support required";

export interface WebsiteSupportIntake {
  isWebsiteSupport: boolean;
  clientName?: string | null;
  websiteUrl?: string | null;
  stagingUrl?: string | null;
  affectedPageUrl?: string | null;
  requestedChange?: string | null;
  problemDescription?: string | null;
  deviceAffected?: string | null;
  browserAffected?: string | null;
  urgency?: string | null;
  businessImpact?: string | null;
  platformBuilder?: WebsiteSupportPlatform | null;
  affectedAreas?: WebsiteSupportAffectedArea[];
  areasNotToChange?: string | null;
}

export interface WebsiteSupportTriage {
  ticketTitle: string;
  clientRequestSummary: string;
  website: {
    productionUrl: string | null;
    stagingUrl: string | null;
    affectedPageUrl: string | null;
  };
  priority: TicketPriority;
  riskLevel: WebsiteSupportRiskLevel;
  riskReason: string;
  platformBuilder: WebsiteSupportPlatform;
  problemStatement: string;
  requestedChange: string;
  acceptanceCriteria: string[];
  doNotChange: string[];
  testingInstructions: string[];
  recommendedRouting: WebsiteSupportRouting;
  routingReason: string;
  codexReadyPrompt: string | null;
}

const HIGH_RISK_AREAS = new Set<WebsiteSupportAffectedArea>([
  "checkout",
  "login",
  "users",
  "payments",
  "security",
  "database",
  "dns",
  "smtp",
]);

const MEDIUM_RISK_PLATFORMS = new Set<WebsiteSupportPlatform>([
  "Elementor",
  "Divi",
  "WooCommerce",
  "Custom Theme",
  "Custom Plugin",
  "Form Plugin",
]);

const MEDIUM_RISK_AREAS = new Set<WebsiteSupportAffectedArea>(["forms", "woocommerce"]);

const HIGH_RISK_KEYWORDS = [
  "checkout",
  "stripe",
  "paypal",
  "payment",
  "login",
  "registration",
  "member",
  "membership",
  "security",
  "malware",
  "hacked",
  "database",
  "dns",
  "smtp",
  "email deliverability",
  "redirect",
  "admin user",
  "install plugin",
  "delete plugin",
  "search and replace",
];

const MEDIUM_RISK_KEYWORDS = [
  "elementor",
  "divi",
  "header",
  "footer",
  "form",
  "notification",
  "woocommerce",
  "template",
  "custom post type",
  "hook",
  "javascript",
  "responsive section",
];

export function buildWebsiteSupportTriage(input: {
  subject: string;
  description: string;
  priority: TicketPriority;
  intake: WebsiteSupportIntake;
}): WebsiteSupportTriage {
  const intake = normalizeWebsiteSupportIntake(input.intake);
  const siteName = getSiteName(intake);
  const problemStatement = getPreferredText(intake.problemDescription, input.description);
  const requestedChange = getPreferredText(intake.requestedChange, input.subject);
  const priority = classifyPriority(input.priority, intake);
  const { riskLevel, riskReason } = classifyRisk(intake, problemStatement, requestedChange);
  const platformBuilder = intake.platformBuilder || "Unknown";
  const acceptanceCriteria = buildAcceptanceCriteria(requestedChange, intake, riskLevel);
  const doNotChange = buildDoNotChangeList(intake, riskLevel);
  const testingInstructions = buildTestingInstructions(intake, riskLevel);
  const { recommendedRouting, routingReason } = classifyRouting(riskLevel, intake);
  const ticketTitle = `[${siteName}] ${input.subject.trim()}`;

  const triage: Omit<WebsiteSupportTriage, "codexReadyPrompt"> = {
    ticketTitle,
    clientRequestSummary: summarizeRequest(requestedChange, problemStatement, intake),
    website: {
      productionUrl: intake.websiteUrl || null,
      stagingUrl: intake.stagingUrl || null,
      affectedPageUrl: intake.affectedPageUrl || null,
    },
    priority,
    riskLevel,
    riskReason,
    platformBuilder,
    problemStatement,
    requestedChange,
    acceptanceCriteria,
    doNotChange,
    testingInstructions,
    recommendedRouting,
    routingReason,
  };

  return {
    ...triage,
    codexReadyPrompt: riskLevel === "High Risk" ? null : buildCodexReadyPrompt(triage),
  };
}

export function normalizeWebsiteSupportIntake(intake: WebsiteSupportIntake): WebsiteSupportIntake {
  return {
    ...intake,
    clientName: cleanNullable(intake.clientName),
    websiteUrl: cleanNullable(intake.websiteUrl),
    stagingUrl: cleanNullable(intake.stagingUrl),
    affectedPageUrl: cleanNullable(intake.affectedPageUrl),
    requestedChange: cleanNullable(intake.requestedChange),
    problemDescription: cleanNullable(intake.problemDescription),
    deviceAffected: cleanNullable(intake.deviceAffected),
    browserAffected: cleanNullable(intake.browserAffected),
    urgency: cleanNullable(intake.urgency),
    businessImpact: cleanNullable(intake.businessImpact),
    platformBuilder: intake.platformBuilder || "Unknown",
    affectedAreas: Array.isArray(intake.affectedAreas) ? intake.affectedAreas : [],
    areasNotToChange: cleanNullable(intake.areasNotToChange),
  };
}

function classifyRisk(
  intake: WebsiteSupportIntake,
  problemStatement: string,
  requestedChange: string,
): Pick<WebsiteSupportTriage, "riskLevel" | "riskReason"> {
  const affectedAreas = new Set(intake.affectedAreas || []);
  const combinedText = `${problemStatement} ${requestedChange} ${intake.businessImpact || ""}`.toLowerCase();

  if (
    [...affectedAreas].some((area) => HIGH_RISK_AREAS.has(area)) ||
    HIGH_RISK_KEYWORDS.some((keyword) => combinedText.includes(keyword))
  ) {
    return {
      riskLevel: "High Risk",
      riskReason:
        "This request may affect revenue, access, data, security, DNS, email delivery, or production-critical WordPress behavior.",
    };
  }

  if (
    (intake.platformBuilder && MEDIUM_RISK_PLATFORMS.has(intake.platformBuilder)) ||
    [...affectedAreas].some((area) => MEDIUM_RISK_AREAS.has(area)) ||
    MEDIUM_RISK_KEYWORDS.some((keyword) => combinedText.includes(keyword))
  ) {
    return {
      riskLevel: "Medium Risk",
      riskReason:
        "This request appears to involve a builder, template, form, WooCommerce display, custom code, or JavaScript/responsive behavior.",
    };
  }

  return {
    riskLevel: "Low Risk",
    riskReason: "This appears limited to content, links, images, basic SEO text, alt text, or minor visual adjustments.",
  };
}

function classifyPriority(priority: TicketPriority, intake: WebsiteSupportIntake): TicketPriority {
  const text = `${intake.urgency || ""} ${intake.businessImpact || ""}`.toLowerCase();

  if (priority === "urgent" || text.includes("site down") || text.includes("cannot take payment")) {
    return "urgent";
  }

  if (priority === "high" || text.includes("revenue") || text.includes("launch") || text.includes("blocked")) {
    return "high";
  }

  if (priority === "low" && (text.includes("cosmetic") || text.includes("no rush"))) {
    return "low";
  }

  return priority || "medium";
}

function classifyRouting(
  riskLevel: WebsiteSupportRiskLevel,
  intake: WebsiteSupportIntake,
): Pick<WebsiteSupportTriage, "recommendedRouting" | "routingReason"> {
  if (!intake.websiteUrl && !intake.affectedPageUrl) {
    return {
      recommendedRouting: "Client clarification required",
      routingReason: "A production URL or affected page URL is needed before work can be scoped safely.",
    };
  }

  if ((intake.affectedAreas || []).some((area) => area === "dns" || area === "smtp")) {
    return {
      recommendedRouting: "Hosting support required",
      routingReason: "DNS or SMTP changes need hosting/account-level review before implementation.",
    };
  }

  if (riskLevel === "High Risk") {
    return {
      recommendedRouting: "Human developer required",
      routingReason:
        "Codex can analyze and draft recommendations, but production-impacting changes require human review, backup, staging validation, and approval.",
    };
  }

  if (riskLevel === "Medium Risk") {
    return {
      recommendedRouting: "Codex eligible with human review",
      routingReason: "Codex may draft the change, but builder/template/custom-code work requires human review and staging validation.",
    };
  }

  return {
    recommendedRouting: "Codex eligible",
    routingReason: "This is a low-risk website change suitable for a small version-controlled Codex branch and staging review.",
  };
}

function buildAcceptanceCriteria(
  requestedChange: string,
  intake: WebsiteSupportIntake,
  riskLevel: WebsiteSupportRiskLevel,
): string[] {
  const criteria = [
    requestedChange,
    "Layout remains clean on desktop and mobile.",
    "No unrelated page sections are changed.",
    "Change is verified on staging before production.",
  ];

  if (intake.affectedPageUrl) {
    criteria.unshift(`Affected page is updated: ${intake.affectedPageUrl}`);
  }

  if (intake.deviceAffected && intake.deviceAffected !== "unknown") {
    criteria.push(`Issue is verified on ${intake.deviceAffected}.`);
  }

  if (riskLevel !== "Low Risk") {
    criteria.push("Human review is completed before production approval.");
  }

  return criteria;
}

function buildDoNotChangeList(intake: WebsiteSupportIntake, riskLevel: WebsiteSupportRiskLevel): string[] {
  const items = [
    "Do not edit production directly.",
    "Do not modify WordPress core.",
    "Do not modify parent theme files.",
    "Do not modify uploads, cache, secrets, database dumps, or wp-config.php.",
    "Do not install or remove plugins.",
  ];

  if (intake.areasNotToChange) {
    items.unshift(intake.areasNotToChange);
  }

  if (riskLevel !== "High Risk") {
    items.push("Do not change checkout, payments, login, users, security, database, DNS, or SMTP.");
  }

  return items;
}

function buildTestingInstructions(intake: WebsiteSupportIntake, riskLevel: WebsiteSupportRiskLevel): string[] {
  const instructions = [
    "Review the affected page on staging.",
    "Test desktop and mobile layouts.",
    "Confirm links, forms, and visible interactions on the affected page still work.",
    "Clear cache and retest.",
    "Capture screenshots before production release when the change is visual.",
  ];

  if (intake.browserAffected && intake.browserAffected !== "unknown") {
    instructions.unshift(`Retest in ${intake.browserAffected}.`);
  }

  if (riskLevel === "High Risk") {
    instructions.unshift("Confirm a backup exists before any deployment.");
  }

  return instructions;
}

function buildCodexReadyPrompt(triage: Omit<WebsiteSupportTriage, "codexReadyPrompt">): string {
  return `You are working on a WordPress support ticket for Kre8ivDesigns.

Site:
${triage.website.productionUrl || "[site URL]"}

Ticket:
${triage.ticketTitle}

Problem:
${triage.problemStatement}

Requested Change:
${triage.requestedChange}

Risk Level:
${triage.riskLevel.toLowerCase()}

Rules:
- Do not edit production directly.
- Work only in version-controlled files.
- Make the smallest effective change.
- Do not modify WordPress core.
- Do not modify parent theme files.
- Do not modify uploads, cache, secrets, database dumps, or wp-config.php.
- Do not install or remove plugins.
- If this affects checkout, payments, login, users, security, database, DNS, or SMTP, stop and recommend human review.
- Prefer child theme, custom plugin, mu-plugin, CSS, JavaScript, template, or builder export-based changes.
- For Elementor or Divi layout changes, prepare the change for staging review.
- Include testing steps and rollback steps.

Acceptance Criteria:
${formatPromptList(triage.acceptanceCriteria)}

Do Not Change:
${formatPromptList(triage.doNotChange)}

Testing:
${formatPromptList(triage.testingInstructions)}

Deliverable:
Create a branch and pull request with:
- Summary of change
- Files changed
- Risk level
- Testing performed
- Screenshots if visual
- Rollback plan`;
}

function summarizeRequest(requestedChange: string, problemStatement: string, intake: WebsiteSupportIntake): string {
  const location = intake.affectedPageUrl || intake.websiteUrl;
  if (location) {
    return `Client is asking for website support on ${location}: ${requestedChange}`;
  }

  return `${requestedChange} ${problemStatement}`.trim();
}

function getSiteName(intake: WebsiteSupportIntake): string {
  if (intake.clientName) {
    return intake.clientName;
  }

  if (!intake.websiteUrl) {
    return "Website";
  }

  try {
    return new URL(intake.websiteUrl).hostname.replace(/^www\./, "");
  } catch {
    return intake.websiteUrl;
  }
}

function getPreferredText(value: string | null | undefined, fallback: string): string {
  return value?.trim() || fallback.trim();
}

function cleanNullable(value: string | null | undefined): string | null {
  const cleaned = value?.trim();
  return cleaned ? cleaned : null;
}

function formatPromptList(items: string[]): string {
  return items.map((item) => `- ${item}`).join("\n");
}
