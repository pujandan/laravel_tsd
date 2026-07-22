<?php

namespace Daniardev\LaravelTsd;

use Daniardev\LaravelTsd\Middleware\AppParseBoolAndNull;
use Daniardev\LaravelTsd\Services\EmailInterface;
use Daniardev\LaravelTsd\Services\EmailService;
use Daniardev\LaravelTsd\Services\MailtrapService;
use Illuminate\Support\ServiceProvider;

class LaravelTsdServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-tsd.php',
            'laravel-tsd'
        );

        // Register email services
        $this->app->bind(EmailInterface::class, function ($app) {
            $mode = config('laravel-tsd.mail.mode', 'mailtrap');

            return $mode === 'mailtrap'
                ? new MailtrapService()
                : new EmailService();
        });

        $this->app->singleton(EmailService::class);
        $this->app->singleton(MailtrapService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register middleware alias
        $this->app['router']->aliasMiddleware('parse.bool', AppParseBoolAndNull::class);

        // Load TSD translations (tsd_message.php, tsd_label.php)
        $this->loadTranslations();

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/laravel-tsd.php' => config_path('laravel-tsd.php'),
        ], 'laravel-tsd-config');

        // Publish documentation
        $this->publishes([
            __DIR__ . '/../docs' => base_path('docs/laravel-tsd'),
        ], 'laravel-tsd-docs');
    }

    /**
     * Load TSD translations with namespace.
     *
     * Loads translations from languages/ directory.
     * Translations are accessible via __('tsd_message.key') and __('tsd_label.key').
     */
    protected function loadTranslations(): void
    {
        $langPath = __DIR__ . '/../languages';

        // Load PHP translation files for each locale
        if (is_dir($langPath)) {
            $locales = scandir($langPath);
            foreach ($locales as $locale) {
                if ($locale === '.' || $locale === '..') {
                    continue;
                }

                $localePath = "$langPath/$locale";
                if (is_dir($localePath)) {
                    // Load tsd_message.php
                    $messageFile = "$localePath/tsd_message.php";
                    if (file_exists($messageFile)) {
                        $this->loadTranslationFile($messageFile, $locale);
                    }

                    // Load tsd_label.php
                    $labelFile = "$localePath/tsd_label.php";
                    if (file_exists($labelFile)) {
                        $this->loadTranslationFile($labelFile, $locale);
                    }
                }
            }
        }

        // Also load JSON translations if they exist
        $this->loadJsonTranslationsFrom($langPath);
    }

    /**
     * Load a single translation file.
     *
     * @param string $path Path to translation file
     * @param string $locale Locale code
     */
    protected function loadTranslationFile(string $path, string $locale): void
    {
        $translations = require $path;

        // Extract filename without extension (tsd_message or tsd_label)
        $namespace = pathinfo($path, PATHINFO_FILENAME);

        // Add to translator with proper namespacing
        foreach ($translations as $key => $value) {
            $this->app['translator']->addLines(
                ["$namespace.$key" => $value],
                $locale
            );
        }
    }
}