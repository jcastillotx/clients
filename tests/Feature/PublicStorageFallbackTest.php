<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageFallbackTest extends TestCase
{
    public function test_it_serves_files_from_public_disk_via_storage_prefix(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/hello.txt', 'hello');

        $this->get('/storage/branding/hello.txt')
            ->assertOk()
            ->assertHeader('Cache-Control', 'public, max-age=31536000')
            ->assertSee('hello');
    }

    public function test_it_blocks_path_traversal_attempts(): void
    {
        Storage::fake('public');

        $this->get('/storage/../.env')->assertNotFound();
        $this->get('/storage/%2e%2e/.env')->assertNotFound();
    }
}

