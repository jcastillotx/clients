<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent lazy loading in development
        Model::preventLazyLoading(! app()->isProduction());

        // Livewire components (non-standard namespace path)
        Livewire::component('dashboard', \App\Http\Livewire\Dashboard::class);
        Livewire::component('requests.request-index', \App\Http\Livewire\Requests\RequestIndex::class);
        Livewire::component('requests.request-show', \App\Http\Livewire\Requests\RequestShow::class);
        Livewire::component('requests.request-create', \App\Http\Livewire\Requests\RequestCreate::class);
        Livewire::component('requests.request-edit', \App\Http\Livewire\Requests\RequestEdit::class);
        Livewire::component('requests.request-comments', \App\Http\Livewire\Requests\RequestComments::class);
        Livewire::component('invoices.invoice-index', \App\Http\Livewire\Invoices\InvoiceIndex::class);
        Livewire::component('invoices.invoice-show', \App\Http\Livewire\Invoices\InvoiceShow::class);
        Livewire::component('contracts.contract-index', \App\Http\Livewire\Contracts\ContractIndex::class);
        Livewire::component('contracts.contract-show', \App\Http\Livewire\Contracts\ContractShow::class);
        Livewire::component('documents.document-index', \App\Http\Livewire\Documents\DocumentIndex::class);
        Livewire::component('documents.document-upload', \App\Http\Livewire\Documents\DocumentUpload::class);
        Livewire::component('activity-feed', \App\Http\Livewire\ActivityFeed::class);
        Livewire::component('admin.activity-log-index', \App\Http\Livewire\Admin\ActivityLogIndex::class);
        Livewire::component('admin.dashboard', \App\Http\Livewire\Admin\Dashboard::class);

        // Custom Blade directives for client portal
        Blade::directive('money', function ($expression) {
            return "<?php echo '$' . number_format($expression, 2); ?>";
        });

        Blade::directive('status', function ($expression) {
            return "<?php echo ucfirst(str_replace('_', ' ', $expression)); ?>";
        });
    }
}
