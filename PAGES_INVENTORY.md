# Complete Page Inventory - Client Portal Application

> **Last Updated**: 2026-02-05  
> **Purpose**: Comprehensive inventory of all pages in the application for Tailwind CSS styling audit  
> **Status**: 🔴 CRITICAL - Multiple pages contain non-Tailwind styling that needs conversion

---

## 📊 Executive Summary

| Metric | Count |
|--------|-------|
| **Total Pages** | 150+ |
| **Admin Pages** | 80+ |
| **Client Pages** | 50+ |
| **Auth Pages** | 6 |
| **Livewire Components** | 100+ |
| **Blade Templates** | 290+ |
| **Pages with Bootstrap** | 20+ |
| **Pages with Mixed Styling** | 15+ |
| **Priority Conversion Targets** | 35+ |

---

## 🎨 Styling Issues Found

### Bootstrap Classes Detected
The following Bootstrap classes are present in the codebase and need to be converted to Tailwind CSS:

```css
/* Layout Classes */
d-flex, flex-wrap, align-items-center, justify-content-between, gap-2
row, col-12, col-md-4, col-md-6, col-lg-2, g-3, gx-2, gy-2

/* Components */
card, card-header, card-body, card-footer, card-header-tabs
btn, btn-primary, btn-secondary, btn-outline-primary, btn-sm, btn-lg
form-control, form-label, form-select, form-check, form-switch
nav, nav-tabs, nav-pills, nav-item, nav-link

/* Typography */
h1, h2, h3, h4, h5, h6
text-muted, text-danger, text-success, text-primary
page-title, page-pretitle, subheader

/* Spacing */
mb-0, mb-1, mb-2, mb-3, mb-4
mt-0, mt-1, mt-2, mt-3, mt-4
p-0, p-1, p-2, p-3, p-4
```

### Conversion Priority
- 🔴 **HIGH**: Tables, forms, and complex layouts (Invoice, Client, Request management)
- 🟡 **MEDIUM**: Dashboard layouts, cards, navigation components
- 🟢 **LOW**: Simple text displays, minor utility classes

---

## 🔐 Authentication Pages

| Page | Route | View/Component | Status | Priority |
|------|-------|----------------|--------|----------|
| Login | `/login` | `resources/views/auth/login.blade.php` | 🟡 Needs Review | Medium |
| Register | `/register` | `resources/views/auth/register.blade.php` | 🟡 Needs Review | Medium |
| Forgot Password | `/forgot-password` | `resources/views/auth/forgot-password.blade.php` | 🟡 Needs Review | Medium |
| Reset Password | `/reset-password/{token}` | `resources/views/auth/reset-password.blade.php` | 🟡 Needs Review | Medium |
| Verify Email | `/verify-email` | `resources/views/auth/verify-email.blade.php` | 🟡 Needs Review | Low |
| Confirm Password | `/confirm-password` | `resources/views/auth/confirm-password.blade.php` | 🟡 Needs Review | Low |

**Notes**: Authentication pages are Breeze-based and should already be Tailwind-styled, but need verification.

---

## 👑 Admin Panel Pages

### Dashboard & Overview
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Admin Dashboard | `/admin` | `AdminDashboardController@index` | 🔴 Bootstrap Cards | High |
| Activity Log | `/admin/activity` | `resources/views/admin/activity.blade.php` | 🔴 Bootstrap Tables | High |
| Reports Dashboard | `/admin/reports` | `ReportDashboard` (Livewire) | 🔴 Bootstrap Tables | High |
| Workload Dashboard | `/admin/workload` | `WorkloadDashboard` (Livewire) | 🟡 Mixed Styling | Medium |
| White Label Config | `/admin/white-label` | `WhiteLabelConfigurator` (Livewire) | 🟡 Forms | Medium |
| Client Reports Config | `/admin/client-reports` | `ReportCustomizer` (Livewire) | 🟡 Forms | Medium |

---

