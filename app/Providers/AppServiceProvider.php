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
        Livewire::component('admin.clients.management', \App\Http\Livewire\Admin\Clients\ClientManagement::class);
        Livewire::component('admin.clients.create', \App\Http\Livewire\Admin\Clients\ClientCreate::class);
        Livewire::component('admin.clients.edit', \App\Http\Livewire\Admin\Clients\ClientEdit::class);
        Livewire::component('admin.clients.detail', \App\Http\Livewire\Admin\Clients\ClientDetail::class);
        Livewire::component('admin.requests.management', \App\Http\Livewire\Admin\Requests\AdminRequestManagement::class);
        Livewire::component('admin.requests.create', \App\Http\Livewire\Admin\Requests\RequestCreate::class);
        Livewire::component('admin.requests.detail', \App\Http\Livewire\Admin\Requests\AdminRequestDetail::class);
        Livewire::component('admin.invoices.management', \App\Http\Livewire\Admin\Invoices\AdminInvoiceManagement::class);
        Livewire::component('admin.invoices.create', \App\Http\Livewire\Admin\Invoices\InvoiceCreate::class);
        Livewire::component('admin.invoices.edit', \App\Http\Livewire\Admin\Invoices\InvoiceEdit::class);
        Livewire::component('admin.users.management', \App\Http\Livewire\Admin\Users\UserManagement::class);
        Livewire::component('admin.users.create', \App\Http\Livewire\Admin\Users\UserCreate::class);
        Livewire::component('admin.users.edit', \App\Http\Livewire\Admin\Users\UserEdit::class);
        Livewire::component('admin.users.permissions', \App\Http\Livewire\Admin\Users\Permissions::class);
        Livewire::component('storage.connect-s3', \App\Http\Livewire\Storage\ConnectS3::class);
        Livewire::component('storage.s3-browser', \App\Http\Livewire\Storage\S3Browser::class);
        Livewire::component('storage.connect-dropbox', \App\Http\Livewire\Storage\ConnectDropbox::class);
        Livewire::component('storage.dropbox-browser', \App\Http\Livewire\Storage\DropboxBrowser::class);
        Livewire::component('storage.connect-google-drive', \App\Http\Livewire\Storage\ConnectGoogleDrive::class);
        Livewire::component('storage.google-drive-browser', \App\Http\Livewire\Storage\GoogleDriveBrowser::class);
        Livewire::component('storage.dashboard', \App\Http\Livewire\Storage\StorageDashboard::class);
        Livewire::component('storage.unified-browser', \App\Http\Livewire\Storage\UnifiedFileBrowser::class);
        Livewire::component('storage.settings', \App\Http\Livewire\Storage\StorageSettings::class);
        Livewire::component('admin.storage.overview', \App\Http\Livewire\Admin\Storage\StorageOverview::class);

        // Custom Blade directives for client portal
        Blade::directive('money', function ($expression) {
            return "<?php echo '$' . number_format($expression, 2); ?>";
        });

        Blade::directive('status', function ($expression) {
            return "<?php echo ucfirst(str_replace('_', ' ', $expression)); ?>";
        });
    }
}
