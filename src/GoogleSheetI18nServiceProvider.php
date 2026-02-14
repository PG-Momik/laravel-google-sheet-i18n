<?php

declare(strict_types=1);

namespace LaravelGoogleSheetI18n;

use Illuminate\Support\ServiceProvider;
use LaravelGoogleSheetI18n\Console\Commands\TranslateCommand;
use LaravelGoogleSheetI18n\Services\GoogleSheetsService;
use LaravelGoogleSheetI18n\Services\PlaceholderMasker;

class GoogleSheetI18nServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views/translation-manager', 'translation-manager');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/google-sheet-i18n.php' => config_path('google-sheet-i18n.php'),
            ], 'config');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views/translation-manager' => resource_path('views/vendor/translation-manager'),
            ], 'views');
        }

        // Register commands in all environments so they can be called via Artisan::call() in controllers
        $this->commands([
            TranslateCommand::class,
            \LaravelGoogleSheetI18n\Console\Commands\InstallCommand::class,
        ]);
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/google-sheet-i18n.php', 'google-sheet-i18n');

        $this->app->singleton(PlaceholderMasker::class, function ($app) {
            return new PlaceholderMasker();
        });

        $this->app->singleton(GoogleSheetsService::class, function ($app) {
            /** @var array $config */
            $config = config('google-sheet-i18n');
            return new GoogleSheetsService($config);
        });
    }
}
