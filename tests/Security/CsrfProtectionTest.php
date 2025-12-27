<?php

namespace Tests\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\VerifyCsrfTokenForTests;
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_routes_reject_missing_csrf_token(): void
    {
        Route::middleware(['web', VerifyCsrfTokenForTests::class])
            ->post('/__csrf-test', fn () => response('ok', 200))
            ->name('tests.csrf');

        $this->post('/__csrf-test')->assertStatus(419);
    }
}