### Client Management
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| All Clients | `/admin/clients` | `ClientManagement` (Livewire) | 🔴 **Bootstrap + Tailwind Mix** | **HIGH** |
| Create Client | `/admin/clients/create` | `ClientCreate` (Livewire) | 🔴 Form Controls | High |
| Client Details | `/admin/clients/{id}` | `ClientDetail` (Livewire) | 🔴 Bootstrap Cards/Tables | High |
| Edit Client | `/admin/clients/{id}/edit` | `ClientEdit` (Livewire) | 🔴 Form Controls | High |

**Critical File**: `resources/views/livewire/admin/clients/index.blade.php` - Contains extensive Bootstrap usage

---

### Request Management
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| All Requests | `/admin/requests` | `AdminRequestManagement` (Livewire) | 🔴 **Complex Layout** | **HIGH** |
| Create Request | `/admin/requests/create` | `AdminRequestCreate` (Livewire) | 🔴 Forms | High |
| Request Detail | `/admin/requests/{id}` | `AdminRequestDetail` (Livewire) | 🔴 Complex Layout | High |
| Project Estimator | `/admin/requests/{id}/estimator` | `AdminProjectEstimator` (Livewire) | 🟡 Forms | Medium |

**Critical File**: `resources/views/livewire/admin/requests/index.blade.php` - Complex Tailwind with custom classes

---

### Invoice Management  🔴 **CRITICAL AREA**
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| All Invoices | `/admin/invoices` | `AdminInvoiceManagement` (Livewire) | 🔴 **Bootstrap Extensive** | **CRITICAL** |
| Create Invoice | `/admin/invoices/create` | `AdminInvoiceCreate` (Livewire) | 🔴 Complex Forms | High |
| Edit Invoice | `/admin/invoices/{id}/edit` | `AdminInvoiceEdit` (Livewire) | 🔴 Forms + Tables | High |
| Recurring Invoices | `/admin/invoices/recurring` | `RecurringInvoiceIndex` (Livewire) | 🔴 Tables | High |
| Export Invoice PDF | `/admin/reports/export-invoices-pdf` | PDF View | 🟢 PDF Styling | Low |

**Critical Files**:
- `resources/views/livewire/admin/invoices/index.blade.php` - Uses `d-flex`, `btn btn-primary`, `card`, `form-control`
- `resources/views/admin/invoices/export-list-pdf.blade.php` - PDF export (different concern)

---

### Contract Management
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| All Contracts | `/admin/contracts` | `AdminContractManagement` (Livewire) | 🔴 Tables | High |
| Create Contract | `/admin/contracts/create` | `AdminContractCreate` (Livewire) | 🟡 Forms | Medium |
| Edit Contract | `/admin/contracts/{id}/edit` | `AdminContractEdit` (Livewire) | 🟡 Forms | Medium |
| Contract Generator | `/admin/contracts/generator` | `AdminContractGenerator` (Livewire) | 🟡 Form Builder | Medium |

---

### Project & Time Management
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Task Management | `/admin/tasks` | `AdminTaskManagement` (Livewire) | 🔴 **Multiple Views** | **CRITICAL** |
| Task Board (Kanban) | `/admin/projects/board` | `AdminTaskBoard` (Livewire) | 🔴 **Complex Kanban UI** | **CRITICAL** |
| Time Tracking | `/admin/projects/time` | `AdminTimeTracker` (Livewire) | 🔴 Tables | High |
| Project Timeline | `/admin/projects/timeline` | `AdminProjectTimeline` (Livewire) | 🟡 Timeline UI | Medium |
| Team Workload | `/admin/projects/workload` | `AdminTeamWorkload` (Livewire) | 🟡 Tables | Medium |
| Task Details | `/admin/projects/tasks/{id}` | `AdminTaskDetail` (Livewire) | 🟡 Forms | Medium |
| Time Approvals | `/admin/projects/time-approvals` | `AdminTimeApprovals` (Livewire) | 🟡 Tables | Medium |
| Project Budgets | `/admin/projects/budgets` | `AdminProjectBudgets` (Livewire) | 🟡 Charts | Medium |

**Notes**: Task Management supports Kanban, Gantt, List, and Calendar views - each needs individual styling review.

---

