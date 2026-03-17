<?php

namespace I18nAgent\LocaleChain\Tests;

use I18nAgent\LocaleChain\ChainTranslator;
use I18nAgent\LocaleChain\LocaleChainServiceProvider;
use Orchestra\Testbench\TestCase;

/**
 * Integration test: boots a real Laravel app via orchestra/testbench
 * and validates the full chain works through __() and trans().
 */
class IntegrationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LocaleChainServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.locale', 'en');
        $app['config']->set('app.fallback_locale', 'en');

        // Point the translation loader at our test lang directory
        $app['path.lang'] = __DIR__ . '/fixtures/lang';
    }

    protected function setUp(): void
    {
        // Create test fixture translation files
        $langPath = __DIR__ . '/fixtures/lang';

        @mkdir($langPath . '/en', 0777, true);
        @mkdir($langPath . '/pt', 0777, true);
        @mkdir($langPath . '/pt-BR', 0777, true);
        @mkdir($langPath . '/pt-PT', 0777, true);
        @mkdir($langPath . '/es', 0777, true);
        @mkdir($langPath . '/es-MX', 0777, true);

        // PHP files
        file_put_contents($langPath . '/en/messages.php', '<?php return ["greeting" => "Hello", "farewell" => "Goodbye", "english_only" => "Only in English"];');
        file_put_contents($langPath . '/pt/messages.php', '<?php return ["greeting" => "Olá (pt)", "farewell" => "Adeus (pt)"];');
        file_put_contents($langPath . '/pt-PT/messages.php', '<?php return ["greeting" => "Olá (pt-PT)"];');
        file_put_contents($langPath . '/pt-BR/messages.php', '<?php return ["greeting" => "Oi (pt-BR)"];');
        file_put_contents($langPath . '/es/messages.php', '<?php return ["greeting" => "Hola", "farewell" => "Adiós"];');
        file_put_contents($langPath . '/es-MX/messages.php', '<?php return ["greeting" => "¡Qué onda!"];');

        // JSON files
        file_put_contents($langPath . '/en.json', json_encode(['Welcome' => 'Welcome', 'Settings' => 'Settings']));
        file_put_contents($langPath . '/pt.json', json_encode(['Welcome' => 'Bem-vindo (pt)']));
        file_put_contents($langPath . '/pt-PT.json', json_encode(['Welcome' => 'Bem-vindo (pt-PT)']));
        file_put_contents($langPath . '/es.json', json_encode(['Welcome' => 'Bienvenido']));
        // pt-BR.json and es-MX.json intentionally missing

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up fixtures
        $langPath = __DIR__ . '/fixtures/lang';
        if (is_dir($langPath)) {
            $this->rmdir($langPath);
        }
        // Remove parent fixtures dir if empty
        @rmdir(__DIR__ . '/fixtures');
    }

    private function rmdir(string $dir): void
    {
        if (! is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    // -- ServiceProvider integration --

    public function test_translator_is_chain_translator(): void
    {
        $translator = app('translator');
        $this->assertInstanceOf(ChainTranslator::class, $translator);
    }

    public function test_chains_are_loaded_with_defaults(): void
    {
        /** @var ChainTranslator $translator */
        $translator = app('translator');
        $chains = $translator->getChains();
        $this->assertArrayHasKey('pt-BR', $chains);
        $this->assertArrayHasKey('es-MX', $chains);
        $this->assertArrayHasKey('zh-Hant-HK', $chains);
        $this->assertCount(75, $chains);
    }

    // -- PHP fallback chain via __() helper --

    public function test_php_primary_locale_via_helper(): void
    {
        app()->setLocale('pt-BR');
        $this->assertEquals('Oi (pt-BR)', __('messages.greeting'));
    }

    public function test_php_chain_fallthrough_via_helper(): void
    {
        // pt-BR has no "farewell" → pt-PT has no "farewell" → pt has it
        app()->setLocale('pt-BR');
        $this->assertEquals('Adeus (pt)', __('messages.farewell'));
    }

    public function test_php_final_fallback_via_helper(): void
    {
        // "english_only" only in en
        app()->setLocale('pt-BR');
        $this->assertEquals('Only in English', __('messages.english_only'));
    }

    public function test_php_returns_key_when_missing_everywhere(): void
    {
        app()->setLocale('pt-BR');
        $this->assertEquals('messages.nonexistent', __('messages.nonexistent'));
    }

    // -- JSON fallback chain via __() helper (THE BUG FIX) --

    public function test_json_chain_fallthrough_via_helper(): void
    {
        // pt-BR has no JSON file → falls through to pt-PT.json
        app()->setLocale('pt-BR');
        $this->assertEquals('Bem-vindo (pt-PT)', __('Welcome'));
    }

    public function test_json_falls_to_final_fallback_via_helper(): void
    {
        // "Settings" only in en.json
        app()->setLocale('pt-BR');
        $this->assertEquals('Settings', __('Settings'));
    }

    // -- Spanish chain (es-MX → es-419 → es → en) --

    public function test_spanish_chain_primary(): void
    {
        app()->setLocale('es-MX');
        $this->assertEquals('¡Qué onda!', __('messages.greeting'));
    }

    public function test_spanish_chain_fallthrough(): void
    {
        // es-MX has no "farewell" → es-419 not present → es has it
        app()->setLocale('es-MX');
        $this->assertEquals('Adiós', __('messages.farewell'));
    }

    public function test_spanish_json_fallthrough(): void
    {
        // es-MX has no JSON → es.json has "Welcome"
        app()->setLocale('es-MX');
        $this->assertEquals('Bienvenido', __('Welcome'));
    }

    // -- trans() also works --

    public function test_trans_helper_works(): void
    {
        app()->setLocale('pt-BR');
        $this->assertEquals('Oi (pt-BR)', trans('messages.greeting'));
    }

    // -- Disabled mode --

    public function test_disabled_uses_stock_translator(): void
    {
        // Reconfigure with disabled
        config(['locale-chain.enabled' => false]);

        // Re-resolve the translator
        app()->forgetInstance('translator');
        app()->register(LocaleChainServiceProvider::class);

        $translator = app('translator');
        $this->assertNotInstanceOf(ChainTranslator::class, $translator);
    }
}
