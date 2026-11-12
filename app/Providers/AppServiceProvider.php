<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Services\Cart;
use App\Services\PriceCalculator;
use App\Services\Settings;
use App\View\Composers\LayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Settings::class);
        $this->app->scoped(PriceCalculator::class);
        $this->app->scoped(Cart::class);
    }

    public function boot(): void
    {
        Model::unguard();
        Model::shouldBeStrict(! $this->app->isProduction());

        Paginator::defaultView('pagination.default');
        Paginator::defaultSimpleView('pagination.simple');

        Password::defaults(static fn (): Password => Password::min(8));

        Gate::before(static fn (User $user): ?bool => $user->isAdmin() ? true : null);

        View::composer(['components.layouts.app', 'components.layouts.panel', 'components.layouts.auth'], LayoutComposer::class);
        View::composer(
            ['storefront.*', 'account.*', 'vendor.*', 'admin.*', 'support.*', 'auth.*'],
            static fn (ViewInstance $view) => $view->with('settings', app(Settings::class)),
        );

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('license-api', static fn (Request $request): Limit => Limit::perMinute(
            config('marketplace.api.rate_limit_per_minute'),
        )->by($request->ip()));

        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('downloads', static fn (Request $request): Limit => Limit::perMinute(30)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('search', static fn (Request $request): Limit => Limit::perMinute(90)->by($request->ip()));
    }
}
