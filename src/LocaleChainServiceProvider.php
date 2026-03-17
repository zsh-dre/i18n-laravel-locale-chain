<?php

namespace I18nAgent\LocaleChain;

use Illuminate\Translation\TranslationServiceProvider;

class LocaleChainServiceProvider extends TranslationServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/locale-chain.php', 'locale-chain');

        // Register the file loader (from parent)
        $this->registerLoader();

        // Replace the translator singleton with our ChainTranslator
        $this->app->singleton('translator', function ($app) {
            $config = $app['config'];

            if (! $config->get('locale-chain.enabled', true)) {
                // Disabled — use stock Laravel translator
                $trans = new \Illuminate\Translation\Translator(
                    $app['translation.loader'],
                    $config->get('app.locale'),
                );
                $trans->setFallback($config->get('app.fallback_locale'));
                return $trans;
            }

            $chains = FallbackMap::merge(
                $config->get('locale-chain.chains', []) ?: null,
                mergeDefaults: $config->get('locale-chain.merge_defaults', true),
            );

            $trans = new ChainTranslator(
                $app['translation.loader'],
                $config->get('app.locale'),
                $chains,
            );

            $trans->setFallback($config->get('app.fallback_locale'));

            return $trans;
        });
    }

    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/locale-chain.php' => $this->app->configPath('locale-chain.php'),
        ], 'locale-chain-config');
    }
}