### User & Permission Management
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| All Users | `/admin/users` | `UserManagement` (Livewire) | 🔴 Tables | High |
| Create User | `/admin/users/create` | `UserCreate` (Livewire) | 🟡 Forms | Medium |
| Edit User | `/admin/users/{id}/edit` | `UserEdit` (Livewire) | 🟡 Forms | Medium |
| Permissions Matrix | `/admin/users/permissions` | `AdminUserPermissions` (Livewire) | 🔴 **Permission Grid** | **HIGH** |

---

### Marketing & Campaigns
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Website Auditor | `/admin/marketing/website-auditor` | `MarketingWebsiteAuditor` (Livewire) | 🟡 Forms | Medium |
| Audit Results | `/admin/marketing/audit-results` | `MarketingAuditResults` (Livewire) | 🟡 Tables/Charts | Medium |
| Campaign Analytics | `/admin/marketing/campaigns` | `CampaignAnalyticsDashboard` (Livewire) | 🟡 Charts | Medium |
| Campaign Management | `/admin/marketing/campaigns/manage` | `CampaignManagement` (Livewire) | 🟡 Forms/Tables | Medium |
| Brand Monitoring Dashboard | `/admin/marketing/brand-monitoring` | `AdminBrandMonitoringDashboard` (Livewire) | 🟡 Charts | Medium |
| Brand Monitoring API Status | `/admin/brand-monitoring/api-status` | `AdminBrandMonitoringApiStatus` (Livewire) | 🟢 Status Display | Low |
| Competitor Analysis | `/admin/marketing/competitor-analysis` | `CompetitorAnalysisDashboard` (Livewire) | 🟡 Tables/Charts | Medium |
| Lead Management | `/admin/marketing/leads` | `AdminLeadManagement` (Livewire) | 🔴 Tables | High |

---

### Social Media Management
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Posts Manager | `/admin/social/posts` | `PostManager` (Livewire) | 🔴 Tables | High |
| Create Post | `/admin/social/posts/create` | `PostCreator` (Livewire) | 🟡 Forms | Medium |
| Edit Post | `/admin/social/posts/{id}/edit` | `PostCreator` (Livewire) | 🟡 Forms | Medium |
| Content Calendar | `/admin/social/content-calendar` | `ContentCalendar` (Livewire) | 🔴 **Calendar UI** | **HIGH** |
| Social Manager | `/admin/social/manager` | `SocialMediaManager` (Livewire) | 🟡 Complex UI | Medium |

---

### Ad Management
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Ad Campaigns | `/admin/ads/campaigns` | `AdCampaignManager` (Livewire) | 🟡 Tables | Medium |
| Ad Performance | `/admin/ads/performance` | `AdPerformanceDashboard` (Livewire) | 🟡 Charts/Analytics | Medium |

---

### Support & Maintenance
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Support Tickets | `/admin/support-tickets` | `SupportTicketManagement` (Livewire) | 🔴 Tables | High |
| Ticket Details | `/admin/support-tickets/{id}` | `SupportTicketDetail` (Livewire) | 🟡 Complex Layout | Medium |
| Maintenance Plans | `/admin/maintenance-plans` | `MaintenancePlanIndex` (Livewire) | 🟡 Tables | Medium |
| Create Plan | `/admin/maintenance-plans/create` | `MaintenancePlanCreate` (Livewire) | 🟡 Forms | Medium |
| Edit Plan | `/admin/maintenance-plans/{id}/edit` | `MaintenancePlanEdit` (Livewire) | 🟡 Forms | Medium |

---

### Communication & Notes
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Email Assistant | `/admin/communication/email-assistant` | `EmailDraftAssistant` (Livewire) | 🟡 Forms | Medium |
| Meeting Notes | `/admin/meeting-notes` | `AdminMeetingNotes` (Livewire) | 🟡 Notes Layout | Medium |
| Messaging Hub | `/admin/messages` | `MessagingHub` (Livewire) | 🟡 Chat Interface | Medium |

---

