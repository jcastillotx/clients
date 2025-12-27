<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Database\Seeders\RoleAndPermissionSeeder;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\StorageConnection;
use App\Models\User;
use App\Services\AdminReports\ReportScheduleRunner;
use App\Services\Storage\StorageSyncScheduler;
use App\Services\AutomationEngine;
use App\Services\WebhookService;
use App\Services\AI\RequestEmbeddingService;
use App\Services\Marketing\Scheduling\WebsiteAuditScheduleRunner;
use App\Models\Request as ServiceRequest;
use App\Jobs\Analytics\UpdateClientHealthScoresJob;
use App\Jobs\Analytics\GenerateWeeklyTrendReportJob;
use App\Jobs\Analytics\GenerateMonthlyRevenueForecastJob;
use App\Jobs\Analytics\GenerateQuarterlyBusinessIntelligenceReportJob;
use App\Jobs\Security\PurgeOldAuditLogsJob;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| Production bootstrap utilities
|--------------------------------------------------------------------------
*/

Artisan::command('portal:bootstrap-admin {email : Admin email address} {--name=Admin User} {--password=} {--force : Update password if user already exists}', function () {
    $email = (string) $this->argument('email');
    $name = (string) $this->option('name');
    $password = (string) ($this->option('password') ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Invalid email address.');
        return 1;
    }

    if ($password === '') {
        // Generate a strong password if not provided (prints once to console).
        $password = Str::password(24);
        $this->warn('No password provided. Generated a password (store it securely):');
        $this->line($password);
    }

    if (mb_strlen($password) < 16) {
        $this->error('Password must be at least 16 characters.');
        return 1;
    }

    // Ensure roles/permissions exist (idempotent).
    $this->call('db:seed', [
        '--class' => RoleAndPermissionSeeder::class,
        '--force' => true,
    ]);

    $user = User::query()->where('email', $email)->first();
    $force = (bool) $this->option('force');

    if ($user) {
        if (!$force) {
            $this->error("User already exists: {$email}. Re-run with --force to update name/password.");
            return 1;
        }

        $user->fill([
            'name' => $name !== '' ? $name : ($user->name ?? 'Admin User'),
            'password' => Hash::make($password),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'is_active' => true,
        ])->save();
    } else {
        $user = User::create([
            'name' => $name !== '' ? $name : 'Admin User',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    $user->assignRole('admin');

    $this->info("Admin ready: {$email}");
    return 0;
})->purpose('Create/repair the initial admin account (safe for production)');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Check for overdue invoices daily
Schedule::call(function () {
    Invoice::where('status', 'sent')
        ->where('due_date', '<', now())
        ->update(['status' => 'overdue']);
})->daily()->name('check-overdue-invoices');

// Scheduled automation triggers
Schedule::call(function () {
    app(AutomationEngine::class)->run('schedule.daily', ['meta' => ['date' => now()->toDateString()]]);
})->daily()->name('automation-schedule-daily');

Schedule::call(function () {
    app(AutomationEngine::class)->run('schedule.weekly', ['meta' => ['date' => now()->toDateString()]]);
})->weekly()->name('automation-schedule-weekly');

Schedule::call(function () {
    app(AutomationEngine::class)->run('schedule.monthly', ['meta' => ['date' => now()->toDateString()]]);
})->monthly()->name('automation-schedule-monthly');

// Trigger contract expiring webhooks daily (simple daily reminder until expiration)
Schedule::call(function () {
    $contracts = Contract::expiringSoon(30)->get();
    foreach ($contracts as $c) {
        // Automation trigger
        app(AutomationEngine::class)->run('contract.expiring', [
            'contract' => $c->toArray(),
            'client' => $c->client?->toArray(),
        ], (int) $c->client_id);

        app(WebhookService::class)->triggerWebhook('contract.expiring', [
            'id' => $c->id,
            'client_id' => $c->client_id,
            'contract_number' => $c->contract_number,
            'title' => $c->title,
            'status' => $c->status,
            'end_date' => optional($c->end_date)->toDateString(),
            'days_until_expiration' => $c->days_until_expiration,
        ], (int) $c->client_id);
    }
})->daily()->name('contract-expiring-webhooks');

// Invoice due date approaching (7 days) automation trigger
Schedule::call(function () {
    $cutoff = now()->addDays(7)->toDateString();
    $invoices = Invoice::query()
        ->whereIn('status', ['sent', 'overdue'])
        ->whereDate('due_date', $cutoff)
        ->get();

    foreach ($invoices as $inv) {
        app(AutomationEngine::class)->run('invoice.due_approaching', [
            'invoice' => $inv->toArray(),
            'client' => $inv->client?->toArray(),
            'meta' => ['days_before' => 7],
        ], (int) $inv->client_id);
    }
})->daily()->name('automation-invoice-due-approaching');

// Storage quota reached automation trigger (>=80%)
Schedule::call(function () {
    $connections = StorageConnection::query()
        ->where('status', 'active')
        ->whereNotNull('quota_bytes')
        ->get();

    foreach ($connections as $conn) {
        $quota = (int) $conn->quota_bytes;
        if ($quota <= 0) {
            continue;
        }
        $used = (int) $conn->used_bytes;
        $percent = (int) floor(($used / $quota) * 100);
        if ($percent >= 80) {
            app(AutomationEngine::class)->run('storage.quota_reached', [
                'storage' => [
                    'connection_id' => $conn->id,
                    'provider' => $conn->provider,
                    'name' => $conn->name,
                    'used_bytes' => $used,
                    'quota_bytes' => $quota,
                    'percent' => $percent,
                ],
                'client' => $conn->client?->toArray(),
            ], (int) $conn->client_id);
        }
    }
})->daily()->name('automation-storage-quota-reached');

// Send scheduled admin reports (requires mail configuration)
Schedule::call(function () {
    app(ReportScheduleRunner::class)->runDueSchedules();
})->everyFiveMinutes()->name('send-scheduled-admin-reports');

// Purge old audit/activity logs
Schedule::call(function () {
    PurgeOldAuditLogsJob::dispatch();
})->daily()->name('purge-old-audit-logs');

// Run scheduled website audits (requires queue worker)
Schedule::call(function () {
    app(WebsiteAuditScheduleRunner::class)->runDueSchedules();
})->everyFiveMinutes()->name('run-scheduled-website-audits');

// Auto-sync connected storage providers (requires queue worker)
Schedule::call(function () {
    app(StorageSyncScheduler::class)->dispatchDue();
})->everyFiveMinutes()->name('storage-auto-sync');

// AI analytics: update client health scores daily
Schedule::call(function () {
    UpdateClientHealthScoresJob::dispatch();
})->daily()->name('ai-analytics-client-health-daily');

// AI analytics: weekly trends report
Schedule::call(function () {
    GenerateWeeklyTrendReportJob::dispatch();
})->weekly()->name('ai-analytics-weekly-trends');

// AI analytics: monthly forecast report
Schedule::call(function () {
    GenerateMonthlyRevenueForecastJob::dispatch();
})->monthly()->name('ai-analytics-monthly-forecast');

// AI analytics: quarterly business intelligence report
Schedule::call(function () {
    GenerateQuarterlyBusinessIntelligenceReportJob::dispatch();
})->quarterly()->name('ai-analytics-quarterly-bi');

/*
|--------------------------------------------------------------------------
| AI / Embeddings Utilities
|--------------------------------------------------------------------------
*/

Artisan::command('ai:embeddings:backfill {--limit=200} {--provider=openai} {--model=text-embedding-3-small}', function () {
    $limit = (int) $this->option('limit');
    $provider = (string) $this->option('provider');
    $model = (string) $this->option('model');

    $svc = app(RequestEmbeddingService::class);

    $count = 0;
    $skipped = 0;
    $q = ServiceRequest::query()->orderByDesc('id')->limit(max(1, $limit));
    foreach ($q->cursor() as $req) {
        $row = $svc->upsertRequestEmbedding($req, [
            'provider' => $provider,
            'model' => $model,
            'timeout' => 45,
        ]);
        if ($row) {
            $count++;
            $this->line("Embedded request #{$req->id}");
        } else {
            $skipped++;
        }
    }

    $this->info("Done. Embedded={$count}, skipped={$skipped}.");
})->purpose('Backfill semantic embeddings for past requests');

/*
|--------------------------------------------------------------------------
| Brand Monitoring - Free API Integrations
|--------------------------------------------------------------------------
*/

// Monitor news mentions (NewsAPI + Google News RSS)
Schedule::call(function () {
    $newsService = app(\App\Services\BrandMonitoring\NewsMonitoringService::class);

    foreach (\App\Models\Client::where('is_active', true)->cursor() as $client) {
        try {
            $newsService->searchNewsAPI($client);
            $newsService->searchGoogleNewsRSS($client);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('News monitoring failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
})->hourly()->name('brand-monitoring-news');

// Monitor reviews (Yelp + Google Places)
Schedule::call(function () {
    $reviewService = app(\App\Services\BrandMonitoring\ReviewMonitoringService::class);

    foreach (\App\Models\Client::where('is_active', true)->cursor() as $client) {
        try {
            $reviewService->getYelpReviews($client);
            $reviewService->getGooglePlacesReviews($client);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Review monitoring failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
})->everySixHours()->name('brand-monitoring-reviews');

// Monitor social media (Reddit, YouTube, Twitter RSS)
Schedule::call(function () {
    $socialService = app(\App\Services\BrandMonitoring\SocialMonitoringService::class);

    foreach (\App\Models\Client::where('is_active', true)->cursor() as $client) {
        try {
            $socialService->searchReddit($client);
            $socialService->searchYouTube($client);
            $socialService->searchTwitterRSS($client);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Social monitoring failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
})->everyThirtyMinutes()->name('brand-monitoring-social');

// Monitor web mentions (Google Custom Search, Bing)
Schedule::call(function () {
    $webService = app(\App\Services\BrandMonitoring\WebMentionService::class);

    foreach (\App\Models\Client::where('is_active', true)->cursor() as $client) {
        try {
            $webService->searchGoogle($client);
            $webService->searchBing($client);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Web mention monitoring failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
})->everyTwoHours()->name('brand-monitoring-web-mentions');

// Batch sentiment analysis (runs every 30 minutes)
Schedule::call(function () {
    $sentimentService = app(\App\Services\BrandMonitoring\SentimentAnalysisService::class);

    try {
        $result = $sentimentService->analyzePendingSentiments();
        \Illuminate\Support\Facades\Log::info('Sentiment analysis completed', $result);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Sentiment analysis failed', [
            'error' => $e->getMessage(),
        ]);
    }
})->everyThirtyMinutes()->name('brand-monitoring-sentiment-analysis');

/*
|--------------------------------------------------------------------------
| Social Media Publishing
|--------------------------------------------------------------------------
*/

// Publish scheduled social media posts
Schedule::command('social:publish-scheduled')
    ->everyFiveMinutes()
    ->name('social-media-publish-scheduled');

