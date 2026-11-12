<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\LicenseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LicenseController::class, 'index']);

Route::prefix('v1')->name('api.')->group(function (): void {
    Route::middleware('throttle:license-api')->controller(LicenseController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/activate', 'index');
        Route::get('/deactivate', 'index');
        Route::post('/activate', 'activate')->name('licenses.activate');
        Route::post('/deactivate', 'deactivate')->name('licenses.deactivate');
        Route::match(['get', 'post'], '/verify', 'verify')->name('licenses.verify');
        Route::get('/updates/check', 'update')->name('updates.check');
    });

    Route::middleware('throttle:api')->group(function (): void {
        Route::controller(CatalogController::class)->group(function (): void {
            Route::get('/plugins', 'index')->name('plugins.index');
            Route::get('/plugins/{plugin:public_id}', 'show')->name('plugins.show');
            Route::get('/plugins/{plugin:public_id}/versions', 'versions')->name('plugins.versions');
        });

        Route::middleware('auth:sanctum')->controller(AccountController::class)->group(function (): void {
            Route::get('/me', 'me')->name('me');
            Route::get('/licenses', 'licenses')->middleware('ability:licenses:read')->name('licenses.index');
            Route::get('/licenses/{license:key}/download', 'download')->middleware('ability:downloads:read')->name('licenses.download');
        });
    });
});