### Storage Management
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Storage Overview | `/admin/storage` | `StorageOverview` (Livewire) | 🟡 Dashboard | Medium |
| S3 Connection | `/admin/storage/s3/connect` | `ConnectS3` (Livewire) | 🟡 Forms | Medium |
| S3 Browser | `/admin/storage/s3/browse/{id}` | `S3Browser` (Livewire) | 🟡 File Browser | Medium |
| Dropbox Connection | `/admin/storage/dropbox/connect` | `ConnectDropbox` (Livewire) | 🟡 Forms | Medium |
| Dropbox Browser | `/admin/storage/dropbox/browse/{id}` | `DropboxBrowser` (Livewire) | 🟡 File Browser | Medium |
| Google Drive Connection | `/admin/storage/google-drive/connect` | `ConnectGoogleDrive` (Livewire) | 🟡 Forms | Medium |
| Google Drive Browser | `/admin/storage/google-drive/browse/{id}` | `GoogleDriveBrowser` (Livewire) | 🟡 File Browser | Medium |

---

### Account Management
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Account Health Dashboard | `/admin/account/health` | `AccountHealthDashboard` (Livewire) | 🟡 Dashboard | Medium |
| QBR Builder | `/admin/account/qbrs` | `QBRBuilder` (Livewire) | 🟡 Forms | Medium |
| Renewal Manager | `/admin/account/renewals` | `RenewalManager` (Livewire) | 🟡 Tables | Medium |
| Upsell Tracker | `/admin/account/upsells` | `UpsellTracker` (Livewire) | 🟡 Tables | Medium |

---

### AI & Automation
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| AI Provider Management | `/admin/ai/providers` | `AdminAIProviderManagement` (Livewire) | 🟡 Tables | Medium |
| Create AI Provider | `/admin/ai/providers/create` | `AdminAIProviderForm` (Livewire) | 🟡 Forms | Medium |
| Edit AI Provider | `/admin/ai/providers/{id}` | `AdminAIProviderForm` (Livewire) | 🟡 Forms | Medium |
| AI Tasks Config | `/admin/ai/tasks` | `AdminAITaskConfiguration` (Livewire) | 🟡 Tables | Medium |
| AI Usage Dashboard | `/admin/ai/usage` | `AdminAIUsageDashboard` (Livewire) | 🟡 Charts | Medium |
| AI Audit Log | `/admin/ai/audit` | `AdminAIAuditLog` (Livewire) | 🟡 Tables | Medium |
| AI Safety Dashboard | `/admin/ai/safety` | `AdminAISafetyDashboard` (Livewire) | 🟡 Dashboard | Medium |
| AI Review Queue | `/admin/ai/review-queue` | `AdminAIReviewQueue` (Livewire) | 🟡 Tables | Medium |
| AI Quality Metrics | `/admin/ai/quality` | `AdminAIQualityMetrics` (Livewire) | 🟡 Charts | Medium |
| AI Assistant Chat | `/admin/ai/assistant` | `AdminAssistantChat` (Livewire) | 🟡 Chat Interface | Medium |
| Prompt Templates | `/admin/ai/prompt-templates` | `AdminPromptTemplates` (Livewire) | 🟡 Tables | Medium |
| Knowledge Base | `/admin/ai/knowledge-base` | `AdminKnowledgeBase` (Livewire) | 🟡 Document Lists | Medium |
| Workflow Builder | `/admin/ai/workflows` | `AdminWorkflowBuilder` (Livewire) | 🔴 **Visual Builder** | **HIGH** |

---

### Analytics & Insights
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| AI Insights Dashboard | `/admin/analytics/ai-insights` | `AdminAIInsightsDashboard` (Livewire) | 🟡 Charts | Medium |
| Predictive Analytics | `/admin/analytics/predictive` | `AdminPredictiveCharts` (Livewire) | 🟡 Charts | Medium |
| Client Health Monitor | `/admin/analytics/client-health` | `AdminClientHealthMonitor` (Livewire) | 🟡 Dashboard | Medium |

---

### Technical Tools
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Code Review Tool | `/admin/technical/code-review` | `AdminCodeReviewer` (Livewire) | 🟡 Code Display | Medium |
| Architecture Advisor | `/admin/technical/architecture` | `AdminArchitectureAdvisor` (Livewire) | 🟡 Diagrams/Forms | Medium |

---

