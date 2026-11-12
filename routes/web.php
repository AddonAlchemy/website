<?php

declare(strict_types=1);

use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Storefront;
use App\Http\Controllers\Support\TicketController;
use App\Http\Controllers\Vendor;
use Illuminate\Support\Facades\Route;

Route::get('/', Storefront\HomeController::class)->name('home');

Route::controller(Storefront\PluginController::class)->group(function (): void {
    Route::get('/plugins', 'index')->name('plugins.index');
    Route::get('/plugins/{plugin}', 'show')->name('plugins.show');
});

Route::get('/search/suggest', Storefront\SearchController::class)->middleware('throttle:search')->name('search.suggest');

Route::controller(Storefront\VendorController::class)->group(function (): void {
    Route::get('/vendors', 'index')->name('vendors.index');
    Route::get('/vendors/{vendor}', 'show')->name('vendors.show');
});

Route::controller(Storefront\PageController::class)->group(function (): void {
    Route::get('/verify', 'licensing')->name('licensing.docs');
    Route::get('/{page}', 'legal')->whereIn('page', Storefront\PageController::legalSlugs())->name('legal.show');
});

Route::controller(Storefront\CartController::class)->group(function (): void {
    Route::get('/cart', 'show')->name('cart.show');
    Route::post('/cart/{plugin}', 'store')->name('cart.store');
    Route::delete('/cart/{plugin}', 'destroy')->name('cart.destroy');
    Route::delete('/cart', 'clear')->name('cart.clear');
});

Route::get('/downloads/{version}/file', [Storefront\DownloadController::class, 'file'])
    ->middleware(['signed', 'throttle:downloads'])
    ->name('downloads.file');

