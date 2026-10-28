# Client portal readiness and pricing reset — October 28, 2026

## Owner direction
Review admin configuration and integrations; build services/plans around organizational needs. Remove existing prices from portal and public website, then develop competitive Texas pricing. Preserve contractual amounts and history. Replacement prices below are proposals, not approved selling prices.

## Observed live state
- Admin authenticated as Jeremiah Castillo. Empty service catalog and empty form templates.
- Four maintenance templates: Starter $125, Basic $175, Growth $350, Pro $650. All made inactive through the admin UI. Existing subscriptions and financial history unchanged.
- Three marketing software tiers exist as unpublished drafts. Software access is separate from agency services. Do not repurpose the $1,500 public agency retainer as a software price.
- Stripe live key account check passed October 28. This establishes credential acceptance only, not webhook receipt or successful payment.
- Email sending: Microsoft OAuth connected; sender info@kre8ivdesigns.com. The preceding branded service-request test established received submitter and staff mail. Separate integration credential catalog also contains Resend; that does not mean Resend is the selected sender.
- AI: legacy badges claimed Anthropic, OpenAI, Gemini and Stitch verified. Verification implementation only checked nonempty values except Stripe. Replace with actual read-only checks and discard legacy presence-only verification dates.
- Google/Microsoft calendar connections exist with refresh pending. This does not establish free/busy availability. No changes to OAuth grants.
- Social, analytics, marketing platform, Google Drive/Dropbox integration credentials mostly unconfigured. Client-specific OAuth connections must be evaluated separately; global credentials must not be substituted for client consent.
- Storage lists Company S3, kre8ivdesigns-portal, us-east-1, Healthy, never synced. Badge alone does not establish read/write or tenant separation.
- No outbound webhook endpoints configured. Optional outbound webhooks differ from incoming Stripe payment webhooks.
- Security Overview cannot display metrics for an admin without a client association. Do not attach an arbitrary client to hide this empty state.
- Form Templates page is an empty placeholder without creation controls. Public intake remains its own functional form.

## Engineering remediation
- Enforce authenticated client scope and Zod inputs on service proposal requests before database access.
- Quote-only services create draft requests; they cannot be accepted as zero-dollar sent proposals. Sign endpoint already requires sent/viewed status.
- Display Custom quote instead of zero dollars; explain that requesting does not authorize charges.
- Use read-only provider checks with fixed trusted URLs, timeout, no redirects, no secret logging or response-body leakage. Unsupported integrations remain saved/unverified.
- Replace header All Systems Operational with Portal online: process health is not every integration's health.

## Texas market evidence
Observed public asking prices on October 28, 2026. These are a small directional sample, not transaction data or a statewide average. Bundled website subscriptions are not comparable to outright builds; setup charges, commitments, hosting and service hours differ.

| Provider / market | Public signal | Source |
| --- | --- | --- |
| Graphic Gato / San Antonio | Care $149; care/local SEO $350; ads management from $500, ad spend separate | https://graphicgato.com/pricing/ |
| Odyssey Design / San Antonio | Maintenance $150–$550 | https://odysseydesignco.com/product/web-maintenance/ |
| Gigtime / San Antonio | Care $79/$149/$199; hosting included; edit hours and backup frequency differ | https://www.gigtime.com/services/website-maintenance-plans/ |
| Digibot / San Antonio | Five-page upfront build from $2,000; $149/mo site subscription alternative | https://digibotweb.com/ |
| Austin Web Services / Austin | Websites $1,499–$5,000+; SEO $800–$2,500/mo; ads $500–$2,500/mo plus media | https://www.austin-web-services.com/packages |
| Gairy / Dallas–Fort Worth | Build from $2,500; brand plus site $5,000; management from $600/mo | https://www.gairy.co/websites/ |
| Ingalls / Austin | $2,000 launch + $299/mo care, three-month minimum | https://wonderfulwebsite.ai/websites |

## Proposed needs-based pricing — DRAFT
Amounts USD, before applicable tax. No tier published or sold until owner review and operational validation. Scope all third-party costs separately. Hosting sold by Kre8ivHosting is a separate product unless explicitly included.

| Need / offer | Proposed price | Bounded scope |
| --- | --- | --- |
| Website health assessment | $295 one time | Up to 10 URLs, performance/accessibility heuristic review, intake/form inspection, prioritized findings; no compliance certification |
| Brand foundation | $1,500 one time | Discovery, two initial logo directions, two revision rounds, color/type rules, final source files; naming and trademark work excluded |
| Business website | $2,500 one time | Up to five pages, client-supplied approved copy/assets, one standard inquiry form, basic metadata, two review rounds |
| Growth website | $4,500 one time | Up to ten pages, CMS/blog configuration, two standard forms, conversion measurement setup subject to consent and provider access |
| Commerce launch | $6,500 one time | Standard Shopify/WooCommerce setup, up to 20 supplied products, one gateway, standard shipping/tax configuration subject to merchant approval; fees excluded |
| Custom software/integrations | Paid discovery from $750; implementation quoted | Requirements, tenant/access model, integration feasibility and cost estimate; no fixed implementation price before discovery |
| Care Essential | $149/mo per site | Routine updates, backup/restore checks, uptime/security review, 30 minutes minor existing-content changes; one-business-day response target |
| Care Growth | $249/mo per site | Essential plus one total hour of edits, monthly form/performance review and report |
| Care Commerce | $449/mo per site | Growth plus two total hours, staging update review and checkout smoke test; third-party outages and incident cleanup separate |
| Local Visibility | $750/mo/location | GBP review, priority technical/on-page backlog, one content refresh, monthly report; no ranking guarantee |
| Search Growth | $1,500/mo | Local Visibility plus two scoped content deliverables and technical sprint; competitive/multilocation campaigns separately scoped |
| Paid Media Management | $500/mo one channel; $1,000/mo two channels | Account audit, agreed campaign limits, weekly optimization, monthly reporting; media paid directly by client; setup from $500 after audit |
| Creative production | $450/mo | Up to four static assets in agreed formats and two revision rounds; copy/video/print production separately scoped |
| Strategy sprint | $750 one time | Discovery workshop, channel priorities, 90-day action plan and measurement brief |

Care overage: proposed $95/hour, written approval required before additional work. No rollover. Response targets refer to acknowledgement during Monday–Friday business hours in Central Time, not resolution or 24/7 coverage. Month-to-month cancellation policy and onboarding charges require owner approval before publication.

## Economic gate
Use actual loaded labor cost before publishing. Price floor = (delivery labor + allocated infrastructure/tools + payment cost + risk reserve) / (1 - target margin). Illustration only: at $60 loaded labor/hour, 0.5/1/2 edit hours consume $30/$60/$120 before routine maintenance, tools, hosting and support. At a 60% contribution target, $149/$249/$449 allow total delivery costs of $59.60/$99.60/$179.60 respectively. If measured all-in cost exceeds those limits, narrow scope or increase price. No unlimited edits, guaranteed rankings, or unsupported 24/7 promises.

## Client journey
Organization onboarding → choose a need → describe goals/assets/timeline → staff scopes → client reviews written proposal → authorized signature → invoice/payment → project/support/maintenance workspace → reporting. Client-owned integrations need explicit account selection and consent. An admin catalog preview is not proof of client-role authorization; test both tenant access and forbidden cross-tenant paths.