### Partner Management
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Partner Manager | `/admin/partners` | `PartnerManager` (Livewire) | 🟡 Tables | Medium |
| Referral Dashboard | `/admin/referrals` | `ReferralDashboard` (Livewire) | 🟡 Dashboard | Medium |

---

### Feedback & Surveys
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Survey Builder | `/admin/feedback/surveys` | `SurveyBuilder` (Livewire) | 🟡 Form Builder | Medium |
| Testimonial Manager | `/admin/feedback/testimonials` | `TestimonialManager` (Livewire) | 🟡 Tables/Forms | Medium |

---

### Settings & Configuration
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| System Settings | `/admin/settings` | `SystemSettings` (Livewire) | 🔴 Form Styling | High |
| API Integrations | `/admin/settings/integrations` | `ApiSettings` (Livewire) | 🟡 Tables/Forms | Medium |
| Form Templates | `/admin/settings/forms` | `FormTemplateIndex` (Livewire) | 🟡 Tables | Medium |
| Form Template Editor | `/admin/settings/forms/{slug}` | `FormTemplateEditor` (Livewire) | 🟡 Form Builder | Medium |
| Webhooks | `/admin/webhooks` | `WebhookManagement` (Livewire) | 🟡 Tables/Forms | Medium |
| Automation Rules | `/admin/automation` | `AutomationIndex` (Livewire) | 🟡 Tables | Medium |
| Automation Builder | `/admin/automation/builder/{id}` | `AutomationBuilder` (Livewire) | 🟡 Form Builder | Medium |
| Automation Logs | `/admin/automation/logs` | `AutomationLogs` (Livewire) | 🟡 Tables | Medium |

---

### Security & Compliance
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Privacy Requests | `/admin/security/privacy-requests` | `AdminPrivacyRequests` (Livewire) | 🟡 Tables | Medium |
| Security Overview | `/admin/security` | `AdminSecurityOverview` (Livewire) | 🟡 Dashboard | Medium |

---

### Staff Resources
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Staff Guides Index | `/admin/staff-guides` | `StaffGuidesIndex` (Livewire) | 🟡 Document Lists | Medium |
| Staff Guide Manager | `/admin/staff-guides/manage` | `StaffGuideManager` (Livewire) | 🟡 Form Builder | Medium |

---

## 👥 Client Portal Pages

### Core Features
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Client Dashboard | `/dashboard` | `DashboardController@index` | 🟡 Mixed Styling | Medium |
| Projects Dashboard | `/projects` | `ProjectDashboard` (Livewire) | 🟡 Dashboard | Medium |
| Data Rooms | `/data-rooms` | `DataRoomBrowser` (Livewire) | 🟡 File Browser | Medium |
| Data Room Detail | `/data-rooms/{id}` | `DataRoomBrowser` (Livewire) | 🟡 File Browser | Medium |
| Messages | `/messages` | `Messaging` (Livewire) | 🟡 Chat Interface | Medium |
| Knowledge Base | `/knowledge-base` | `KnowledgeBase` (Livewire) | 🟡 Document Lists | Medium |
| Notifications Center | `/notifications` | `NotificationsCenter` (Livewire) | 🟡 Lists | Medium |
| Analytics Dashboard | `/analytics` | `AnalyticsDashboard` (Livewire) | 🟡 Dashboard | Medium |
| SEO Dashboard | `/seo` | `SeoDashboard` (Livewire) | 🟡 Dashboard | Medium |
| Campaigns Dashboard | `/campaigns` | `CampaignsDashboard` (Livewire) | 🟡 Dashboard | Medium |
| Campaign Manager | `/campaigns/manage` | `CampaignManager` (Livewire) | 🟡 Forms/Tables | Medium |
| Account Connections | `/connections` | `AccountConnections` (Livewire) | 🟡 Tables | Medium |
| Onboarding Wizard | `/onboarding` | `OnboardingWizard` (Livewire) | 🟡 Form Wizard | Medium |
| Meeting Scheduler | `/meetings` | `MeetingScheduler` (Livewire) | 🟡 Calendar/Forms | Medium |
| Privacy Center | `/privacy` | `PrivacyCenter` (Livewire) | 🟡 Forms | Low |

---

