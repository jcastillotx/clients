<?php

namespace Tests\Integration;

use App\Services\Storage\AwsS3Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwsS3FileOperationsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_connect_to_s3_and_list_objects_in_test_bucket(): void
    {
        if (! getenv('RUN_INTEGRATION_TESTS')) {
            $this->markTestSkipped('Set RUN_INTEGRATION_TESTS=1 to run external integration tests.');
        }

        $region = getenv('AWS_INTEGRATION_REGION') ?: '';
        $bucket = getenv('AWS_INTEGRATION_BUCKET') ?: '';
        $key = getenv('AWS_INTEGRATION_ACCESS_KEY_ID') ?: '';
        $secret = getenv('AWS_INTEGRATION_SECRET_ACCESS_KEY') ?: '';

        if ($region === '' || $bucket === '' || $key === '' || $secret === '') {
            $this->markTestSkipped('Missing AWS integration env vars (AWS_INTEGRATION_REGION, AWS_INTEGRATION_BUCKET, AWS_INTEGRATION_ACCESS_KEY_ID, AWS_INTEGRATION_SECRET_ACCESS_KEY).');
        }

        $svc = app(AwsS3Service::class);

        // save=false avoids writing a StorageConnection row; this is a pure connectivity check.
        $svc->connect([
            'save' => false,
            'access_key_id' => $key,
            'secret_access_key' => $secret,
            'region' => $region,
            'bucket' => $bucket,
            'folder_path' => getenv('AWS_INTEGRATION_PREFIX') ?: '',
        ]);

        $files = $svc->listFiles('');
        $this->assertIsArray($files);
    }
}
