<?php

namespace Tests\Support;

/**
 * Laravel skips CSRF checks when running unit tests.
 *
 * For security tests, we want to assert CSRF behavior, so we override the
 * internal "running unit tests" detection.
 */
class VerifyCsrfTokenForTests extends \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}
