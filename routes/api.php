<?php

use App\Http\Controllers\Api\V1\ApiTokenController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\DocumentUploadController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\RequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Token management (requires existing token; intended for admins to mint integration tokens)
    Route::middleware(['auth:sanctum', 'token.any_ability:admin'])->group(function () {
        Route::post('/tokens', [ApiTokenController::class, 'store']);
        Route::delete('/tokens/{tokenId}', [ApiTokenController::class, 'destroy']);
    });

    // Clients
    Route::middleware(['auth:sanctum', 'throttle:api-token', 'token.any_ability:admin'])->group(function () {
        Route::post('/clients', [ClientController::class, 'store']);
    });
    Route::middleware(['auth:sanctum', 'throttle:api-token', 'token.any_ability:admin,read,write'])->group(function () {
        Route::get('/clients/{id}', [ClientController::class, 'show']);
    });

    // Requests
    Route::middleware(['auth:sanctum', 'throttle:api-token', 'token.any_ability:admin,write'])->group(function () {
        Route::post('/requests', [RequestController::class, 'store']);
        Route::put('/requests/{id}/status', [RequestController::class, 'updateStatus']);
    });
    Route::middleware(['auth:sanctum', 'throttle:api-token', 'token.any_ability:admin,read,write'])->group(function () {
        Route::get('/requests/{id}', [RequestController::class, 'show']);
    });

    // Invoices
    Route::middleware(['auth:sanctum', 'throttle:api-token', 'token.any_ability:admin,write'])->group(function () {
        Route::post('/invoices', [InvoiceController::class, 'store']);
    });
    Route::middleware(['auth:sanctum', 'throttle:api-token', 'token.any_ability:admin,read,write'])->group(function () {
        Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    });

    // Documents
    Route::middleware(['auth:sanctum', 'throttle:api-token', 'token.any_ability:admin,write'])->group(function () {
        Route::post('/documents/upload', [DocumentUploadController::class, 'store']);
    });
});

