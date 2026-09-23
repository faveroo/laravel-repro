# Laravel Repro

Turn real Laravel failures into reproducible regression tests.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/faveroo/laravel-repro.svg?style=flat-square)](https://packagist.org/packages/faveroo/laravel-repro)
[![Tests](https://github.com/faveroo/laravel-repro/actions/workflows/tests.yml/badge.svg)](https://github.com/faveroo/laravel-repro/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/faveroo/laravel-repro.svg?style=flat-square)](LICENSE.md)

> Laravel Repro is under active development and is not yet recommended for production use.

## About

Laravel Repro captures failed Laravel HTTP requests, removes sensitive information and stores them as reproducible cases.

Captured cases can be converted into Pest regression tests.

```text
Request + Exception → Sanitization → Storage → Pest Test
```

## Requirements

* PHP 8.3 or newer
* Laravel 13
* Composer

## Installation

Install the package through Composer:

```bash
composer require faveroo/laravel-repro
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=repro-config
```

Enable failure capturing in your `.env` file:

```dotenv
LARAVEL_REPRO_ENABLED=true
```

Clear the configuration cache:

```bash
php artisan config:clear
```

## Middleware

Register the middleware in `bootstrap/app.php`:

```php
use Faveroo\LaravelRepro\Middleware\CaptureFailures;
use Illuminate\Foundation\Configuration\Middleware;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->append(CaptureFailures::class);
})
```

Keep any middleware already registered in the existing `withMiddleware` callback.

## Usage

After an HTTP failure is captured, list the stored reproduction cases:

```bash
php artisan repro:list
```

Limit the number of displayed cases:

```bash
php artisan repro:list --limit=10
```

Generate a Pest regression test:

```bash
php artisan repro:test <case-id>
```

Laravel Repro will not overwrite an existing test unless `--force` is provided:

```bash
php artisan repro:test <case-id> --force
```

Generated tests are stored in the path configured by `repro.test_path`.

Always review generated tests before committing them. Redacted values may need to be replaced with fixtures from your application.

## Pest

The consuming Laravel application must have Pest installed to execute generated tests:

```bash
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies
vendor/bin/pest --init
```

Failure capturing is disabled automatically while the application is running in
the `testing` environment, even when `LARAVEL_REPRO_ENABLED` is `true`. You can
also enforce the disabled setting in `phpunit.xml`:

```xml
<env
    name="LARAVEL_REPRO_ENABLED"
    value="false"
    force="true"
/>
```

Capture can be enabled intentionally during tests with:

```dotenv
LARAVEL_REPRO_CAPTURE_IN_TESTING=true
```

This option is intended primarily for integration tests of the failure-capture
mechanism itself.

## Security

Laravel Repro minimizes the amount of sensitive information stored:

* Capturing is disabled by default.
* Only configured headers are captured.
* Passwords, tokens, secrets and API keys are recursively redacted.
* Exception messages are omitted by default.
* Absolute filesystem paths are converted to project-relative paths.
* Exception classes can be ignored through configuration.

Captured data should be stored on a protected filesystem and must not be committed to version control.

## Development

Install the dependencies:

```bash
composer install
```

Run all quality checks:

```bash
composer lint
composer analyse
composer test
```

Format the code:

```bash
composer format
```

## License

Laravel Repro is open-source software licensed under the [MIT License](LICENSE.md).
