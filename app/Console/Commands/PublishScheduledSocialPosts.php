<?php

namespace App\Console\Commands;

use App\Services\Social\SocialMediaPublishingService;
use Illuminate\Console\Command;

class PublishScheduledSocialPosts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'social:publish-scheduled
                            {--dry-run : Run without actually publishing}';

    /**
     * The console command description.
     */
    protected $description = 'Publish scheduled social media posts that are ready';

    /**
     * Execute the console command.
     */
    public function handle(SocialMediaPublishingService $publishingService): int
    {
        $this->info('Starting scheduled social media post publishing...');

        if ($this->option('dry-run')) {
            $this->warn('Running in DRY RUN mode - no posts will be published');
        }

        try {
            $results = $publishingService->publishScheduledPosts();

            $this->info('Publishing completed:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Posts Checked', $results['total']],
                    ['Successfully Published', $results['published']],
                    ['Failed', $results['failed']],
                    ['Skipped', $results['skipped']],
                ]
            );

            if ($results['published'] > 0) {
                $this->info("✓ Successfully published {$results['published']} post(s)");
            }

            if ($results['failed'] > 0) {
                $this->error("✗ Failed to publish {$results['failed']} post(s)");

                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Publishing failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
