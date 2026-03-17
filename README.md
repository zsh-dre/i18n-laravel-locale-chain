# i18n-laravel-locale-chain

Smart locale fallback chains for Laravel -- because pt-BR users deserve pt-PT translations, not English.

## The Problem

Laravel's translation system has two limitations:

1. **Single fallback locale.** The `fallback_locale` config only supports one language. There is no intermediate fallback. If a user requests `pt-BR` and you only have `pt-PT` translations, Laravel skips `pt-PT` entirely and shows English (or whatever your `fallback_locale` is).

2. **JSON translation files ignore fallback entirely.** This is a [known Laravel bug](https://github.com/laravel/framework/issues/41565). When you use JSON translation files (`lang/pt-BR.json`), Laravel does **not** check the fallback locale's JSON file. PHP files (`lang/pt-BR/messages.php`) respect `fallback_locale`, but JSON files do not.

**Example:** A user's browser sends `Accept-Language: pt-BR`. Your Laravel app has `pt-PT` translations but no `pt-BR` locale. Laravel skips `pt-PT` entirely and shows English.

The same thing happens with `es-MX` -> `es`, `fr-CA` -> `fr`, `de-AT` -> `de`, and every other regional variant.

Your users see English when a perfectly good translation exists in a sibling locale.

## The Solution

One service provider. Zero code changes. Laravel auto-discovery handles everything.

`i18n-laravel-locale-chain` replaces Laravel's `TranslationServiceProvider` with a `ChainTranslator` that walks a configurable fallback chain for **both** PHP and JSON translation files. Missing keys in the primary locale are resolved from fallback locales before reaching the app's default language. Your existing translation calls just work:

- `__('key')` helper
- `trans('key')` helper
- `@lang('key')` Blade directive
- `{{ __('key') }}` in Blade templates
- All `Illuminate\Translation` functions

## Installation

```bash
composer require i18nagent/laravel-locale-chain
```

That's it. Laravel auto-discovers the service provider. All 75 default fallback chains are active immediately.

## Quick Start

### 1. Install (auto-discovery does the rest)

```bash
composer require i18nagent/laravel-locale-chain
```

No configuration needed. A `pt-BR` user will now see `pt-PT` translations when `pt-BR` is not available, and JSON translation files will correctly fall back through the chain.

### 2. (Optional) Publish the config file

```bash
php artisan vendor:publish --tag=locale-chain-config
```

This creates `config/locale-chain.php` where you can customize chains or disable the package.

### 3. (Optional) Add custom chains

```php
// config/locale-chain.php

return [
    'chains' => [
        'pt-BR' => ['pt-PT', 'pt'],
        'es-MX' => ['es-419', 'es'],
        'ja-JP' => ['ja'],
    ],
];
```

Your custom chains are merged with the 75 built-in defaults. Keys you specify replace the corresponding default chain.

## Configuration Modes

### Default (zero config)

Just install the package. Uses all 75 built-in fallback chains covering Chinese, Portuguese, Spanish, French, German, Italian, Dutch, English, Arabic, Norwegian, and Malay regional variants.

### Config file

```php
// config/locale-chain.php

return [
    'enabled' => true,

    // Custom chains merged with defaults
    'chains' => [
        'pt-BR' => ['pt-PT', 'pt'],
        'ja-JP' => ['ja'],
    ],

    // Set to false to use ONLY your custom chains
    'merge_defaults' => true,
];
```

### Programmatic

```php
// At runtime, via the translator instance
app('translator')->setChains([
    'pt-BR' => ['pt-PT', 'pt'],
]);

// Or merge with defaults
use I18nAgent\LocaleChain\FallbackMap;

app('translator')->setChains(
    FallbackMap::merge(['pt-BR' => ['pt']])
);
```

**Priority order** (highest to lowest):

1. `setChains()` call (programmatic API)
2. `config/locale-chain.php` config file
3. Built-in defaults (zero-config)

## API Reference

### `FallbackMap::defaults()`

Returns the 75 built-in fallback chains as `array<string, list<string>>`.

### `FallbackMap::merge(?array $overrides = null, bool $mergeDefaults = true)`

Merge overrides on top of the default chains. Returns a new array -- the defaults are never mutated.

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$overrides` | `array\|null` | `null` | Per-locale chains that replace defaults |
| `$mergeDefaults` | `bool` | `true` | If false, return only overrides |

### `ChainTranslator`

Extends `Illuminate\Translation\Translator`. Overrides `get()` to walk a configurable fallback chain for both PHP and JSON files.

| Method | Description |
|--------|-------------|
| `setChains(array $chains)` | Set the fallback chains at runtime |
| `getChains()` | Get the current fallback chains |

### `LocaleChainServiceProvider`

Extends `TranslationServiceProvider`. Replaces the `translator` singleton with `ChainTranslator`. Auto-discovered by Laravel.

### Config Reference

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `enabled` | `bool` | `true` | Set to false to disable without removing the package |
| `chains` | `array` | `[]` | Custom fallback chains, merged with built-in defaults |
| `merge_defaults` | `bool` | `true` | If false, use only custom chains |

## Default Fallback Map

### Chinese (Traditional)

| Locale | Fallback Chain |
|--------|---------------|
| zh-Hant-HK | zh-Hant-TW -> zh-Hant -> (app locale) |
| zh-Hant-MO | zh-Hant-HK -> zh-Hant-TW -> zh-Hant -> (app locale) |
| zh-Hant-TW | zh-Hant -> (app locale) |

### Chinese (Simplified)

| Locale | Fallback Chain |
|--------|---------------|
| zh-Hans-SG | zh-Hans -> (app locale) |
| zh-Hans-MY | zh-Hans -> (app locale) |

### Portuguese

| Locale | Fallback Chain |
|--------|---------------|
| pt-BR | pt-PT -> pt -> (app locale) |
| pt-PT | pt -> (app locale) |
| pt-AO | pt-PT -> pt -> (app locale) |
| pt-MZ | pt-PT -> pt -> (app locale) |

### Spanish

| Locale | Fallback Chain |
|--------|---------------|
| es-419 | es -> (app locale) |
| es-MX | es-419 -> es -> (app locale) |
| es-AR | es-419 -> es -> (app locale) |
| es-CO | es-419 -> es -> (app locale) |
| es-CL | es-419 -> es -> (app locale) |
| es-PE | es-419 -> es -> (app locale) |
| es-VE | es-419 -> es -> (app locale) |
| es-EC | es-419 -> es -> (app locale) |
| es-GT | es-419 -> es -> (app locale) |
| es-CU | es-419 -> es -> (app locale) |
| es-BO | es-419 -> es -> (app locale) |
| es-DO | es-419 -> es -> (app locale) |
| es-HN | es-419 -> es -> (app locale) |
| es-PY | es-419 -> es -> (app locale) |
| es-SV | es-419 -> es -> (app locale) |
| es-NI | es-419 -> es -> (app locale) |
| es-CR | es-419 -> es -> (app locale) |
| es-PA | es-419 -> es -> (app locale) |
| es-UY | es-419 -> es -> (app locale) |
| es-PR | es-419 -> es -> (app locale) |

### French

| Locale | Fallback Chain |
|--------|---------------|
| fr-CA | fr -> (app locale) |
| fr-BE | fr -> (app locale) |
| fr-CH | fr -> (app locale) |
| fr-LU | fr -> (app locale) |
| fr-MC | fr -> (app locale) |
| fr-SN | fr -> (app locale) |
| fr-CI | fr -> (app locale) |
| fr-ML | fr -> (app locale) |
| fr-CM | fr -> (app locale) |
| fr-MG | fr -> (app locale) |
| fr-CD | fr -> (app locale) |

### German

| Locale | Fallback Chain |
|--------|---------------|
| de-AT | de -> (app locale) |
| de-CH | de -> (app locale) |
| de-LU | de -> (app locale) |
| de-LI | de -> (app locale) |

### Italian

| Locale | Fallback Chain |
|--------|---------------|
| it-CH | it -> (app locale) |

### Dutch

| Locale | Fallback Chain |
|--------|---------------|
| nl-BE | nl -> (app locale) |

### English

| Locale | Fallback Chain |
|--------|---------------|
| en-GB | en -> (app locale) |
| en-AU | en-GB -> en -> (app locale) |
| en-NZ | en-AU -> en-GB -> en -> (app locale) |
| en-IN | en-GB -> en -> (app locale) |
| en-CA | en -> (app locale) |
| en-ZA | en-GB -> en -> (app locale) |
| en-IE | en-GB -> en -> (app locale) |
| en-SG | en-GB -> en -> (app locale) |

### Arabic

| Locale | Fallback Chain |
|--------|---------------|
| ar-SA | ar -> (app locale) |
| ar-EG | ar -> (app locale) |
| ar-AE | ar -> (app locale) |
| ar-MA | ar -> (app locale) |
| ar-DZ | ar -> (app locale) |
| ar-IQ | ar -> (app locale) |
| ar-KW | ar -> (app locale) |
| ar-QA | ar -> (app locale) |
| ar-BH | ar -> (app locale) |
| ar-OM | ar -> (app locale) |
| ar-JO | ar -> (app locale) |
| ar-LB | ar -> (app locale) |
| ar-TN | ar -> (app locale) |
| ar-LY | ar -> (app locale) |
| ar-SD | ar -> (app locale) |
| ar-YE | ar -> (app locale) |

### Norwegian

| Locale | Fallback Chain |
|--------|---------------|
| nb | no -> (app locale) |
| nn | nb -> no -> (app locale) |

### Malay

| Locale | Fallback Chain |
|--------|---------------|
| ms-MY | ms -> (app locale) |
| ms-SG | ms -> (app locale) |
| ms-BN | ms -> (app locale) |

## How It Works

1. Laravel auto-discovers `LocaleChainServiceProvider`, which extends Laravel's `TranslationServiceProvider`.
2. The service provider replaces the `translator` singleton with `ChainTranslator`, which extends `Illuminate\Translation\Translator`.
3. `ChainTranslator` overrides the `get()` method to walk a configurable fallback chain.
4. For each key lookup, it tries the primary locale first, then each locale in the chain, then the configured `fallback_locale`.
5. **Crucially, it checks JSON translation files at each step** -- fixing Laravel's bug where JSON files ignore fallback entirely.
6. The original terminal fallback (your `fallback_locale`) is preserved at the end of the chain.
7. Your existing `__()`, `trans()`, and `@lang()` calls work without any changes.

## Example

A working example route is included in the `example/` directory. Copy it to your `routes/web.php`:

```php
Route::get('/locale-chain-test', function () {
    App::setLocale('pt-BR');

    return response()->json([
        'locale' => App::getLocale(),
        'php_greeting' => __('messages.greeting'),
        'json_welcome' => __('Welcome'),
    ]);
});
```

Then test:

```bash
curl http://localhost:8000/locale-chain-test
```

See `example/README.md` for full details.

## FAQ

**Is this production-ready?**
Yes. The library extends Laravel's `Translator` class using its public API. No monkey-patching, no private API access.

**Performance impact?**
Negligible. Translation files are loaded once per locale per request via Laravel's built-in `FileLoader` caching. The chain walking adds only a few array lookups per missing key.

**Does it fix the JSON fallback bug?**
Yes. This is one of the two main features. Laravel's stock translator does not check JSON fallback files -- `ChainTranslator` checks JSON files at every step of the fallback chain.

**Can I use a non-English default locale?**
Yes. The fallback chains are independent of your app's `locale` and `fallback_locale`. They only control which sibling locales are checked before the default language.

**Can I disable it?**
Yes. Set `'enabled' => false` in `config/locale-chain.php`, or remove the package entirely.

**Does it work with Laravel Livewire / Inertia.js?**
Yes. Both use Laravel's translation system under the hood, so fallback chains work automatically.

**Does it work with API resources?**
Yes. Any code that uses `__()`, `trans()`, or the `Translator` service will benefit from fallback chains.

**Minimum Laravel version?**
Laravel 10 (LTS). Also supports Laravel 11 and Laravel 12.

## Contributing

- Open issues for bugs or feature requests.
- PRs welcome, especially for adding new locale fallback chains.
- Run tests with: `./vendor/bin/phpunit`

## License

MIT License - see [LICENSE](LICENSE) file.

Built by [i18nagent.ai](https://i18nagent.ai)
