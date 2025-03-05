<?php

namespace Bleuren\SocialiteUnify\Providers;

use Bleuren\SocialiteUnify\Actions\Fortify\CreateNewUser;
use Bleuren\SocialiteUnify\Actions\Fortify\ResetUserPassword;
use Bleuren\SocialiteUnify\Actions\Fortify\UpdateUserPassword;
use Bleuren\SocialiteUnify\Contracts\SocialiteService;
use Bleuren\SocialiteUnify\Http\Controllers\SocialiteController;
use Bleuren\SocialiteUnify\Livewire\Profile\SocialAccountsForm;
use Bleuren\SocialiteUnify\Services\SocialiteManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;

class SocialiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/socialite-unify.php', 'socialite-unify'
        );

        $this->app->singleton(SocialiteService::class, SocialiteManager::class);
    }

    public function boot(): void
    {
        // 發布 config
        $this->publishes([
            __DIR__.'/../../config/socialite-unify.php' => config_path('socialite-unify.php'),
        ], 'config');

        // 發布 migrations
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'migrations');

        // 發布 views
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/socialite-unify'),
            __DIR__.'/../../resources/views/profile/update-password-form.blade.php' => resource_path('views/profile/update-password-form.blade.php'),
        ], 'views');

        // 發布語系檔
        $this->publishes([
            __DIR__.'/../../lang' => lang_path('vendor/socialite-unify'),
        ], 'translations');

        // 載入 views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'socialite-unify');

        // 載入 migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->loadRoutes();

        // 覆蓋 Fortify actions
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // 註冊 Socialite 提供者
        $this->app['events']->listen('SocialiteProviders\Manager\SocialiteWasCalled', function ($event) {
            $event->extendSocialite('line', \SocialiteProviders\Line\Provider::class);
        });

        // Register Livewire Components
        Livewire::component('socialite-unify.social-accounts-form', SocialAccountsForm::class);

        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'socialite-unify');
    }

    protected function loadRoutes()
    {
        $routePrefix = config('socialite-unify.route_prefix', 'auth');
        $routeNamePrefix = config('socialite-unify.route_name_prefix', 'auth');

        Route::middleware('web')
            ->prefix($routePrefix)
            ->group(function () use ($routeNamePrefix) {
                Route::get('{provider}', [SocialiteController::class, 'redirect'])->name("$routeNamePrefix.redirect");
                Route::get('{provider}/callback', [SocialiteController::class, 'callback'])->name("$routeNamePrefix.callback");
            });
    }
}
