<?php

use App\Http\Controllers\Api\V1\ApiTokenController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\RequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:api-token'])
    ->group(function () {
        // Token management (for authenticated user; restrict admin ability in controller)
        Route::post('/tokens', [ApiTokenController::class, 'store'])->name('api.v1.tokens.store');

        // Clients (admin-only create)
        Route::post('/clients', [ClientController::class, 'store'])
            ->middleware('token.any_ability:admin')
            ->name('api.v1.clients.store');
        Route::get('/clients/{client}', [ClientController::class, 'show'])
            ->middleware('token.any_ability:read,write,admin')
            ->name('api.v1.clients.show');

        // Requests
        Route::post('/requests', [RequestController::class, 'store'])
            ->middleware('token.any_ability:write,admin')
            ->name('api.v1.requests.store');
        Route::get('/requests/{requestModel}', [RequestController::class, 'show'])
            ->middleware('token.any_ability:read,write,admin')
            ->name('api.v1.requests.show');
        Route::put('/requests/{requestModel}/status', [RequestController::class, 'updateStatus'])
            ->middleware('token.any_ability:write,admin')
            ->name('api.v1.requests.status');

        // Invoices
        Route::post('/invoices', [InvoiceController::class, 'store'])
            ->middleware('token.any_ability:write,admin')
            ->name('api.v1.invoices.store');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])
            ->middleware('token.any_ability:read,write,admin')
            ->name('api.v1.invoices.show');

        // Documents
        Route::post('/documents/upload', [DocumentController::class, 'upload'])
            ->middleware('token.any_ability:write,admin')
            ->name('api.v1.documents.upload');
    });