### Requests (Service Requests)
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| All Requests | `/requests` | `RequestController@index` | 🔴 Tables | High |
| Create Request | `/requests/create` | `RequestController@create` | 🟡 Forms | Medium |
| Request Details | `/requests/{id}` | `RequestController@show` | 🟡 Complex Layout | Medium |
| Edit Request | `/requests/{id}/edit` | `RequestController@edit` | 🟡 Forms | Medium |
| Estimate Approval | `/requests/{id}/estimate` | `EstimateApproval` (Livewire) | 🟡 Form Display | Medium |
| Request Estimate | `/estimate` | `EstimateRequest` (Livewire) | 🟡 Forms | Medium |

---

### Support Tickets
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| All Tickets | `/support-tickets` | `SupportTicketIndex` (Livewire) | 🔴 Tables | High |
| Create Ticket | `/support-tickets/create` | `SupportTicketCreate` (Livewire) | 🟡 Forms | Medium |
| Ticket Details | `/support-tickets/{id}` | `SupportTicketShow` (Livewire) | 🟡 Chat Layout | Medium |

---

### Contracts
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| All Contracts | `/contracts` | `ContractController@index` | 🔴 Tables | High |
| Contract Details | `/contracts/{id}` | `ContractController@show` | 🟡 Display Layout | Medium |
| Contract Preview | `/contracts/{id}/preview` | `ContractController@preview` | 🟡 Document Viewer | Medium |

---

### Invoices & Payments
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| All Invoices | `/invoices` | `InvoiceController@index` | 🔴 **Tables** | **HIGH** |
| Invoice Details | `/invoices/{id}` | `InvoiceController@show` | 🟡 Display | Medium |
| Payment Page | `/invoices/{id}/pay` | `PaymentController@show` | 🟡 Form Styling | Medium |

---

### Documents
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| All Documents | `/documents` | `DocumentController@index` | 🔴 **Tables** | **HIGH** |
| Document Viewer | `/documents/{id}` | `DocumentController@show` | 🟡 Complex Viewer | Medium |
| Document Chat | `/documents/{id}/chat` | `DocumentChat` (Livewire) | 🟡 Chat Interface | Medium |
| Document AI Analysis | `/documents/{id}/ai` | `DocumentAIAnalysis` (Livewire) | 🟡 Chat/Analysis | Medium |
| Document Summarize | `/documents/{id}/summarize` | `SummarizeDocument` (Livewire) | 🟡 Text Display | Low |
| Document Workflow | `/documents/{id}/workflow` | `DocumentWorkflow` (Livewire) | 🟡 Workflow UI | Medium |
| Smart Browser | `/documents/smart-browser` | `SmartDocumentBrowser` (Livewire) | 🟡 File Browser | Medium |
| Document Templates | `/documents/templates` | `DocumentTemplates` (Livewire) | 🟡 Lists | Low |

---

### Storage
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Storage Dashboard | `/storage` | `StorageDashboard` (Livewire) | 🟡 Dashboard | Medium |
| Unified File Browser | `/storage/browser` | `UnifiedFileBrowser` (Livewire) | 🟡 File Browser | Medium |
| Storage Settings | `/storage/settings` | `StorageSettings` (Livewire) | 🟡 Forms | Medium |
| Storage Conflicts | `/storage/conflicts` | `StorageConflicts` (Livewire) | 🟡 Tables | Medium |

---

### Social Media & Marketing
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Pending Approvals | `/social/pending-approvals` | `PendingApprovals` (Livewire) | 🟡 Tables | Medium |
| Social Accounts | `/social/accounts` | `SocialAccountManager` (Livewire) | 🟡 Tables | Medium |
| Analytics Accounts | `/analytics/accounts` | `AnalyticsAccountManager` (Livewire) | 🟡 Tables | Medium |
| Brand Monitoring Mentions | `/brand-monitoring/my-mentions` | `ClientMyMentions` (Livewire) | 🟡 Tables | Medium |

---

