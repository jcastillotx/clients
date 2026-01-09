<?php

namespace Database\Seeders;

use App\Models\StaffGuide;
use App\Models\StaffGuideCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StaffGuidesSeeder extends Seeder
{
    public function run(): void
    {
        // Create Categories
        $seoCategory = StaffGuideCategory::firstOrCreate(
            ['slug' => 'seo-services'],
            [
                'name' => 'SEO Services',
                'icon' => 'fas fa-search-dollar',
                'description' => 'How-to guides and checklists for SEO service delivery',
                'sort_order' => 1,
            ]
        );

        $onboardingCategory = StaffGuideCategory::firstOrCreate(
            ['slug' => 'client-onboarding'],
            [
                'name' => 'Client Onboarding',
                'icon' => 'fas fa-user-check',
                'description' => 'Guides for onboarding new SEO clients',
                'sort_order' => 2,
            ]
        );

        $addOnsCategory = StaffGuideCategory::firstOrCreate(
            ['slug' => 'add-on-services'],
            [
                'name' => 'Add-On Services',
                'icon' => 'fas fa-plus-circle',
                'description' => 'Upsell and add-on service delivery guides',
                'sort_order' => 3,
            ]
        );

        // =====================================================
        // LOCAL SEO FOUNDATION - $2,250/month
        // =====================================================
        StaffGuide::firstOrCreate(
            ['slug' => 'local-seo-foundation-service-guide'],
            [
                'category_id' => $seoCategory->id,
                'title' => 'Local SEO Foundation Service Guide',
                'summary' => 'Complete delivery guide for Local SEO Foundation package ($2,250/month). Built for local service businesses, solo operators, and early growth companies.',
                'service_tier' => 'local_seo',
                'price' => 2250.00,
                'commitment' => '3-month minimum',
                'is_published' => true,
                'published_at' => now(),
                'content' => $this->getLocalSeoContent(),
                'checklist' => $this->getLocalSeoChecklist(),
            ]
        );

        // =====================================================
        // GROWTH SEO - $3,750/month
        // =====================================================
        StaffGuide::firstOrCreate(
            ['slug' => 'growth-seo-service-guide'],
            [
                'category_id' => $seoCategory->id,
                'title' => 'Growth SEO Service Guide',
                'summary' => 'Complete delivery guide for Growth SEO package ($3,750/month). This is the core revenue package - most clients should land here.',
                'service_tier' => 'growth_seo',
                'price' => 3750.00,
                'commitment' => '3-month minimum',
                'is_published' => true,
                'published_at' => now(),
                'content' => $this->getGrowthSeoContent(),
                'checklist' => $this->getGrowthSeoChecklist(),
            ]
        );

        // =====================================================
        // AUTHORITY SEO - $6,500/month
        // =====================================================
        StaffGuide::firstOrCreate(
            ['slug' => 'authority-seo-service-guide'],
            [
                'category_id' => $seoCategory->id,
                'title' => 'Authority SEO Service Guide',
                'summary' => 'Complete delivery guide for Authority SEO package ($6,500/month). For companies that want to own search, not dabble.',
                'service_tier' => 'authority_seo',
                'price' => 6500.00,
                'commitment' => '6-month minimum',
                'is_published' => true,
                'published_at' => now(),
                'content' => $this->getAuthoritySeoContent(),
                'checklist' => $this->getAuthoritySeoChecklist(),
            ]
        );

        // =====================================================
        // SEO ONBOARDING & DEEP AUDIT - $1,750 one-time
        // =====================================================
        StaffGuide::firstOrCreate(
            ['slug' => 'seo-onboarding-deep-audit-guide'],
            [
                'category_id' => $onboardingCategory->id,
                'title' => 'SEO Onboarding & Deep Audit Guide',
                'summary' => 'Required one-time onboarding for all new SEO clients ($1,750). Includes full technical audit, competitor benchmark, and 90-day roadmap.',
                'service_tier' => 'onboarding',
                'price' => 1750.00,
                'commitment' => 'One-time fee',
                'is_published' => true,
                'published_at' => now(),
                'content' => $this->getOnboardingContent(),
                'checklist' => $this->getOnboardingChecklist(),
            ]
        );

        // =====================================================
        // ADD-ON SERVICES
        // =====================================================
        StaffGuide::firstOrCreate(
            ['slug' => 'additional-seo-content-guide'],
            [
                'category_id' => $addOnsCategory->id,
                'title' => 'Additional SEO Content Delivery',
                'summary' => 'Guide for delivering additional SEO content beyond package inclusions ($450 per article).',
                'service_tier' => 'add_on',
                'price' => 450.00,
                'commitment' => 'Per article',
                'is_published' => true,
                'published_at' => now(),
                'content' => $this->getAdditionalContentGuide(),
                'checklist' => $this->getAdditionalContentChecklist(),
            ]
        );

        StaffGuide::firstOrCreate(
            ['slug' => 'advanced-link-building-guide'],
            [
                'category_id' => $addOnsCategory->id,
                'title' => 'Advanced Link Building Campaign',
                'summary' => 'Guide for executing advanced link building campaigns ($1,500 - $4,000/month based on scope).',
                'service_tier' => 'add_on',
                'price' => 1500.00,
                'commitment' => 'Monthly',
                'is_published' => true,
                'published_at' => now(),
                'content' => $this->getLinkBuildingContent(),
                'checklist' => $this->getLinkBuildingChecklist(),
            ]
        );

        StaffGuide::firstOrCreate(
            ['slug' => 'multi-location-seo-guide'],
            [
                'category_id' => $addOnsCategory->id,
                'title' => 'Multi-Location SEO Guide',
                'summary' => 'Guide for managing SEO for additional business locations ($500 per location/month).',
                'service_tier' => 'add_on',
                'price' => 500.00,
                'commitment' => 'Per location/month',
                'is_published' => true,
                'published_at' => now(),
                'content' => $this->getMultiLocationContent(),
                'checklist' => $this->getMultiLocationChecklist(),
            ]
        );

        StaffGuide::firstOrCreate(
            ['slug' => 'cro-audit-guide'],
            [
                'category_id' => $addOnsCategory->id,
                'title' => 'Conversion Rate Optimization Audit',
                'summary' => 'One-time CRO audit to improve conversion rates on SEO landing pages ($1,200 one-time).',
                'service_tier' => 'add_on',
                'price' => 1200.00,
                'commitment' => 'One-time',
                'is_published' => true,
                'published_at' => now(),
                'content' => $this->getCroAuditContent(),
                'checklist' => $this->getCroAuditChecklist(),
            ]
        );

        StaffGuide::firstOrCreate(
            ['slug' => 'technical-seo-cleanup-sprint'],
            [
                'category_id' => $addOnsCategory->id,
                'title' => 'Technical SEO Cleanup Sprint',
                'summary' => 'Intensive technical SEO cleanup project ($1,500 - $3,500 based on scope).',
                'service_tier' => 'add_on',
                'price' => 1500.00,
                'commitment' => 'One-time (scope-based)',
                'is_published' => true,
                'published_at' => now(),
                'content' => $this->getTechnicalCleanupContent(),
                'checklist' => $this->getTechnicalCleanupChecklist(),
            ]
        );
    }

    // =========================================================================
    // LOCAL SEO FOUNDATION CONTENT
    // =========================================================================
    private function getLocalSeoContent(): string
    {
        return <<<'CONTENT'
LOCAL SEO FOUNDATION SERVICE GUIDE
$2,250/month | 3-month minimum commitment

OVERVIEW
Built for local service businesses, solo operators, and early growth companies looking to establish a strong local search presence.

BEST FIT CLIENTS
• Local businesses
• Contractors (plumbers, electricians, HVAC, etc.)
• Medical practices
• Legal firms
• Restaurants
• Nonprofits

MONTHLY DELIVERABLES

1. Technical SEO Baseline Audit (Month 1 Only)
   - One-time comprehensive technical audit
   - Site health assessment
   - Core Web Vitals review
   - Mobile responsiveness check
   - Indexation status review

2. Local Keyword Research (10-15 Keywords)
   - Focus on local intent keywords
   - Service + location combinations
   - "Near me" variations
   - Competitor keyword analysis

3. Google Business Profile Optimization
   - Complete profile setup/optimization
   - Category selection and optimization
   - Service area configuration
   - Photo optimization
   - Post scheduling (weekly)
   - Q&A management
   - Review response strategy

4. On-Page Optimization (Up to 5 Pages)
   - Title tag optimization
   - Meta description optimization
   - Header tag structure (H1, H2, H3)
   - Internal linking
   - Schema markup implementation
   - NAP consistency

5. Local Citation Cleanup & Management
   - Top directory submissions
   - NAP consistency audit
   - Duplicate listing removal
   - Major platforms: Google, Yelp, Facebook, BBB, industry-specific

6. Monthly Performance Report
   - Keyword ranking changes
   - Traffic analysis
   - GBP insights
   - Citation status
   - Next month priorities

7. Basic Competitor Visibility Tracking
   - Monitor 3-5 local competitors
   - Track their ranking changes
   - Identify opportunities

TOOLS TO USE
• Google Search Console
• Google Business Profile Manager
• Semrush or Ahrefs (keyword tracking)
• BrightLocal or Whitespark (citations)
• Screaming Frog (technical audits)

COMMUNICATION CADENCE
• Monthly email report
• Quarterly check-in call (optional)
• Responsive to client questions within 24-48 hours
CONTENT;
    }

    private function getLocalSeoChecklist(): array
    {
        return [
            ['title' => 'Complete Technical SEO Baseline Audit', 'description' => 'Run Screaming Frog crawl, check GSC for errors, review Core Web Vitals', 'notes' => 'Month 1 only'],
            ['title' => 'Conduct Local Keyword Research', 'description' => 'Identify 10-15 local keywords with service + location combinations'],
            ['title' => 'Optimize Google Business Profile', 'description' => 'Complete all profile fields, add photos, set up posts schedule'],
            ['title' => 'Verify NAP Consistency', 'description' => 'Ensure Name, Address, Phone match across all platforms'],
            ['title' => 'Optimize Homepage', 'description' => 'Title tag, meta description, H1, schema markup'],
            ['title' => 'Optimize Service Pages (up to 5)', 'description' => 'On-page optimization for primary service pages'],
            ['title' => 'Submit to Top Citations', 'description' => 'Google, Yelp, Facebook, BBB, and industry directories'],
            ['title' => 'Clean Up Duplicate Listings', 'description' => 'Find and remove/merge duplicate business listings'],
            ['title' => 'Set Up Competitor Tracking', 'description' => 'Add 3-5 local competitors to ranking tracker'],
            ['title' => 'Create Monthly Report', 'description' => 'Rankings, traffic, GBP insights, citations, priorities'],
            ['title' => 'Schedule GBP Posts', 'description' => 'Plan weekly Google Business Profile posts'],
            ['title' => 'Monitor and Respond to Reviews', 'description' => 'Set up alerts and response templates'],
        ];
    }

    // =========================================================================
    // GROWTH SEO CONTENT
    // =========================================================================
    private function getGrowthSeoContent(): string
    {
        return <<<'CONTENT'
GROWTH SEO SERVICE GUIDE
$3,750/month | 3-month minimum commitment

OVERVIEW
This is the core revenue package. Most clients should land here. Designed for growing SMBs in competitive local markets and regional service providers.

BEST FIT CLIENTS
• Growing SMBs
• Competitive local markets
• Regional service providers
• Businesses ready to scale their online presence

INCLUDES EVERYTHING IN LOCAL SEO FOUNDATION, PLUS:

1. Expanded Keyword Strategy (25-40 Keywords)
   - Broader keyword targeting
   - Long-tail opportunities
   - Question-based keywords
   - Seasonal variations
   - Competitor gap analysis

2. On-Page Optimization (Up to 10 Pages)
   - All service pages
   - Location pages
   - Key landing pages
   - Blog optimization

3. Monthly SEO Content Creation (2 Pieces)
   - Optimized blog articles OR
   - Landing pages
   - 1,000-1,500 words each
   - Keyword-targeted
   - Internal linking included

4. Internal Linking Strategy
   - Silo structure planning
   - Contextual link building
   - Navigation optimization
   - Breadcrumb implementation

5. Technical SEO Fixes & Monitoring
   - Ongoing technical health monitoring
   - Fix crawl errors
   - Improve page speed
   - Mobile optimization
   - Schema expansion

6. Entry-Level Link Acquisition
   - Citation building (continued)
   - Foundational backlinks
   - Local sponsorships/partnerships
   - Guest posting (1-2/month)

7. Conversion Optimization Recommendations
   - CTA placement suggestions
   - Form optimization tips
   - User flow improvements
   - Landing page recommendations

8. Monthly Strategy Call + Reporting
   - 30-minute monthly call
   - Detailed performance report
   - Strategy discussion
   - Q&A session

CONTENT CALENDAR
Week 1: Content planning and keyword assignment
Week 2: First content piece creation
Week 3: Second content piece creation
Week 4: Optimization, internal linking, reporting

TOOLS TO USE
• Google Search Console
• Google Analytics 4
• Semrush or Ahrefs
• BrightLocal
• Screaming Frog
• Surfer SEO (content optimization)
• Google Looker Studio (reporting)

COMMUNICATION CADENCE
• Monthly strategy call (30 min)
• Monthly detailed report
• Email updates as needed
• 24-48 hour response time
CONTENT;
    }

    private function getGrowthSeoChecklist(): array
    {
        return [
            ['title' => 'Complete All Local SEO Foundation Tasks', 'description' => 'Ensure baseline tasks from Local SEO are maintained'],
            ['title' => 'Expand Keyword Research to 25-40 Keywords', 'description' => 'Include long-tail, questions, and seasonal variations'],
            ['title' => 'Create Content Calendar', 'description' => 'Plan 2 content pieces per month with target keywords'],
            ['title' => 'Write First Content Piece', 'description' => '1,000-1,500 word optimized article or landing page'],
            ['title' => 'Write Second Content Piece', 'description' => '1,000-1,500 word optimized article or landing page'],
            ['title' => 'Optimize Up to 10 Pages', 'description' => 'Expand on-page optimization beyond initial 5 pages'],
            ['title' => 'Implement Internal Linking Strategy', 'description' => 'Create silo structure, add contextual links'],
            ['title' => 'Monitor Technical SEO Health', 'description' => 'Weekly GSC check, fix errors promptly'],
            ['title' => 'Execute Link Acquisition', 'description' => 'Citations, foundational links, 1-2 guest posts'],
            ['title' => 'Provide Conversion Recommendations', 'description' => 'Document CTA and UX improvement suggestions'],
            ['title' => 'Conduct Monthly Strategy Call', 'description' => '30-minute call to review performance and strategy'],
            ['title' => 'Deliver Monthly Report', 'description' => 'Comprehensive report with rankings, traffic, content performance'],
        ];
    }

    // =========================================================================
    // AUTHORITY SEO CONTENT
    // =========================================================================
    private function getAuthoritySeoContent(): string
    {
        return <<<'CONTENT'
AUTHORITY SEO SERVICE GUIDE
$6,500/month | 6-month minimum commitment

OVERVIEW
For companies that want to OWN search, not dabble. Premium service tier for serious businesses in competitive markets.

BEST FIT CLIENTS
• Multi-location businesses
• E-commerce companies
• High-competition industries
• Funded startups
• Businesses targeting regional/national reach

INCLUDES EVERYTHING IN GROWTH SEO, PLUS:

1. Advanced Competitor & SERP Analysis
   - Deep competitor backlink analysis
   - Content gap analysis
   - SERP feature opportunities
   - Featured snippet targeting
   - People Also Ask optimization

2. Content Cluster Strategy
   - Pillar page development
   - Supporting content mapping
   - Topic authority building
   - Content hub creation

3. Monthly Content Production (4 Long-Form Assets)
   - 4 pieces of premium content
   - 1,500-2,500 words each
   - Comprehensive topic coverage
   - Original research when applicable
   - Infographics/visual assets

4. Strategic Link Building Campaign
   - High-authority placements
   - Digital PR opportunities
   - Industry publication outreach
   - Resource link building
   - Broken link building

5. Technical SEO Roadmap Execution
   - Complete technical overhaul
   - Site architecture optimization
   - Advanced schema implementation
   - International SEO (if applicable)
   - JavaScript rendering fixes

6. UX and CRO Alignment for SEO Pages
   - Heat map analysis
   - User flow optimization
   - A/B testing recommendations
   - Page speed optimization
   - Mobile UX improvements

7. Multi-Location or Regional SEO
   - Location page strategy
   - Individual GBP management
   - Location-specific content
   - Local link building per location

8. Priority Support and Reporting
   - Dedicated account manager
   - Weekly check-ins available
   - Priority response (same-day)
   - Custom reporting dashboards
   - Quarterly business reviews

STRATEGIC APPROACH
Month 1-2: Foundation & Analysis
- Deep audit completion
- Competitor analysis
- Content cluster mapping
- Technical roadmap creation

Month 3-4: Execution & Building
- Content production ramp-up
- Link building campaign launch
- Technical fixes implementation
- UX improvements

Month 5-6: Optimization & Scaling
- Performance analysis
- Strategy refinement
- Scaling successful tactics
- ROI documentation

TOOLS TO USE
• Full Semrush/Ahrefs suite
• Screaming Frog
• Google Search Console
• Google Analytics 4
• Hotjar or Microsoft Clarity
• Surfer SEO
• Pitchbox or BuzzStream (outreach)
• Google Looker Studio

COMMUNICATION CADENCE
• Weekly check-ins (15 min)
• Monthly strategy call (45 min)
• Quarterly business review (1 hour)
• Same-day response for urgent matters
• Custom Slack channel (optional)
CONTENT;
    }

    private function getAuthoritySeoChecklist(): array
    {
        return [
            ['title' => 'Complete All Growth SEO Tasks', 'description' => 'Maintain all deliverables from Growth tier'],
            ['title' => 'Conduct Advanced Competitor Analysis', 'description' => 'Deep dive into competitor backlinks, content, SERP features'],
            ['title' => 'Develop Content Cluster Strategy', 'description' => 'Map pillar pages and supporting content topics'],
            ['title' => 'Create 4 Long-Form Content Assets', 'description' => '1,500-2,500 words each, comprehensive coverage'],
            ['title' => 'Execute Strategic Link Building', 'description' => 'High-authority placements, digital PR, resource links'],
            ['title' => 'Implement Technical SEO Roadmap', 'description' => 'Advanced schema, site architecture, JavaScript fixes'],
            ['title' => 'Conduct UX/CRO Analysis', 'description' => 'Heat maps, user flows, A/B test recommendations'],
            ['title' => 'Manage Multi-Location SEO', 'description' => 'Individual GBP management, location pages, local links'],
            ['title' => 'Hold Weekly Check-ins', 'description' => '15-minute weekly touchpoint with client'],
            ['title' => 'Conduct Monthly Strategy Call', 'description' => '45-minute deep dive on performance and strategy'],
            ['title' => 'Prepare Quarterly Business Review', 'description' => '1-hour comprehensive review with ROI analysis'],
            ['title' => 'Deliver Custom Dashboard Reports', 'description' => 'Real-time or weekly updated reporting dashboards'],
        ];
    }

    // =========================================================================
    // ONBOARDING CONTENT
    // =========================================================================
    private function getOnboardingContent(): string
    {
        return <<<'CONTENT'
SEO ONBOARDING & DEEP AUDIT GUIDE
$1,750 one-time | Required for all new SEO clients

OVERVIEW
This comprehensive onboarding package is required for all new SEO clients. It establishes the baseline, identifies opportunities, and creates a clear 90-day roadmap for success.

DELIVERABLES

1. Full Technical SEO Audit
   - Complete site crawl (Screaming Frog)
   - Error identification and categorization
   - Redirect chain analysis
   - Canonical tag audit
   - Robots.txt and sitemap review
   - Mobile-friendliness assessment
   - Core Web Vitals analysis
   - Page speed deep dive

2. Site Health, Indexation & Crawl Analysis
   - Google Search Console setup/review
   - Index coverage report analysis
   - Crawl stats review
   - URL inspection for key pages
   - Structured data validation
   - Security issues check (HTTPS, mixed content)

3. Competitor Benchmark
   - Identify top 5 organic competitors
   - Domain authority comparison
   - Keyword overlap analysis
   - Content gap identification
   - Backlink profile comparison
   - SERP feature analysis

4. Keyword Mapping
   - Current keyword positions audit
   - Opportunity keyword identification
   - Keyword-to-page mapping
   - Search intent classification
   - Priority keyword selection
   - Quick win identification

5. 90-Day SEO Roadmap
   - Prioritized action items
   - Month-by-month milestones
   - Expected outcomes
   - Resource requirements
   - Success metrics definition

TIMELINE
Week 1: Data collection and crawls
Week 2: Analysis and competitor research
Week 3: Strategy development and roadmap
Week 4: Presentation and kickoff

DELIVERABLE FORMAT
• Executive summary (2-3 pages)
• Detailed audit document (15-25 pages)
• Keyword mapping spreadsheet
• 90-day roadmap presentation
• Kickoff call (45-60 minutes)

TOOLS REQUIRED
• Screaming Frog
• Google Search Console
• Semrush or Ahrefs
• PageSpeed Insights
• GTmetrix
• Schema validator
• Mobile-friendly test

IMPORTANT NOTES
• This replaces scattered audit pricing
• Cannot be skipped - foundational for success
• Sets expectations and benchmarks
• Creates accountability framework
CONTENT;
    }

    private function getOnboardingChecklist(): array
    {
        return [
            ['title' => 'Set Up/Verify Google Search Console Access', 'description' => 'Ensure full access to GSC for the client domain'],
            ['title' => 'Run Complete Screaming Frog Crawl', 'description' => 'Full site crawl with all analysis tabs'],
            ['title' => 'Document All Technical Errors', 'description' => '4xx, 5xx, redirect chains, canonicals, etc.'],
            ['title' => 'Analyze Core Web Vitals', 'description' => 'LCP, FID/INP, CLS for mobile and desktop'],
            ['title' => 'Review Index Coverage Report', 'description' => 'Identify indexation issues in GSC'],
            ['title' => 'Conduct Page Speed Analysis', 'description' => 'PageSpeed Insights and GTmetrix for key pages'],
            ['title' => 'Identify Top 5 Competitors', 'description' => 'Based on keyword overlap and local market'],
            ['title' => 'Complete Competitor Benchmark', 'description' => 'DA, keywords, content, backlinks comparison'],
            ['title' => 'Audit Current Keyword Rankings', 'description' => 'Export current positions for baseline'],
            ['title' => 'Create Keyword-to-Page Mapping', 'description' => 'Assign target keywords to specific pages'],
            ['title' => 'Identify Quick Win Keywords', 'description' => 'Position 4-20 keywords with low difficulty'],
            ['title' => 'Build 90-Day Roadmap', 'description' => 'Prioritized actions with monthly milestones'],
            ['title' => 'Create Executive Summary', 'description' => '2-3 page overview for stakeholders'],
            ['title' => 'Compile Full Audit Document', 'description' => '15-25 page detailed audit report'],
            ['title' => 'Conduct Kickoff Presentation', 'description' => '45-60 minute call to present findings and roadmap'],
        ];
    }

    // =========================================================================
    // ADD-ON SERVICE CONTENT
    // =========================================================================
    private function getAdditionalContentGuide(): string
    {
        return <<<'CONTENT'
ADDITIONAL SEO CONTENT DELIVERY
$450 per article

OVERVIEW
For clients who need additional content beyond their package allocation. Each piece follows our standard SEO content process.

SPECIFICATIONS
• 1,000-1,500 words
• Keyword-optimized
• Internal links included
• Meta title and description
• Header tag structure
• 1 round of revisions

PROCESS
1. Receive keyword/topic from strategist
2. Create content brief
3. Write first draft
4. SEO optimization pass
5. Client review (if requested)
6. Publish and internal linking
7. Submit to GSC for indexing

TURNAROUND: 5-7 business days
CONTENT;
    }

    private function getAdditionalContentChecklist(): array
    {
        return [
            ['title' => 'Receive Topic and Target Keyword', 'description' => 'Get assignment from SEO strategist'],
            ['title' => 'Create Content Brief', 'description' => 'Outline, keywords, competitor analysis'],
            ['title' => 'Write First Draft', 'description' => '1,000-1,500 words, following brief'],
            ['title' => 'Optimize for SEO', 'description' => 'Keywords, headers, meta tags, internal links'],
            ['title' => 'Client Review (if applicable)', 'description' => 'Send for approval, incorporate feedback'],
            ['title' => 'Publish Content', 'description' => 'Upload to CMS, verify formatting'],
            ['title' => 'Add Internal Links', 'description' => 'Link from/to relevant existing content'],
            ['title' => 'Submit to Google Search Console', 'description' => 'Request indexing for new URL'],
        ];
    }

    private function getLinkBuildingContent(): string
    {
        return <<<'CONTENT'
ADVANCED LINK BUILDING CAMPAIGN
$1,500 - $4,000/month (scope-based)

OVERVIEW
Strategic link acquisition beyond foundational citations. Focus on high-authority, relevant placements that move rankings.

PRICING TIERS
• $1,500/mo: 5-8 quality links
• $2,500/mo: 10-15 quality links
• $4,000/mo: 15-25 quality links + digital PR

LINK TYPES
• Guest posts on relevant industry sites
• Resource page placements
• Broken link building
• HARO/journalist queries
• Digital PR campaigns
• Niche edits (contextual placements)
• Local sponsorships and partnerships

QUALITY STANDARDS
• DR/DA 30+ minimum
• Relevant to client industry
• Real traffic websites
• No PBNs or link farms
• Natural anchor text distribution

REPORTING
• Monthly link acquisition report
• Domain metrics for each link
• Anchor text used
• Target page linked
• Live link verification
CONTENT;
    }

    private function getLinkBuildingChecklist(): array
    {
        return [
            ['title' => 'Define Link Building Goals', 'description' => 'Target pages, anchor text strategy, quantity'],
            ['title' => 'Prospect Link Opportunities', 'description' => 'Build list of relevant, quality sites'],
            ['title' => 'Conduct Outreach Campaign', 'description' => 'Personalized emails to prospects'],
            ['title' => 'Create/Provide Content', 'description' => 'Guest posts or assets for placements'],
            ['title' => 'Secure Link Placements', 'description' => 'Follow up and confirm live links'],
            ['title' => 'Verify Link Quality', 'description' => 'Check DR/DA, relevance, traffic'],
            ['title' => 'Document in Link Tracker', 'description' => 'URL, anchor, date, metrics'],
            ['title' => 'Deliver Monthly Report', 'description' => 'All links acquired with metrics'],
        ];
    }

    private function getMultiLocationContent(): string
    {
        return <<<'CONTENT'
MULTI-LOCATION SEO MANAGEMENT
$500 per location/month

OVERVIEW
For businesses with multiple physical locations requiring individual local SEO management.

PER-LOCATION DELIVERABLES
• Individual Google Business Profile management
• Location-specific keyword tracking
• Local citation building
• Location page optimization
• Review monitoring and response
• Local content recommendations

REQUIREMENTS
• Each location must have unique address
• Separate GBP listing per location
• Location pages on website (or we create them)

BEST PRACTICES
• Unique content per location page
• Location-specific schema markup
• Individual NAP consistency
• Local link building per market
• Service area optimization
CONTENT;
    }

    private function getMultiLocationChecklist(): array
    {
        return [
            ['title' => 'Set Up/Optimize GBP for Location', 'description' => 'Complete profile for specific location'],
            ['title' => 'Create/Optimize Location Page', 'description' => 'Unique content, schema, NAP'],
            ['title' => 'Build Location-Specific Citations', 'description' => 'Local directories for that market'],
            ['title' => 'Set Up Local Keyword Tracking', 'description' => 'Track rankings in specific geo'],
            ['title' => 'Monitor and Respond to Reviews', 'description' => 'Location-specific review management'],
            ['title' => 'Identify Local Link Opportunities', 'description' => 'Sponsorships, partnerships in area'],
            ['title' => 'Include in Monthly Reporting', 'description' => 'Break out metrics by location'],
        ];
    }

    private function getCroAuditContent(): string
    {
        return <<<'CONTENT'
CONVERSION RATE OPTIMIZATION AUDIT
$1,200 one-time

OVERVIEW
One-time comprehensive audit focused on improving conversion rates on SEO landing pages and key website pages.

DELIVERABLES
• Heat map and scroll depth analysis
• User flow analysis
• Form optimization recommendations
• CTA placement and copy suggestions
• Page speed impact on conversions
• Mobile UX review
• Trust signal recommendations
• A/B test hypotheses (3-5 tests)

TOOLS USED
• Hotjar or Microsoft Clarity
• Google Analytics 4
• PageSpeed Insights
• Mobile usability tools

REPORT FORMAT
• Executive summary
• Page-by-page recommendations
• Prioritized action items
• Expected impact estimates
• A/B test roadmap
CONTENT;
    }

    private function getCroAuditChecklist(): array
    {
        return [
            ['title' => 'Install Heat Mapping Tool', 'description' => 'Hotjar or Clarity on key pages'],
            ['title' => 'Collect 2 Weeks of Data', 'description' => 'Minimum data for analysis'],
            ['title' => 'Analyze Heat Maps and Recordings', 'description' => 'Identify UX issues and drop-off points'],
            ['title' => 'Review GA4 Conversion Funnels', 'description' => 'Where users abandon process'],
            ['title' => 'Audit Forms and CTAs', 'description' => 'Placement, copy, friction points'],
            ['title' => 'Assess Mobile Experience', 'description' => 'Touch targets, load time, usability'],
            ['title' => 'Document Trust Signals', 'description' => 'Reviews, badges, guarantees present?'],
            ['title' => 'Create A/B Test Hypotheses', 'description' => '3-5 prioritized test ideas'],
            ['title' => 'Compile CRO Report', 'description' => 'All findings with recommendations'],
            ['title' => 'Present to Client', 'description' => '30-minute walkthrough of findings'],
        ];
    }

    private function getTechnicalCleanupContent(): string
    {
        return <<<'CONTENT'
TECHNICAL SEO CLEANUP SPRINT
$1,500 - $3,500 (scope-based)

OVERVIEW
Intensive technical SEO project to resolve significant technical debt. Pricing based on site size and complexity.

PRICING GUIDE
• $1,500: Small sites (<500 pages), basic issues
• $2,500: Medium sites (500-2000 pages), moderate issues
• $3,500: Large sites (2000+ pages), complex issues

COMMON ISSUES ADDRESSED
• Crawl errors and broken links
• Redirect chain cleanup
• Duplicate content resolution
• Canonical tag implementation
• XML sitemap optimization
• Robots.txt configuration
• Schema markup implementation
• Page speed optimization
• Mobile usability fixes
• Index bloat cleanup

PROCESS
1. Technical audit (included)
2. Issue prioritization
3. Implementation plan
4. Execute fixes
5. Verification testing
6. Before/after documentation

TIMELINE
• Small: 1-2 weeks
• Medium: 2-3 weeks
• Large: 3-4 weeks
CONTENT;
    }

    private function getTechnicalCleanupChecklist(): array
    {
        return [
            ['title' => 'Run Comprehensive Technical Audit', 'description' => 'Full crawl and error identification'],
            ['title' => 'Prioritize Issues by Impact', 'description' => 'Critical, high, medium, low'],
            ['title' => 'Create Implementation Plan', 'description' => 'Detailed steps for each fix'],
            ['title' => 'Fix Crawl Errors', 'description' => '4xx, 5xx errors resolved'],
            ['title' => 'Clean Up Redirect Chains', 'description' => 'Maximum 1 redirect hop'],
            ['title' => 'Resolve Duplicate Content', 'description' => 'Canonicals, consolidation, noindex'],
            ['title' => 'Optimize XML Sitemap', 'description' => 'Only indexable URLs, proper format'],
            ['title' => 'Configure Robots.txt', 'description' => 'Proper allow/disallow rules'],
            ['title' => 'Implement Schema Markup', 'description' => 'Organization, LocalBusiness, etc.'],
            ['title' => 'Address Page Speed Issues', 'description' => 'Images, caching, code optimization'],
            ['title' => 'Verify All Fixes', 'description' => 'Re-crawl and test implementations'],
            ['title' => 'Document Before/After', 'description' => 'Screenshots and metrics comparison'],
        ];
    }
}
