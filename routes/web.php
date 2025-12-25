<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequestAttachmentController;
use App\Http\Controllers\Storage\DropboxOAuthController;
use App\Http\Controllers\Storage\GoogleDriveDownloadController;
use App\Http\Controllers\Storage\GoogleDriveOAuthController;
use App\Http\Controllers\Webhook\DropboxWebhookController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use App\Http\Livewire\Admin\Clients\ClientCreate;
use App\Http\Livewire\Admin\Clients\ClientDetail;
use App\Http\Livewire\Admin\Clients\ClientEdit;
use App\Http\Livewire\Admin\Clients\ClientManagement;
use App\Http\Livewire\Admin\Requests\AdminRequestDetail;
use App\Http\Livewire\Admin\Requests\AdminRequestManagement;
use App\Http\Livewire\Admin\Requests\RequestCreate as AdminRequestCreate;
use App\Http\Livewire\Admin\Invoices\AdminInvoiceManagement;
use App\Http\Livewire\Admin\Invoices\InvoiceCreate as AdminInvoiceCreate;
use App\Http\Livewire\Admin\Invoices\InvoiceEdit as AdminInvoiceEdit;
use App\Http\Livewire\Admin\Users\UserManagement as AdminUserManagement;
use App\Http\Livewire\Admin\Users\UserCreate as AdminUserCreate;
use App\Http\Livewire\Admin\Users\UserEdit as AdminUserEdit;
use App\Http\Livewire\Admin\Users\Permissions as AdminPermissions;
use App\Http\Livewire\Admin\Storage\StorageOverview as AdminStorageOverview;
use App\Http\Livewire\Admin\Reports\ReportDashboard as AdminReportDashboard;
use App\Http\Livewire\Admin\Reports\FinancialReport as AdminFinancialReport;
use App\Http\Livewire\Admin\Reports\ClientReport as AdminClientReport;
use App\Http\Livewire\Admin\Reports\RequestReport as AdminRequestReport;
use App\Http\Livewire\Admin\Reports\CustomReportBuilder as AdminCustomReportBuilder;
use App\Http\Livewire\Admin\Reports\PerformanceReport as AdminPerformanceReport;
use App\Http\Livewire\Admin\Reports\StorageReport as AdminStorageReport;
use App\Http\Livewire\Admin\Settings\SystemSettings as AdminSystemSettings;
use App\Http\Livewire\Storage\ConnectDropbox;
use App\Http\Livewire\Storage\ConnectGoogleDrive;
use App\Http\Livewire\Storage\ConnectS3;
use App\Http\Livewire\Storage\DropboxBrowser;
use App\Http\Livewire\Storage\GoogleDriveBrowser;
use App\Http\Livewire\Storage\S3Browser;
use App\Http\Livewire\Storage\StorageDashboard;
use App\Http\Livewire\Storage\StorageSettings;
use App\Http\Livewire\Storage\UnifiedFileBrowser;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Webhook Routes (No CSRF)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe')
    ->withoutMiddleware(['web']);

Route::get('/webhooks/dropbox', [DropboxWebhookController::class, 'verify'])
    ->name('webhooks.dropbox.verify')
    ->withoutMiddleware(['web']);