Route::controller(Storefront\LegacyRedirectController::class)->group(function (): void {
    Route::get('/shop', 'shop');
    Route::get('/product', 'product');
});
Route::permanentRedirect('/home', '/');
Route::permanentRedirect('/team', '/vendors');
Route::permanentRedirect('/login/admin', '/login');
Route::permanentRedirect('/licenses', '/account/licenses');
Route::permanentRedirect('/orders', '/account/orders');
Route::permanentRedirect('/account', '/account/profile');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [Auth\LoginController::class, 'create'])->name('login');
    Route::post('/login', [Auth\LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [Auth\RegisterController::class, 'create'])->name('register');
    Route::post('/register', [Auth\RegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.store');

    Route::controller(Auth\PasswordResetController::class)->group(function (): void {
        Route::get('/forgot-password', 'request')->name('password.request');
        Route::post('/forgot-password', 'email')->middleware('throttle:5,1')->name('password.email');
        Route::get('/reset-password/{token}', 'edit')->name('password.reset');
        Route::post('/reset-password', 'update')->name('password.update');
    });
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [Auth\LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', Account\DashboardController::class)->name('dashboard');

    Route::post('/checkout', Storefront\CheckoutController::class)->name('checkout.store');
    Route::post('/plugins/{plugin}/favorite', Storefront\FavoriteController::class)->name('plugins.favorite');
    Route::post('/plugins/{plugin}/reviews', [Storefront\ReviewController::class, 'store'])->name('plugins.reviews.store');
    Route::delete('/reviews/{review}', [Storefront\ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::get('/downloads/{version}', [Storefront\DownloadController::class, 'start'])
        ->middleware('throttle:downloads')
        ->name('downloads.start');

    Route::prefix('account')->name('account.')->group(function (): void {
        Route::controller(Account\ProfileController::class)->group(function (): void {
            Route::get('/profile', 'edit')->name('profile.edit');
            Route::put('/profile', 'update')->name('profile.update');
            Route::put('/password', 'password')->name('password.update');
        });

        Route::controller(Account\OrderController::class)->group(function (): void {
            Route::get('/orders', 'index')->name('orders.index');
            Route::get('/orders/{order}', 'show')->name('orders.show');
            Route::get('/invoices/{invoice}', 'invoice')->name('invoices.show');
        });

        Route::controller(Account\LicenseController::class)->group(function (): void {
            Route::get('/licenses', 'index')->name('licenses.index');
            Route::post('/licenses/redeem', 'redeem')->middleware('throttle:10,1')->name('licenses.redeem');
            Route::post('/licenses/{license}/rotate', 'rotate')->name('licenses.rotate');
            Route::delete('/licenses/{license}/ips', 'releaseIp')->name('licenses.release-ip');
            Route::delete('/activations/{activation}', 'destroyActivation')->name('activations.destroy');
        });

        Route::controller(Account\SubkeyController::class)->group(function (): void {
            Route::post('/subkeys', 'store')->name('subkeys.store');
            Route::patch('/subkeys/{subkey}/revoke', 'revoke')->name('subkeys.revoke');
            Route::patch('/subkeys/{subkey}/reactivate', 'reactivate')->name('subkeys.reactivate');
            Route::delete('/subkeys/{subkey}', 'destroy')->name('subkeys.destroy');
        });

        Route::get('/downloads', [Account\LibraryController::class, 'downloads'])->name('downloads.index');
        Route::get('/favorites', [Account\LibraryController::class, 'favorites'])->name('favorites.index');

        Route::controller(Account\ApiTokenController::class)->group(function (): void {
            Route::get('/api-tokens', 'index')->name('tokens.index');
            Route::post('/api-tokens', 'store')->name('tokens.store');
            Route::delete('/api-tokens/{token}', 'destroy')->whereNumber('token')->name('tokens.destroy');
        });

        Route::post('/notifications/read', Account\NotificationController::class)->name('notifications.read');
    });

    Route::controller(TicketController::class)->prefix('support')->name('support.')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('throttle:10,1')->name('store');
        Route::get('/{ticket}', 'show')->name('show');
        Route::post('/{ticket}/messages', 'reply')->name('reply');
        Route::patch('/{ticket}', 'status')->name('status');
    });

    Route::prefix('vendor')->name('vendor.')->group(function (): void {
        Route::get('/apply', [Vendor\ApplicationController::class, 'create'])->name('apply');
        Route::post('/apply', [Vendor\ApplicationController::class, 'store'])->name('apply.store');

        Route::middleware('vendor')->group(function (): void {
            Route::get('/', Vendor\DashboardController::class)->name('dashboard');
            Route::get('/profile', [Vendor\ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [Vendor\ProfileController::class, 'update'])->name('profile.update');
            Route::get('/tickets', Vendor\TicketController::class)->name('tickets.index');
            Route::get('/plugins', [Vendor\PluginController::class, 'index'])->name('plugins.index');

            Route::controller(Vendor\SettlementController::class)->group(function (): void {
                Route::get('/payouts', 'payouts')->name('payouts');
                Route::get('/settlements', 'index')->name('settlements.index');
                Route::get('/settlements/{settlement}', 'show')->name('settlements.show');
            });
        });

        Route::middleware('vendor:approved')->group(function (): void {
            Route::resource('plugins', Vendor\PluginController::class)->except(['index', 'show']);
            Route::post('/plugins/{plugin}/submit', [Vendor\PluginController::class, 'submit'])->name('plugins.submit');

            Route::post('/plugins/{plugin}/versions', [Vendor\VersionController::class, 'store'])->name('versions.store');
            Route::delete('/versions/{version}', [Vendor\VersionController::class, 'destroy'])->name('versions.destroy');

            Route::post('/plugins/{plugin}/screenshots', [Vendor\ScreenshotController::class, 'store'])->name('screenshots.store');
            Route::delete('/screenshots/{screenshot}', [Vendor\ScreenshotController::class, 'destroy'])->name('screenshots.destroy');

            Route::controller(Vendor\LicenseCodeController::class)->group(function (): void {
                Route::post('/plugins/{plugin}/license-codes', 'store')->name('codes.store');
                Route::patch('/license-codes/{code}/revoke', 'revoke')->name('codes.revoke');
                Route::delete('/license-codes/{code}', 'destroy')->name('codes.destroy');
            });

            Route::post('/reviews/{review}/reply', Vendor\ReviewReplyController::class)->name('reviews.reply');
        });
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::controller(Admin\ModerationController::class)->prefix('moderation')->name('moderation.')->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::post('/plugins/{plugin}/approve', 'approvePlugin')->name('plugins.approve');
            Route::post('/plugins/{plugin}/reject', 'rejectPlugin')->name('plugins.reject');
            Route::post('/versions/{version}/approve', 'approveVersion')->name('versions.approve');
            Route::post('/versions/{version}/reject', 'rejectVersion')->name('versions.reject');
        });

        Route::controller(Admin\PluginController::class)->group(function (): void {
            Route::get('/plugins', 'index')->name('plugins.index');
            Route::patch('/plugins/{plugin}/featured', 'feature')->name('plugins.feature');
            Route::delete('/plugins/{plugin}', 'destroy')->name('plugins.destroy');
        });

        Route::get('/vendors', [Admin\VendorController::class, 'index'])->name('vendors.index');
        Route::patch('/vendors/{vendor}', [Admin\VendorController::class, 'update'])->name('vendors.update');

        Route::controller(Admin\UserController::class)->group(function (): void {
            Route::get('/users', 'index')->name('users.index');
            Route::get('/users/{user}', 'show')->name('users.show');
            Route::patch('/users/{user}', 'update')->name('users.update');
            Route::delete('/users/{user}', 'destroy')->name('users.destroy');
            Route::post('/users/{user}/licenses', 'grantLicense')->name('users.licenses.store');
            Route::delete('/licenses/{license}', 'revokeLicense')->name('licenses.destroy');
        });

        Route::controller(Admin\OrderController::class)->group(function (): void {
            Route::get('/orders', 'index')->name('orders.index');
            Route::post('/orders/{order}/fulfill', 'fulfill')->name('orders.fulfill');
            Route::post('/orders/{order}/cancel', 'cancel')->name('orders.cancel');
        });

        Route::get('/settlements', [Admin\SettlementController::class, 'index'])->name('settlements.index');
        Route::patch('/settlements/{settlement}', [Admin\SettlementController::class, 'update'])->name('settlements.update');

        Route::controller(Admin\SaleController::class)->group(function (): void {
            Route::get('/sales', 'index')->name('sales.index');
            Route::post('/sales', 'store')->name('sales.store');
            Route::patch('/sales/{sale}', 'toggle')->name('sales.toggle');
            Route::delete('/sales/{sale}', 'destroy')->name('sales.destroy');
        });

        Route::controller(Admin\AnnouncementController::class)->group(function (): void {
            Route::get('/announcements', 'index')->name('announcements.index');
            Route::post('/announcements', 'store')->name('announcements.store');
            Route::delete('/announcements/{announcement}', 'destroy')->name('announcements.destroy');
        });

        Route::controller(Admin\CategoryController::class)->group(function (): void {
            Route::get('/categories', 'index')->name('categories.index');
            Route::post('/categories', 'store')->name('categories.store');
            Route::put('/categories/{category}', 'update')->name('categories.update');
            Route::delete('/categories/{category}', 'destroy')->name('categories.destroy');
        });

        Route::get('/activations', [Admin\MonitoringController::class, 'activations'])->name('activations.index');
        Route::get('/tickets', [Admin\MonitoringController::class, 'tickets'])->name('tickets.index');

        Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
    });
});
