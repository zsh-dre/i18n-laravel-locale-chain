<?php

namespace I18nAgent\LocaleChain;

use Illuminate\Translation\Translator;

/**
 * Translator that walks a configurable locale fallback chain.
 *
 * Fixes two Laravel limitations:
 * 1. Single fallback_locale (no intermediate fallback like pt-BR -> pt-PT -> pt -> en)
 * 2. JSON translation files ignoring fallback_locale entirely
 */
class ChainTranslator extends Translator
{
    /** @var array<string, list<string>> */
    protected array $chains;

    /**
     * @param  \Illuminate\Translation\Loader\LoaderInterface  $loader
     * @param  string  $locale
     * @param  array<string, list<string>>  $chains
     */
    public function __construct($loader, string $locale, array $chains = [])
    {
        parent::__construct($loader, $locale);
        $this->chains = $chains;
    }

    /**
     * Get the translation for the given key.
     *
     * Overrides the parent to walk the fallback chain for BOTH PHP and JSON files.
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $locale = $locale ?: $this->locale;

        // Try the primary locale first (parent handles PHP + JSON for this locale)
        $result = $this->resolveForLocale($key, $replace, $locale);
        if ($result !== null) {
            return $result;
        }

        // Walk the fallback chain
        if ($fallback) {
            $chain = $this->chains[$locale] ?? [];
            foreach ($chain as $fallbackLocale) {
                $result = $this->resolveForLocale($key, $replace, $fallbackLocale);
                if ($result !== null) {
                    return $result;
                }
            }

            // Final fallback to the configured fallback locale
            $configFallback = $this->getFallback();
            if ($configFallback && $configFallback !== $locale && ! in_array($configFallback, $chain)) {
                $result = $this->resolveForLocale($key, $replace, $configFallback);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        // Nothing found — return the key (Laravel convention)
        return $this->makeReplacements($key, $replace);
    }

    /**
     * Get a translation according to an integer value.
     *
     * @param  string  $key
     * @param  \Countable|int|float|array  $number
     * @param  array  $replace
     * @param  string|null  $locale
     * @return string
     */
     public function choice($key, $number, array $replace = [], $locale = null, $fallback=true)
    {
        $line = $this->get($key, [], $locale, $fallback);
		
        // If the given "number" is actually an array or countable we will simply count the
        // number of elements in an instance. This allows developers to pass an array of
        // items without having to count it on their end first which gives bad syntax.
        if (is_countable($number)) {
            $number = count($number);
        }

        if (! isset($replace['count'])) {
            $replace['count'] = $number;
        }
		
        return $this->makeReplacements(
            $this->getSelector()->choose($line, $number, $locale), $replace
        );
    }

    /**
     * Try to resolve a key for a specific locale (PHP files + JSON files).
     *
     * Returns null if the key is not found (as opposed to returning the key itself).
     *
     * @return string|array|null
     */
    protected function resolveForLocale(string $key, array $replace, string $locale): mixed
    {
        // Check JSON translations first (this is the bug fix — Laravel never checks
        // JSON fallback locales, we do)
        $this->load('*', '*', $locale);
        $jsonLine = $this->loaded['*']['*'][$locale][$key] ?? null;
        if ($jsonLine !== null) {
            return $this->makeReplacements($jsonLine, $replace);
        }

        // Check PHP group files (namespace.group.item pattern)
        [$namespace, $group, $item] = $this->parseKey($key);
        if ($group === null || $item === null) {
            return null;
        }

        $this->load($namespace, $group, $locale);
        $phpLine = $this->loaded[$namespace][$group][$locale][$item] ?? null;

        if ($phpLine !== null && is_string($phpLine)) {
            return $this->makeReplacements($phpLine, $replace);
        }

        // Also handle array returns (e.g., trans('messages') returning all keys)
        if ($phpLine !== null && is_array($phpLine)) {
            return $phpLine;
        }

        return null;
    }

    /**
     * Parse a key into namespace, group, and item.
     *
     * @return array{string, string|null, string|null}
     */
    public function parseKey($key): array
    {
        $segments = parent::parseKey($key);

        // parseKey returns [namespace, group, item]
        // For JSON keys (no dots), group is '*' and item is the key
        if ($segments[1] === '*' && $segments[2] === $key) {
            return [$segments[0], null, null]; // Signal: already handled by JSON path
        }

        return $segments;
    }

    /**
     * Set the chains at runtime.
     *
     * @param  array<string, list<string>>  $chains
     */
    public function setChains(array $chains): void
    {
        $this->chains = $chains;
    }

    /**
     * Get the current chains.
     *
     * @return array<string, list<string>>
     */
    public function getChains(): array
    {
        return $this->chains;
    }
}
