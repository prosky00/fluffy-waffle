<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Discord\Provider as DiscordProvider;
use App\Models\FactionSetting;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->app['events']->listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            $event->extendSocialite('discord', DiscordProvider::class);
        });

        View::composer('*', function ($view) {
            if ($view->offsetExists('settings')) {
                return;
            }
            // Skip DB call before the app is installed (no DB file yet)
            if (!file_exists(storage_path('installed.lock'))) {
                $view->with('settings', new FactionSetting(['name' => 'Faction Dashboard']));
                return;
            }
            try {
                $view->with('settings', FactionSetting::singleton());
            } catch (\Throwable $e) {
                $view->with('settings', new FactionSetting(['name' => 'Faction Dashboard']));
            }
        });
    }
}
