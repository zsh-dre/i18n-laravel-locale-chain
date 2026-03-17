<?php

namespace I18nAgent\LocaleChain\Tests;

use I18nAgent\LocaleChain\FallbackMap;
use PHPUnit\Framework\TestCase;

class FallbackMapTest extends TestCase
{
    // -- Data integrity --

    public function test_has_exactly_75_entries(): void
    {
        $this->assertCount(75, FallbackMap::defaults());
    }

    public function test_all_values_are_non_empty_arrays_of_strings(): void
    {
        foreach (FallbackMap::defaults() as $key => $chain) {
            $this->assertIsArray($chain, "$key: chain is not an array");
            $this->assertNotEmpty($chain, "$key: chain is empty");
            foreach ($chain as $item) {
                $this->assertIsString($item, "$key: chain item is not a string");
            }
        }
    }

    public function test_no_self_references(): void
    {
        foreach (FallbackMap::defaults() as $key => $chain) {
            $this->assertNotContains($key, $chain, "$key appears in its own chain");
        }
    }

    public function test_no_duplicate_entries_in_any_chain(): void
    {
        foreach (FallbackMap::defaults() as $key => $chain) {
            $this->assertCount(
                count(array_unique($chain)),
                $chain,
                "$key: chain has duplicates"
            );
        }
    }

    // -- Language groups --

    public function test_portuguese_variants(): void
    {
        $defaults = FallbackMap::defaults();
        $this->assertArrayHasKey('pt-BR', $defaults);
        $this->assertArrayHasKey('pt-PT', $defaults);
        $this->assertEquals(['pt-PT', 'pt'], $defaults['pt-BR']);
    }

    public function test_chinese_traditional_variants(): void
    {
        $defaults = FallbackMap::defaults();
        $this->assertArrayHasKey('zh-Hant-HK', $defaults);
        $this->assertEquals(['zh-Hant-TW', 'zh-Hant'], $defaults['zh-Hant-HK']);
    }

    public function test_spanish_variants(): void
    {
        $defaults = FallbackMap::defaults();
        $this->assertArrayHasKey('es-MX', $defaults);
        $this->assertEquals(['es-419', 'es'], $defaults['es-MX']);
    }

    public function test_english_variants(): void
    {
        $defaults = FallbackMap::defaults();
        $this->assertArrayHasKey('en-AU', $defaults);
        $this->assertEquals(['en-GB', 'en'], $defaults['en-AU']);
    }

    public function test_norwegian_variants(): void
    {
        $defaults = FallbackMap::defaults();
        $this->assertArrayHasKey('nn', $defaults);
        $this->assertEquals(['nb', 'no'], $defaults['nn']);
    }

    // -- Merge --

    public function test_merge_no_overrides_returns_defaults(): void
    {
        $result = FallbackMap::merge();
        $this->assertEquals(FallbackMap::defaults(), $result);
    }

    public function test_merge_override_replaces_chain(): void
    {
        $result = FallbackMap::merge(['pt-BR' => ['pt']]);
        $this->assertEquals(['pt'], $result['pt-BR']);
        $this->assertEquals(['en'], $result['en-GB']); // others intact
    }

    public function test_merge_adds_new_chain(): void
    {
        $result = FallbackMap::merge(['xx-YY' => ['xx']]);
        $this->assertEquals(['xx'], $result['xx-YY']);
        $this->assertCount(76, $result);
    }

    public function test_merge_no_defaults_returns_only_overrides(): void
    {
        $result = FallbackMap::merge(['a-A' => ['a']], mergeDefaults: false);
        $this->assertEquals(['a-A' => ['a']], $result);
    }

    public function test_merge_does_not_mutate_defaults(): void
    {
        $before = FallbackMap::defaults();
        FallbackMap::merge(['pt-BR' => ['custom']]);
        $this->assertEquals($before, FallbackMap::defaults());
    }
}