Route::post('/webhooks/dropbox', [DropboxWebhookController::class, 'handle'])
    ->name('webhooks.dropbox')
    ->withoutMiddleware(['web']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Service Requests
    Route::resource('requests', RequestController::class);
    Route::get('/requests/{request}/attachments/{attachment}/download', [RequestAttachmentController::class, 'download'])
        ->name('requests.attachments.download');

    // Contracts
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('/contracts/{contract}/download', [ContractController::class, 'download'])->name('contracts.download');
    Route::post('/contracts/{contract}/sign', [ContractController::class, 'sign'])->name('contracts.sign');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Payments
    Route::get('/invoices/{invoice}/pay', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/invoices/{invoice}/pay', [PaymentController::class, 'process'])->name('payments.process');

    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/view', [DocumentController::class, 'view'])->name('documents.view');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Storage: Dropbox OAuth (used by admin storage UI; can be opened in popup)
    Route::get('/storage/dropbox/authorize', [DropboxOAuthController::class, 'authorize'])->name('storage.dropbox.authorize');
    Route::get('/storage/dropbox/callback', [DropboxOAuthController::class, 'callback'])->name('storage.dropbox.callback');

    // Storage: Google Drive OAuth + download/export (used by admin storage UI; can be opened in popup)
    Route::get('/storage/google-drive/authorize', [GoogleDriveOAuthController::class, 'authorize'])->name('storage.google-drive.authorize');
    Route::get('/storage/google-drive/callback', [GoogleDriveOAuthController::class, 'callback'])->name('storage.google-drive.callback');
    Route::get('/storage/google-drive/{connection}/download', [GoogleDriveDownloadController::class, 'download'])->name('storage.google-drive.download');

});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'ensure.admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::view('/activity', 'admin.activity')->name('activity');

        // Clients
        Route::get('/clients', ClientManagement::class)->name('clients.index');
        Route::get('/clients/create', ClientCreate::class)->name('clients.create');
        Route::get('/clients/{client}', ClientDetail::class)->name('clients.show');
        Route::get('/clients/{client}/edit', ClientEdit::class)->name('clients.edit');

        // Requests
        Route::get('/requests', AdminRequestManagement::class)->name('requests.index');
        Route::get('/requests/create', AdminRequestCreate::class)->name('requests.create');
        Route::get('/requests/{request}', AdminRequestDetail::class)->name('requests.show');

        // Invoices
        Route::get('/invoices', AdminInvoiceManagement::class)->name('invoices.index');
        Route::get('/invoices/create', AdminInvoiceCreate::class)->name('invoices.create');
        Route::get('/invoices/{invoice}/edit', AdminInvoiceEdit::class)->name('invoices.edit');

        // Users & permissions
        Route::get('/users', AdminUserManagement::class)->name('users.index');
        Route::get('/users/create', AdminUserCreate::class)->name('users.create');
        Route::get('/users/{user}/edit', AdminUserEdit::class)->name('users.edit');
        Route::get('/users/permissions', AdminPermissions::class)->name('users.permissions');

        // Placeholder routes for the expanded admin navigation (wired to admin layout)
        Route::get('/contracts', fn () => view('admin.section', ['title' => 'Contracts Management']))->name('contracts');
        Route::get('/documents', fn () => view('admin.section', ['title' => 'Documents']))->name('documents');
            Route::get('/storage', StorageDashboard::class)->name('storage');
            Route::get('/storage/files', UnifiedFileBrowser::class)->name('storage.files');
            Route::get('/storage/settings', StorageSettings::class)->name('storage.settings');
            Route::get('/storage/overview', AdminStorageOverview::class)->name('storage.overview');
            Route::get('/storage/s3/connect', ConnectS3::class)->name('storage.s3.connect');
            Route::get('/storage/s3/browse/{connection?}', S3Browser::class)->name('storage.s3.browse');
            Route::get('/storage/dropbox/connect', ConnectDropbox::class)->name('storage.dropbox.connect');
            Route::get('/storage/dropbox/browse/{connection?}', DropboxBrowser::class)->name('storage.dropbox.browse');
            Route::get('/storage/google-drive/connect', ConnectGoogleDrive::class)->name('storage.google-drive.connect');
            Route::get('/storage/google-drive/browse/{connection?}', GoogleDriveBrowser::class)->name('storage.google-drive.browse');
        // Reports
        Route::get('/reports', AdminReportDashboard::class)->name('reports');
        Route::get('/reports/builder', AdminCustomReportBuilder::class)->name('reports.builder');
        Route::get('/reports/financial', AdminFinancialReport::class)->name('reports.financial');
        Route::get('/reports/clients', AdminClientReport::class)->name('reports.clients');
        Route::get('/reports/requests', AdminRequestReport::class)->name('reports.requests');
        Route::get('/reports/performance', AdminPerformanceReport::class)->name('reports.performance');
        Route::get('/reports/storage', AdminStorageReport::class)->name('reports.storage');
        Route::get('/settings', AdminSystemSettings::class)->name('settings');
    });

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
