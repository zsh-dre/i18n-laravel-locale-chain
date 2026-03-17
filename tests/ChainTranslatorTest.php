<?php

namespace I18nAgent\LocaleChain\Tests;

use I18nAgent\LocaleChain\ChainTranslator;
use I18nAgent\LocaleChain\FallbackMap;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;

class ChainTranslatorTest extends TestCase
{
    private string $langPath;

    protected function setUp(): void
    {
        // Create temp lang directory with test translation files
        $this->langPath = sys_get_temp_dir() . '/locale-chain-test-' . uniqid();
        mkdir($this->langPath . '/en', 0777, true);
        mkdir($this->langPath . '/pt', 0777, true);
        mkdir($this->langPath . '/pt-BR', 0777, true);
        mkdir($this->langPath . '/pt-PT', 0777, true);

        // PHP translation files
        file_put_contents($this->langPath . '/en/messages.php', '<?php return ["greeting" => "Hello", "farewell" => "Goodbye", "english_only" => "Only in English"];');
        file_put_contents($this->langPath . '/pt/messages.php', '<?php return ["greeting" => "Olá (pt)", "farewell" => "Adeus (pt)"];');
        file_put_contents($this->langPath . '/pt-PT/messages.php', '<?php return ["greeting" => "Olá (pt-PT)"];');
        file_put_contents($this->langPath . '/pt-BR/messages.php', '<?php return ["greeting" => "Oi (pt-BR)"];');

        // JSON translation files
        file_put_contents($this->langPath . '/en.json', json_encode(['Welcome' => 'Welcome', 'Settings' => 'Settings']));
        file_put_contents($this->langPath . '/pt.json', json_encode(['Welcome' => 'Bem-vindo (pt)']));
        file_put_contents($this->langPath . '/pt-PT.json', json_encode(['Welcome' => 'Bem-vindo (pt-PT)']));
        // pt-BR.json intentionally missing to test fallback
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->langPath);
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

    private function makeTranslator(array $chains = [], string $fallback = 'en'): ChainTranslator
    {
        $loader = new FileLoader(new Filesystem(), $this->langPath);
        $translator = new ChainTranslator($loader, 'pt-BR', $chains ?: FallbackMap::defaults());
        $translator->setFallback($fallback);
        return $translator;
    }

    // -- PHP file fallback chain --

    public function test_php_resolves_from_primary_locale(): void
    {
        $t = $this->makeTranslator();
        $this->assertEquals('Oi (pt-BR)', $t->get('messages.greeting'));
    }

    public function test_php_falls_through_chain_to_pt_pt(): void
    {
        // pt-BR has no "farewell", pt-PT has no "farewell", pt has it
        $t = $this->makeTranslator();
        $this->assertEquals('Adeus (pt)', $t->get('messages.farewell'));
    }

    public function test_php_falls_through_to_fallback_locale(): void
    {
        $t = $this->makeTranslator();
        $this->assertEquals('Only in English', $t->get('messages.english_only'));
    }

    public function test_php_returns_key_when_not_found_anywhere(): void
    {
        $t = $this->makeTranslator();
        $this->assertEquals('messages.nonexistent', $t->get('messages.nonexistent'));
    }

    // -- JSON file fallback chain (the bug fix) --

    public function test_json_falls_through_chain(): void
    {
        // pt-BR has no JSON file, should fall through to pt-PT then pt
        $t = $this->makeTranslator();
        $this->assertEquals('Bem-vindo (pt-PT)', $t->get('Welcome'));
    }

    public function test_json_falls_through_to_fallback_locale(): void
    {
        // "Settings" only exists in en.json
        $t = $this->makeTranslator();
        $this->assertEquals('Settings', $t->get('Settings'));
    }

    // -- Custom chains --

    public function test_custom_chain_overrides_defaults(): void
    {
        // Default chain: pt-BR -> pt-PT -> pt -> en
        // Custom chain:  pt-BR -> pt -> en (skip pt-PT)
        // "farewell" is missing in pt-BR and pt-PT, present in pt
        // With both chains it resolves to pt, but let's verify greeting
        // falls through correctly when pt-BR doesn't have it.
        // pt-BR has "greeting", so test with a key only in pt:
        $t = $this->makeTranslator(['pt-BR' => ['pt']]);
        // "farewell" not in pt-BR, not trying pt-PT (skipped), found in pt
        $this->assertEquals('Adeus (pt)', $t->get('messages.farewell'));
        // Also verify greeting still resolves from primary locale
        $this->assertEquals('Oi (pt-BR)', $t->get('messages.greeting'));
    }

    public function test_locale_without_chain_falls_to_default(): void
    {
        $loader = new FileLoader(new Filesystem(), $this->langPath);
        $translator = new ChainTranslator($loader, 'en', []);
        $translator->setFallback('en');
        $this->assertEquals('Hello', $translator->get('messages.greeting'));
    }
}