### Research & Consultation Tools
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Research Assistant | `/research` | `ResearchAssistantTool` (Livewire) | 🟡 Chat Interface | Low |
| Technical Advisor | `/research/technical` | `TechnicalAdvisorTool` (Livewire) | 🟡 Chat Interface | Low |
| Industry Monitor | `/research/monitor` | `IndustryMonitorTool` (Livewire) | 🟡 Dashboard | Low |
| Competitor Monitor | `/research/competitors` | `CompetitorMonitorTool` (Livewire) | 🟡 Dashboard | Low |
| Industry Insights | `/research/insights` | `IndustryInsightsTool` (Livewire) | 🟡 Dashboard | Low |

---

### AI & Advanced Features
| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| AI Assistant | `/assistant` | `ClientAssistantChat` (Livewire) | 🟡 Chat Interface | Low |
| Client Reports Dashboard | `/reports` | `ClientReportDashboard` (Livewire) | 🟡 Dashboard | Medium |
| Report Archive | `/reports/archive` | `ClientReportArchive` (Livewire) | 🟡 Tables | Medium |
| Proposal Viewer | `/proposals/{id}` | `ProposalViewer` (Livewire) | 🟡 Document Viewer | Low |

---

## ⚙️ Settings & Profile

| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| Edit Profile | `/profile` | `ProfileController@edit` | 🟡 Forms | Medium |
| Two-Factor Setup | `/two-factor/setup` | `TwoFactorSetup` (Livewire) | 🟡 Setup Wizard | Low |

---

## 📄 Shared/Common Pages

| Page | Route | Component/View | Status | Priority |
|------|-------|----------------|--------|----------|
| API Documentation | `/api/documentation` | `resources/views/api-docs.blade.php` | 🟡 Custom Styling | Low |
| Public Document Share | `/share/{token}` | `DocumentShareController` | 🟡 Document Viewer | Low |

---

## 🚨 Critical Conversion Targets (Top 20)

### Immediate Priority (Week 1)
1. 🔴 **Admin Invoice Management** - `/admin/invoices` - Extensive Bootstrap usage
2. 🔴 **Admin Client Management** - `/admin/clients` - Bootstrap + Tailwind mix
3. 🔴 **Admin Request Management** - `/admin/requests` - Complex layout
4. 🔴 **Task Management (Kanban)** - `/admin/projects/board` - Complex Kanban UI
5. 🔴 **Content Calendar** - `/admin/social/content-calendar` - Calendar UI
6. 🔴 **Permissions Matrix** - `/admin/users/permissions` - Permission grid
7. 🔴 **Client Invoice List** - `/invoices` - Tables
8. 🔴 **Client Document List** - `/documents` - Tables
9. 🔴 **Workflow Builder** - `/admin/ai/workflows` - Visual builder
10. 🔴 **Admin Dashboard** - `/admin` - Bootstrap cards

### High Priority (Week 2)
11. 🔴 **Support Tickets** - `/admin/support-tickets` - Tables
12. 🔴 **Lead Management** - `/admin/marketing/leads` - Tables
13. 🔴 **Posts Manager** - `/admin/social/posts` - Tables
14. 🔴 **User Management** - `/admin/users` - Tables
15. 🔴 **Contract Management** - `/admin/contracts` - Tables
16. 🔴 **Client Requests List** - `/requests` - Tables
17. 🔴 **Client Support Tickets** - `/support-tickets` - Tables
18. 🔴 **Client Contracts** - `/contracts` - Tables
19. 🔴 **Time Tracking** - `/admin/projects/time` - Tables
20. 🔴 **Activity Log** - `/admin/activity` - Tables

---

## 📝 Conversion Guidelines

### Bootstrap → Tailwind CSS Mapping

#### Layout
```css
/* Bootstrap → Tailwind */
d-flex              → flex
flex-wrap           → flex-wrap
align-items-center  → items-center
justify-content-between → justify-between
gap-2               → gap-2 (already Tailwind)

row                 → flex flex-wrap -mx-4
col-12              → w-full px-4
col-md-6            → md:w-1/2 px-4
col-lg-4            → lg:w-1/3 px-4
g-3                 → gap-3
```

