<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Livewire\Onboarding\OnboardingWizard;
use App\Http\Livewire\Proposals\ProposalViewer;
use App\Http\Livewire\WhiteLabel\ClientReportDashboard;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\Admin\AdminReportExportController;
use App\Http\Controllers\Storage\StorageFileController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use App\Http\Controllers\Documents\DocumentShareController;
use App\Http\Controllers\Documents\DocumentVersionController;
use App\Http\Controllers\Documents\DocumentViewerController;
use App\Http\Controllers\Marketing\WebsiteAuditController;
use App\Http\Livewire\Admin\Reports\ReportDashboard;
use App\Http\Livewire\Admin\Settings\SystemSettings;
use App\Http\Livewire\Admin\Automation\AutomationIndex;
use App\Http\Livewire\Admin\Automation\AutomationBuilder;
use App\Http\Livewire\Admin\Automation\AutomationLogs;
use App\Http\Livewire\Admin\Requests\AdminRequestManagement;
use App\Http\Livewire\Admin\Requests\AdminRequestDetail;
use App\Http\Livewire\Admin\Requests\RequestCreate as AdminRequestCreate;
use App\Http\Livewire\Admin\Requests\ProjectEstimator as AdminProjectEstimator;
use App\Http\Livewire\Admin\Invoices\AdminInvoiceManagement as AdminInvoiceManagement;
use App\Http\Livewire\Admin\Invoices\InvoiceCreate as AdminInvoiceCreate;
use App\Http\Livewire\Admin\Invoices\InvoiceEdit as AdminInvoiceEdit;
use App\Http\Livewire\Settings\WebhookManagement;
use App\Http\Livewire\Storage\StorageDashboard;
use App\Http\Livewire\Storage\StorageConflicts;
use App\Http\Livewire\Storage\UnifiedFileBrowser;
use App\Http\Livewire\Storage\StorageSettings;
use App\Http\Livewire\Admin\Storage\StorageOverview;
use App\Http\Livewire\Documents\DocumentWorkflow;
use App\Http\Livewire\Documents\SmartDocumentBrowser;
use App\Http\Livewire\Documents\DocumentTemplates;
use App\Http\Livewire\Documents\DocumentAIAnalysis;
use App\Http\Livewire\Documents\DocumentChat;
use App\Http\Livewire\Documents\SummarizeDocument;
use App\Http\Livewire\Client\ProjectDashboard;
use App\Http\Livewire\Client\Messaging;
use App\Http\Livewire\Client\KnowledgeBase;
use App\Http\Livewire\Client\NotificationsCenter;
use App\Http\Livewire\Client\AnalyticsDashboard;
use App\Http\Livewire\Client\EstimateApproval;
use App\Http\Livewire\Communication\MeetingScheduler;
use App\Http\Livewire\Feedback\FeedbackCollector;
use App\Http\Livewire\Security\TwoFactorSetup;
use App\Http\Livewire\Admin\Contracts\ContractGenerator as AdminContractGenerator;
use App\Http\Livewire\Admin\MeetingNotes as AdminMeetingNotes;
use App\Http\Livewire\Communication\EmailDraftAssistant;
use App\Http\Livewire\Admin\Analytics\AIInsightsDashboard as AdminAIInsightsDashboard;
use App\Http\Livewire\Admin\Analytics\PredictiveCharts as AdminPredictiveCharts;
use App\Http\Livewire\Admin\Analytics\ClientHealthMonitor as AdminClientHealthMonitor;
use App\Http\Livewire\Research\ResearchAssistant as ResearchAssistantTool;
use App\Http\Livewire\Research\TechnicalAdvisor as TechnicalAdvisorTool;
use App\Http\Livewire\Research\IndustryMonitor as IndustryMonitorTool;
use App\Http\Livewire\Research\CompetitorMonitor as CompetitorMonitorTool;
use App\Http\Livewire\Research\IndustryInsights as IndustryInsightsTool;
use App\Http\Livewire\WhiteLabel\WhiteLabelConfigurator;
use App\Http\Livewire\WhiteLabel\ReportCustomizer;
use App\Http\Livewire\Proposals\ProposalBuilder;
use App\Http\Livewire\Proposals\ProposalAnalytics;
use App\Http\Livewire\Projects\TimeTracker as AdminTimeTracker;
use App\Http\Livewire\Projects\TaskBoard as AdminTaskBoard;
use App\Http\Livewire\Projects\ProjectTimeline as AdminProjectTimeline;
use App\Http\Livewire\Projects\TeamWorkload as AdminTeamWorkload;
use App\Http\Livewire\Feedback\SurveyBuilder;
use App\Http\Livewire\Feedback\TestimonialManager;
use App\Http\Livewire\AccountManagement\AccountHealthDashboard;
use App\Http\Livewire\AccountManagement\QBRBuilder;
use App\Http\Livewire\AccountManagement\RenewalManager;
use App\Http\Livewire\AccountManagement\UpsellTracker;
use App\Http\Livewire\Partners\PartnerManager;
use App\Http\Livewire\Partners\ReferralDashboard;
use App\Http\Livewire\Marketing\WebsiteAuditor as MarketingWebsiteAuditor;
use App\Http\Livewire\Marketing\AuditResults as MarketingAuditResults;
use App\Http\Livewire\Admin\AI\AIProviderManagement as AdminAIProviderManagement;
use App\Http\Livewire\Admin\AI\AIProviderForm as AdminAIProviderForm;
use App\Http\Livewire\Admin\AI\AITaskConfiguration as AdminAITaskConfiguration;
use App\Http\Livewire\Admin\AI\AIUsageDashboard as AdminAIUsageDashboard;
use App\Http\Livewire\Admin\AI\AIAuditLog as AdminAIAuditLog;
use App\Http\Livewire\Admin\AI\AISafetyDashboard as AdminAISafetyDashboard;
use App\Http\Livewire\Admin\AI\AIReviewQueue as AdminAIReviewQueue;
use App\Http\Livewire\Admin\AI\AIQualityMetrics as AdminAIQualityMetrics;
use App\Http\Livewire\AI\AIAssistantChat as AdminAssistantChat;
use App\Http\Livewire\AI\PromptTemplateManager as AdminPromptTemplates;
use App\Http\Livewire\AI\KnowledgeBase as AdminKnowledgeBase;
use App\Http\Livewire\AI\WorkflowBuilder as AdminWorkflowBuilder;
use App\Http\Livewire\AI\ClientAssistantChat as ClientAssistantChat;
use App\Http\Livewire\Technical\CodeReviewer as AdminCodeReviewer;
use App\Http\Livewire\Technical\ArchitectureAdvisor as AdminArchitectureAdvisor;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PushSubscriptionController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Public share links
Route::get('/share/{token}', [DocumentShareController::class, 'download'])->name('documents.share.download');

