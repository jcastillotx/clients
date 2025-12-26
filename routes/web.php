<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\Admin\AdminReportExportController;
use App\Http\Controllers\Storage\StorageFileController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use App\Http\Controllers\Documents\DocumentShareController;
use App\Http\Controllers\Documents\DocumentVersionController;
use App\Http\Controllers\Documents\DocumentViewerController;
use App\Http\Livewire\Admin\Reports\ReportDashboard;
use App\Http\Livewire\Admin\Settings\SystemSettings;
use App\Http\Livewire\Storage\StorageDashboard;
use App\Http\Livewire\Storage\StorageConflicts;
use App\Http\Livewire\Storage\UnifiedFileBrowser;
use App\Http\Livewire\Storage\StorageSettings;
use App\Http\Livewire\Admin\Storage\StorageOverview;
use App\Http\Livewire\Documents\DocumentWorkflow;
use App\Http\Livewire\Documents\SmartDocumentBrowser;
use App\Http\Livewire\Documents\DocumentTemplates;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Public share links
Route::get('/share/{token}', [DocumentShareController::class, 'download'])->name('documents.share.download');

/*
|--------------------------------------------------------------------------
| Webhook Routes (No CSRF)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe')
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
    Route::get('/documents/{document}/open/{viewer?}', [DocumentViewerController::class, 'openDocument'])
        ->whereIn('viewer', ['office', 'google'])
        ->name('documents.viewer.document');
    Route::get('/documents/{document}/workflow', DocumentWorkflow::class)->name('documents.workflow');
    Route::get('/documents/smart-browser', SmartDocumentBrowser::class)->name('documents.smart-browser');
    Route::get('/documents/templates', DocumentTemplates::class)->name('documents.templates');
    Route::get('/documents/versions/{documentVersion}/download', [DocumentVersionController::class, 'download'])->name('documents.versions.download');
    Route::get('/documents/storage-files/{storageFile}/open/{viewer?}', [DocumentViewerController::class, 'openStorageFile'])
        ->whereIn('viewer', ['office', 'google'])
        ->name('documents.viewer.storage-file');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Storage (client unified interface)
    Route::get('/storage', StorageDashboard::class)->name('storage.dashboard');
    Route::get('/storage/browser', UnifiedFileBrowser::class)->name('storage.browser');
    Route::get('/storage/settings', StorageSettings::class)->name('storage.settings');
    Route::get('/storage/conflicts', StorageConflicts::class)->name('storage.conflicts');
    Route::get('/storage/files/{storageFile}/download', [StorageFileController::class, 'download'])->name('storage.files.download');

});

/*
|--------------------------------------------------------------------------
| Admin Reporting Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'permission:access admin panel'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Reports
        Route::get('/reports', ReportDashboard::class)->name('reports.dashboard')->middleware('permission:view reports');

        // Export endpoints
        Route::get('/reports/export/{category}/{format}', [AdminReportExportController::class, 'export'])
            ->whereIn('category', ['financial', 'clients', 'requests', 'performance', 'storage'])
            ->whereIn('format', ['csv', 'xlsx', 'pdf'])
            ->name('reports.export')
            ->middleware('permission:view reports');

        // Admin storage overview
        Route::get('/storage/overview', StorageOverview::class)->name('storage.overview');

        // System settings
        Route::get('/settings', SystemSettings::class)->name('settings.index')->middleware('permission:manage settings');
    });

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
