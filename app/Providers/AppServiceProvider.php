<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Services\AI\AIProviderManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AIProviderManager::class, fn () => new AIProviderManager());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent lazy loading in development
        Model::preventLazyLoading(! app()->isProduction());

        RateLimiter::for('api-token', function (Request $request) {
            $token = $request->user()?->currentAccessToken();
            $key = $token ? 'token:' . $token->id : 'ip:' . $request->ip();

            // Default: 60 req/min per token
            return Limit::perMinute(60)->by($key);
        });

        // Webhook observers
        \App\Models\Request::observe(\App\Observers\RequestObserver::class);
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);
        \App\Models\Contract::observe(\App\Observers\ContractObserver::class);
        \App\Models\Document::observe(\App\Observers\DocumentObserver::class);
        \App\Models\DocumentShare::observe(\App\Observers\DocumentShareObserver::class);
        \App\Models\Payment::observe(\App\Observers\PaymentObserver::class);

        // Custom Blade directives for client portal
        Blade::directive('money', function ($expression) {
            return "<?php echo '$' . number_format($expression, 2); ?>";
        });

        Blade::directive('status', function ($expression) {
            return "<?php echo ucfirst(str_replace('_', ' ', $expression)); ?>";
        });
    }
}
