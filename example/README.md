# Example: Locale Fallback Chain

This example demonstrates how `i18n-laravel-locale-chain` resolves translations through a fallback chain.

## Setup

1. Install the package in your Laravel app:

```bash
composer require i18nagent/laravel-locale-chain
```

2. Create translation files:

```bash
# PHP translations
mkdir -p lang/en lang/pt lang/pt-PT lang/pt-BR

echo '<?php return ["greeting" => "Hello"];' > lang/en/messages.php
echo '<?php return ["greeting" => "Olá (pt)"];' > lang/pt/messages.php
echo '<?php return ["greeting" => "Olá (pt-PT)"];' > lang/pt-PT/messages.php
# Note: no pt-BR/messages.php — it will fall back through the chain

# JSON translations
echo '{"Welcome": "Welcome"}' > lang/en.json
echo '{"Welcome": "Bem-vindo (pt)"}' > lang/pt.json
echo '{"Welcome": "Bem-vindo (pt-PT)"}' > lang/pt-PT.json
# Note: no pt-BR.json — JSON fallback is the bug fix!
```

3. Copy `routes.php` into your `routes/web.php`.

4. Start the dev server:

```bash
php artisan serve
```

## Test

```bash
curl http://localhost:8000/locale-chain-test
```

Expected response:

```json
{
    "locale": "pt-BR",
    "fallback": "en",
    "php_greeting": "Olá (pt-PT)",
    "json_welcome": "Bem-vindo (pt-PT)"
}
```

The `pt-BR` locale has no translations, so the chain walks:
- `pt-BR` (miss) -> `pt-PT` (hit!) for both PHP and JSON files.

Without this package, `php_greeting` would fall back directly to English, and `json_welcome` would return the key itself (`Welcome`) due to Laravel's JSON fallback bug.
