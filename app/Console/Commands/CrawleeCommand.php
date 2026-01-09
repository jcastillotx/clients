<?php

namespace App\Console\Commands;

use App\Services\Marketing\Crawling\CrawleeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class CrawleeCommand extends Command
{
    protected $signature = 'crawlee
                            {action=status : Action to perform (status|start|stop|test|crawl)}
                            {--url= : URL to crawl (for test/crawl actions)}
                            {--lite : Start in lite mode (Cheerio only, no Playwright)}
                            {--low-memory : Start with reduced memory usage}';

    protected $description = 'Manage the Crawlee web scraping service';

    private string $servicePath;

    public function __construct()
    {
        parent::__construct();
        $this->servicePath = base_path('crawlee-service');
    }

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'status' => $this->showStatus(),
            'start' => $this->startService(),
            'stop' => $this->stopService(),
            'test' => $this->testCrawl(),
            'crawl' => $this->runCrawl(),
            default => $this->invalidAction($action),
        };
    }

    private function showStatus(): int
    {
        $this->info('Crawlee Service Status');
        $this->line('─────────────────────────────────────');

        $crawlee = new CrawleeService();
        $status = $crawlee->getStatus();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Enabled', $status['enabled'] ? '<fg=green>Yes</>' : '<fg=red>No</>'],
                ['Configured', $status['configured'] ? '<fg=green>Yes</>' : '<fg=yellow>No</>'],
                ['Service URL', $status['base_url']],
                ['Fallback Enabled', $status['fallback_enabled'] ? '<fg=green>Yes</>' : '<fg=yellow>No</>'],
            ]
        );

        // Check if service is running
        $this->newLine();
        $this->info('Service Health');
        $this->line('─────────────────────────────────────');

        if (!$status['enabled']) {
            $this->warn('Service is disabled in configuration.');
            $this->line('Enable it by setting CRAWLEE_ENABLED=true in .env');
            return 0;
        }

        if ($status['healthy']) {
            $this->info('✓ Crawlee service is running and healthy');

            // Get detailed info
            $details = $crawlee->getHealthDetails();
            if (isset($details['uptime'])) {
                $this->line("  Uptime: {$details['uptime']}");
            }
            if (isset($details['memory'])) {
                $this->line("  Memory: {$details['memory']}");
            }
        } else {
            $this->error('✗ Crawlee service is not responding');
            $this->line('');
            $this->line('To start the service:');
            $this->line('  cd crawlee-service && npm start');
            $this->line('');
            $this->line('Or use: php artisan crawlee start');
        }

        return 0;
    }

    private function startService(): int
    {
        if (!is_dir($this->servicePath)) {
            $this->error('Crawlee service directory not found at: ' . $this->servicePath);
            return 1;
        }

        // Check if already running
        $crawlee = new CrawleeService();
        if ($crawlee->isHealthy()) {
            $this->warn('Crawlee service is already running.');
            return 0;
        }

        // Determine start command
        $startCmd = 'npm start';
        if ($this->option('lite')) {
            $startCmd = 'npm run start:lite';
            $this->info('Starting in LITE mode (Cheerio only)...');
        } elseif ($this->option('low-memory')) {
            $startCmd = 'npm run start:low-memory';
            $this->info('Starting in LOW MEMORY mode (256MB limit)...');
        }

        $this->info('Starting Crawlee service...');
        $this->line("Running: cd {$this->servicePath} && {$startCmd}");
        $this->newLine();

        // Start in background
        $this->warn('Starting service in background...');
        $this->line('');

        // Use nohup to run in background
        $fullCmd = "cd {$this->servicePath} && nohup {$startCmd} > /tmp/crawlee.log 2>&1 &";

        Process::run($fullCmd);

        // Wait and check
        sleep(2);

        if ($crawlee->isHealthy()) {
            $this->info('✓ Crawlee service started successfully!');
            $this->line('');
            $this->line('View logs: tail -f /tmp/crawlee.log');
        } else {
            $this->warn('Service may still be starting. Check logs:');
            $this->line('  tail -f /tmp/crawlee.log');
        }

        return 0;
    }

    private function stopService(): int
    {
        $this->info('Stopping Crawlee service...');

        // Find and kill the process
        $result = Process::run("pkill -f 'node.*crawlee-service'");

        sleep(1);

        $crawlee = new CrawleeService();
        if (!$crawlee->isHealthy()) {
            $this->info('✓ Crawlee service stopped.');
        } else {
            $this->warn('Service may still be running. Try: pkill -9 -f "node.*crawlee"');
        }

        return 0;
    }

    private function testCrawl(): int
    {
        $url = $this->option('url') ?? 'https://example.com';

        $this->info("Testing crawl: {$url}");
        $this->line('─────────────────────────────────────');

        $crawlee = new CrawleeService();
        $status = $crawlee->getStatus();

        $this->line("Crawler mode: " . ($status['healthy'] ? 'Crawlee' : 'Built-in fallback'));
        $this->newLine();

        $startTime = microtime(true);
        $result = $crawlee->smartCrawl($url, ['max_requests' => 1]);
        $duration = round((microtime(true) - $startTime) * 1000);

        if ($result['success'] ?? false) {
            $this->info("✓ Crawl successful ({$duration}ms)");
            $this->line("Crawler used: " . ($result['crawler_used'] ?? 'unknown'));
            $this->newLine();

            $page = $result['results'][0] ?? null;
            if ($page) {
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['URL', $page['url'] ?? $url],
                        ['Status', $page['status_code'] ?? 'N/A'],
                        ['Title', substr($page['title'] ?? 'N/A', 0, 50)],
                        ['Word Count', $page['word_count'] ?? 'N/A'],
                    ]
                );
            }
        } else {
            $this->error('✗ Crawl failed');
            $this->line('Error: ' . ($result['error'] ?? 'Unknown error'));
        }

        return ($result['success'] ?? false) ? 0 : 1;
    }

    private function runCrawl(): int
    {
        $url = $this->option('url');

        if (!$url) {
            $this->error('URL is required for crawl action.');
            $this->line('Usage: php artisan crawlee crawl --url=https://example.com');
            return 1;
        }

        $this->info("Crawling: {$url}");
        $this->line('─────────────────────────────────────');

        $crawlee = new CrawleeService();

        $this->line('Starting crawl (max 10 pages)...');
        $this->newLine();

        $startTime = microtime(true);
        $result = $crawlee->smartCrawl($url, [
            'max_requests' => 10,
            'follow_links' => true,
        ]);
        $duration = round((microtime(true) - $startTime) * 1000);

        if ($result['success'] ?? false) {
            $this->info("✓ Crawl completed ({$duration}ms)");
            $this->line("Crawler used: " . ($result['crawler_used'] ?? 'unknown'));
            $this->line("Pages crawled: " . ($result['total_pages'] ?? count($result['results'] ?? [])));
            $this->newLine();

            // Show pages
            $pages = $result['results'] ?? [];
            if (!empty($pages)) {
                $tableData = [];
                foreach (array_slice($pages, 0, 10) as $page) {
                    $tableData[] = [
                        substr($page['url'] ?? '', 0, 60),
                        $page['status_code'] ?? 'N/A',
                        substr($page['title'] ?? 'N/A', 0, 40),
                    ];
                }

                $this->table(['URL', 'Status', 'Title'], $tableData);
            }
        } else {
            $this->error('✗ Crawl failed');
            $this->line('Error: ' . ($result['error'] ?? 'Unknown error'));
        }

        return ($result['success'] ?? false) ? 0 : 1;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}");
        $this->line('');
        $this->line('Available actions:');
        $this->line('  status  - Show service status (default)');
        $this->line('  start   - Start the Crawlee service');
        $this->line('  stop    - Stop the Crawlee service');
        $this->line('  test    - Test crawl a URL');
        $this->line('  crawl   - Crawl a URL');
        $this->line('');
        $this->line('Options:');
        $this->line('  --url=URL      URL to crawl (for test/crawl)');
        $this->line('  --lite         Start in lite mode (no Playwright)');
        $this->line('  --low-memory   Start with 256MB memory limit');

        return 1;
    }
}
