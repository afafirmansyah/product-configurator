<?php

namespace App\Providers;

use App\Models\ConfigurationSession;
use App\Models\User;
use App\Policies\ConfigurationSessionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(ConfigurationSession::class, ConfigurationSessionPolicy::class);

        Gate::before(fn (User $user, string $ability): ?bool => $user->hasRole('super_admin') ? true : null);
    }
}