// Interactive API documentation (Scramble)
Route::middleware(['auth', 'verified', 'permission:access admin panel'])
    ->get('/api/documentation', function (Generator $generator) {
        $config = Scramble::configure();
        $spec = $generator($config);
        return view('api-docs', compact('spec', 'config'));
    })
    ->name('api.documentation');

Route::middleware(['auth', 'verified', 'permission:access admin panel'])
    ->get('/api/documentation.json', function (Generator $generator) {
        $config = Scramble::configure();
        return response()->json($generator($config), options: JSON_PRETTY_PRINT);
    })
    ->name('api.documentation.json');

/*
|--------------------------------------------------------------------------
| Webhook Routes (No CSRF)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe')
    ->withoutMiddleware(['web']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Service Requests
    Route::resource('requests', RequestController::class);
    Route::get('/requests/{request}/estimate', EstimateApproval::class)->name('client.requests.estimate');

    // Contracts
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('/contracts/{contract}/download', [ContractController::class, 'download'])->name('contracts.download');
    Route::post('/contracts/{contract}/sign', [ContractController::class, 'sign'])->name('contracts.sign');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Payments
    Route::get('/invoices/{invoice}/pay', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/invoices/{invoice}/pay', [PaymentController::class, 'process'])->name('payments.process');

    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{document}/ai', DocumentAIAnalysis::class)->name('documents.ai');
    Route::get('/documents/{document}/chat', DocumentChat::class)->name('documents.chat');
    Route::get('/documents/chat', DocumentChat::class)->name('documents.chat.all');
    Route::get('/documents/{document}/summarize', SummarizeDocument::class)->name('documents.summarize');
    Route::get('/documents/{document}/open/{viewer?}', [DocumentViewerController::class, 'openDocument'])
        ->whereIn('viewer', ['office', 'google'])
        ->name('documents.viewer.document');
    Route::get('/documents/{document}/workflow', DocumentWorkflow::class)->name('documents.workflow');
    Route::get('/documents/smart-browser', SmartDocumentBrowser::class)->name('documents.smart-browser');
    Route::get('/documents/templates', DocumentTemplates::class)->name('documents.templates');
    Route::get('/documents/versions/{documentVersion}/download', [DocumentVersionController::class, 'download'])->name('documents.versions.download');
    Route::get('/documents/storage-files/{storageFile}/open/{viewer?}', [DocumentViewerController::class, 'openStorageFile'])
        ->whereIn('viewer', ['office', 'google'])
        ->name('documents.viewer.storage-file');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Two-factor setup
    Route::get('/two-factor/setup', TwoFactorSetup::class)->name('two-factor.setup');

    // Storage (client unified interface)
    Route::get('/storage', StorageDashboard::class)->name('storage.dashboard');
    Route::get('/storage/browser', UnifiedFileBrowser::class)->name('storage.browser');
    Route::get('/storage/settings', StorageSettings::class)->name('storage.settings');
    Route::get('/storage/conflicts', StorageConflicts::class)->name('storage.conflicts');
    Route::get('/storage/files/{storageFile}/download', [StorageFileController::class, 'download'])->name('storage.files.download');

    // Client advanced features
    Route::get('/projects', ProjectDashboard::class)->name('client.projects');
    Route::get('/messages', Messaging::class)->name('client.messaging');
    Route::get('/knowledge-base', KnowledgeBase::class)->name('client.knowledge-base');
    Route::get('/notifications', NotificationsCenter::class)->name('client.notifications');
    Route::get('/analytics', AnalyticsDashboard::class)->name('client.analytics');
    Route::get('/onboarding', OnboardingWizard::class)->name('client.onboarding');
    Route::get('/meetings', MeetingScheduler::class)->name('client.meetings');

    // Research & consultation tools
    Route::get('/research', ResearchAssistantTool::class)->name('research.assistant');
    Route::get('/research/technical', TechnicalAdvisorTool::class)->name('research.technical');
    Route::get('/research/monitor', IndustryMonitorTool::class)->name('research.monitor');
    Route::get('/research/competitors', CompetitorMonitorTool::class)->name('research.competitors');
    Route::get('/research/insights', IndustryInsightsTool::class)->name('research.insights');

    // Client AI assistant
    Route::get('/assistant', ClientAssistantChat::class)->name('client.ai.assistant');
    Route::get('/reports', ClientReportDashboard::class)->name('client.reports');
    Route::get('/proposals/{proposal}', ProposalViewer::class)->name('client.proposals.view');
    Route::get('/surveys/respond/{token}', FeedbackCollector::class)->name('client.surveys.respond');

    // PWA push notification subscriptions (per-user)
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');

});

/*
|--------------------------------------------------------------------------
| Admin Reporting Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'permission:access admin panel', 'admin.ip_allowlist', 'admin.2fa'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Requests
        Route::get('/requests', AdminRequestManagement::class)->name('requests.index');
        Route::get('/requests/create', AdminRequestCreate::class)->name('requests.create');
        Route::get('/requests/{request}', AdminRequestDetail::class)->name('requests.show');
        Route::get('/requests/{request}/estimator', AdminProjectEstimator::class)->name('requests.estimator');

        // Invoices
        Route::get('/invoices', AdminInvoiceManagement::class)->name('invoices.index');
        Route::get('/invoices/create', AdminInvoiceCreate::class)->name('invoices.create');
        Route::get('/invoices/{invoice}', AdminInvoiceEdit::class)->name('invoices.edit');

        // Contracts (AI)
        Route::get('/contracts/generator', AdminContractGenerator::class)->name('contracts.generator');
        Route::get('/meeting-notes', AdminMeetingNotes::class)->name('meeting-notes');
        Route::get('/communication/email-assistant', EmailDraftAssistant::class)->name('communication.email-assistant');

        // Reports
        Route::get('/reports', ReportDashboard::class)->name('reports.dashboard')->middleware('permission:view reports');
        Route::get('/white-label', WhiteLabelConfigurator::class)->name('white-label')->middleware('permission:manage settings');
        Route::get('/client-reports', ReportCustomizer::class)->name('client-reports')->middleware('permission:manage settings');

        // Proposals
        Route::get('/proposals/builder/{proposal?}', ProposalBuilder::class)->name('proposals.builder');
        Route::get('/proposals/analytics/{proposal}', ProposalAnalytics::class)->name('proposals.analytics');
        Route::get('/meetings', MeetingScheduler::class)->name('meetings');

        // Projects & time tracking
        Route::get('/projects/time', AdminTimeTracker::class)->name('projects.time');
        Route::get('/projects/board', AdminTaskBoard::class)->name('projects.board');
        Route::get('/projects/timeline', AdminProjectTimeline::class)->name('projects.timeline');
        Route::get('/projects/workload', AdminTeamWorkload::class)->name('projects.workload');

        // Feedback
        Route::get('/feedback/surveys', SurveyBuilder::class)->name('feedback.surveys');
        Route::get('/feedback/testimonials', TestimonialManager::class)->name('feedback.testimonials');

        // Account management
        Route::get('/account/health', AccountHealthDashboard::class)->name('account.health');
        Route::get('/account/qbrs', QBRBuilder::class)->name('account.qbrs');
        Route::get('/account/renewals', RenewalManager::class)->name('account.renewals');
        Route::get('/account/upsells', UpsellTracker::class)->name('account.upsells');

        // Partners & referrals
        Route::get('/partners', PartnerManager::class)->name('partners');
        Route::get('/referrals', ReferralDashboard::class)->name('referrals');

        // Marketing: Website auditing (MVP UI)
        Route::prefix('marketing')->name('marketing.')->group(function () {
            Route::get('/website-auditor', MarketingWebsiteAuditor::class)->name('website-auditor');
            Route::get('/audit-results', MarketingAuditResults::class)->name('audit-results');
            Route::get('/website-audits/{websiteAudit}/pdf', [WebsiteAuditController::class, 'pdf'])->name('website-audits.pdf');
        });

        // AI analytics
        Route::get('/analytics/ai-insights', AdminAIInsightsDashboard::class)->name('analytics.ai-insights')->middleware('permission:view reports');
        Route::get('/analytics/predictive', AdminPredictiveCharts::class)->name('analytics.predictive')->middleware('permission:view reports');
        Route::get('/analytics/client-health', AdminClientHealthMonitor::class)->name('analytics.client-health')->middleware('permission:view reports');

        // AI admin
        Route::get('/ai/providers', AdminAIProviderManagement::class)->name('ai.providers');
        Route::get('/ai/providers/create', AdminAIProviderForm::class)->name('ai.providers.create');
        Route::get('/ai/providers/{provider}', AdminAIProviderForm::class)->name('ai.providers.edit');
        Route::get('/ai/tasks', AdminAITaskConfiguration::class)->name('ai.tasks');
        Route::get('/ai/usage', AdminAIUsageDashboard::class)->name('ai.usage');
        Route::get('/ai/audit', AdminAIAuditLog::class)->name('ai.audit');
        Route::get('/ai/safety', AdminAISafetyDashboard::class)->name('ai.safety');
        Route::get('/ai/review-queue', AdminAIReviewQueue::class)->name('ai.review-queue');
        Route::get('/ai/quality', AdminAIQualityMetrics::class)->name('ai.quality');

        // AI assistant & training tools
        Route::get('/ai/assistant', AdminAssistantChat::class)->name('ai.assistant');
        Route::get('/ai/prompt-templates', AdminPromptTemplates::class)->name('ai.prompt-templates');
        Route::get('/ai/knowledge-base', AdminKnowledgeBase::class)->name('ai.knowledge-base');
        Route::get('/ai/workflows', AdminWorkflowBuilder::class)->name('ai.workflows');

        // Technical tools
        Route::get('/technical/code-review', AdminCodeReviewer::class)->name('technical.code-review');
        Route::get('/technical/architecture', AdminArchitectureAdvisor::class)->name('technical.architecture');

        // Export endpoints
        Route::get('/reports/export/{category}/{format}', [AdminReportExportController::class, 'export'])
            ->whereIn('category', ['financial', 'clients', 'requests', 'performance', 'storage'])
            ->whereIn('format', ['csv', 'xlsx', 'pdf'])
            ->name('reports.export')
            ->middleware('permission:view reports');

        // Admin storage overview
        Route::get('/storage/overview', StorageOverview::class)->name('storage.overview');

        // System settings
        Route::get('/settings', SystemSettings::class)->name('settings.index')->middleware('permission:manage settings');

        // Webhooks
        Route::get('/webhooks', WebhookManagement::class)->name('webhooks.index')->middleware('permission:manage settings');

        // Automation
        Route::get('/automation', AutomationIndex::class)->name('automation.index');
        Route::get('/automation/builder/{rule?}', AutomationBuilder::class)->name('automation.builder');
        Route::get('/automation/logs', AutomationLogs::class)->name('automation.logs');
    });

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