#### Components
```css
/* Cards */
card                → bg-white rounded-lg shadow-sm border border-gray-200
card-header         → px-6 py-4 border-b border-gray-200
card-body           → p-6
card-footer         → px-6 py-4 border-t border-gray-200

/* Buttons */
btn                 → inline-flex items-center px-4 py-2 rounded-md font-medium
btn-primary         → bg-blue-600 text-white hover:bg-blue-700
btn-secondary       → bg-gray-600 text-white hover:bg-gray-700
btn-outline-primary → border border-blue-600 text-blue-600 hover:bg-blue-50

/* Forms */
form-control        → w-full px-3 py-2 border border-gray-300 rounded-md
form-label          → block text-sm font-medium text-gray-700 mb-1
form-select         → w-full px-3 py-2 border border-gray-300 rounded-md bg-white
```

#### Typography
```css
/* Bootstrap → Tailwind */
text-muted          → text-gray-500
text-danger         → text-red-600
text-success        → text-green-600
h1, h2, h3          → text-3xl font-bold, text-2xl font-bold, text-xl font-bold
```

#### Spacing
```css
/* Bootstrap → Tailwind */
mb-0, mb-1, mb-2, mb-3, mb-4 → mb-0, mb-1, mb-2, mb-3, mb-4
mt-0, mt-1, mt-2, mt-3, mt-4 → mt-0, mt-1, mt-2, mt-3, mt-4
p-0, p-1, p-2, p-3, p-4       → p-0, p-1, p-2, p-3, p-4
```

---

## 🔍 Search Patterns for Auditing

Use these grep patterns to find non-Tailwind styling:

```bash
# Find Bootstrap classes
grep -r "d-flex\|btn btn-\|card\|form-control\|col-\|row\|nav-tabs" resources/views/ app/Livewire/

# Find inline styles
grep -r "style=\"" resources/views/ app/Livewire/

# Find <style> tags
grep -r "<style>" resources/views/ app/Livewire/

# Find class attributes (for manual review)
grep -r "class=\"" resources/views/ | grep -v "tailwind"
```

---

## 📚 Component Locations

### Livewire Components
- **Location**: `app/Livewire/` (formerly `app/Http/Livewire/`)
- **Views**: Each component has a corresponding view in `resources/views/livewire/`

### Blade Templates
- **Location**: `resources/views/`
- **Subdirectories**:
  - `admin/` - Admin-specific views
  - `auth/` - Authentication views
  - `emails/` - Email templates
  - `documents/` - Document-related views
  - `requests/` - Request views
  - `payments/` - Payment views
  - `storage/` - Storage-related views
  - `livewire/` - Livewire component views
  - `partials/` - Shared partials
  - `components/` - Blade components

---

## 🎯 Testing Strategy

After converting each page:
1. ✅ Visual inspection in browser
2. ✅ Verify responsive behavior (mobile, tablet, desktop)
3. ✅ Test interactive elements (buttons, forms, modals)
4. ✅ Validate table layouts and data display
5. ✅ Check dark mode compatibility (if applicable)
6. ✅ Screenshot before/after for documentation

---

## 📊 Progress Tracking

| Category | Total | Completed | In Progress | Remaining |
|----------|-------|-----------|-------------|-----------|
| Auth Pages | 6 | 0 | 0 | 6 |
| Admin Pages | 80+ | 0 | 0 | 80+ |
| Client Pages | 50+ | 0 | 0 | 50+ |
| Shared Pages | 10+ | 0 | 0 | 10+ |
| **TOTAL** | **150+** | **0** | **0** | **150+** |

---

## 🔗 Related Documentation

- [Tailwind CSS v4 Documentation](https://tailwindcss.com/docs)
- [Laravel Livewire v3 Documentation](https://livewire.laravel.com/docs)
- [Laravel Blade Documentation](https://laravel.com/docs/blade)

---

## 📞 Notes

- **Tech Stack**: Laravel 11, Livewire v3, Tailwind CSS v4
- **Current State**: Mixed Bootstrap + Tailwind styling
- **Goal**: 100% Tailwind CSS styling
- **Approach**: Page-by-page conversion, starting with high-priority areas
- **Special Attention**: Tables, forms, complex layouts (Kanban, calendars, file browsers)

---

**Last Updated**: 2026-02-05  
**Maintained By**: Development Team  
**Status**: 🔴 Active Conversion Project
