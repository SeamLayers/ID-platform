<?php

namespace App\Providers;


use App\Contracts\ValidationTranslatorInterface;
use App\Services\ValidationTranslatorService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ValidationTranslatorInterface::class,
            ValidationTranslatorService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Gate::before(function ($user) {
            return $user->hasRole('superadmin') ? true : null;
        });

    }
}
