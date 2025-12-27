<?php

namespace Tests;

use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // If your CI environment needs a custom Chrome/Chromedriver setup,
        // configure it here. (See Laravel Dusk docs.)
    }

    /**
     * Get the default driver options for Chrome.
     *
     * @return array<int, mixed>
     */
    protected function driverOptions(): array
    {
        $options = parent::driverOptions();

        // Typical CI flags; keep them conservative.
        return Collection::make($options)->merge([
            '--disable-dev-shm-usage',
            '--no-sandbox',
        ])->all();
    }
}
