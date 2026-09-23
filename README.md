# Laravel Repro

Turn real Laravel failures into reproducible regression tests.

> Laravel Repro is under active development and is not yet recommended for production use.

## About

Laravel Repro is a Laravel package that captures failed HTTP requests, removes sensitive information and stores them as reproducible cases.

These cases can then be converted into Pest regression tests.

```text
Request + Exception
        ↓
Sanitization
        ↓
Reproduction Case
        ↓
Storage
        ↓
Pest Test
```

## Features

* Immutable reproduction cases
* Versioned reproduction schema
* Serializable exception snapshots
* Relative and sanitized exception paths
* Recursive sensitive-data redaction
* Configurable sensitive keys and replacement values
* Request and exception recorder
* Filesystem JSON storage
* Laravel Service Provider and automatic package discovery
* Dependency injection through Laravel's container
* HTTP failure-capture middleware
* Artisan commands for listing cases and generating tests
* Pest regression-test generation
* Duplicate-file protection with optional forced overwrite
* Automated tests with Pest

## Requirements

* PHP 8.2 or newer
* Laravel
* Composer

The package is currently being tested primarily with Laravel 13 and PHP 8.5. Check `composer.json` for the currently supported dependency constraints.

## Installation

Laravel Repro has not yet been published on Packagist.

During development, it can be installed in a Laravel application through a Composer path repository.

Assuming the Laravel application and this package are in neighboring directories:

```text
projects/
├── laravel-app/
└── laravel-repro/
```

From the Laravel application directory, run:

```bash
composer config repositories.laravel-repro path ../laravel-repro
composer require faveroo/laravel-repro:@dev
```

Once the package is published on Packagist, installation will use:

```bash
composer require faveroo/laravel-repro
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=repro-config
```

The configuration file will be created at:

```text
config/repro.php
```

Example configuration:

```php
<?php

return [
    'enabled' => env('LARAVEL_REPRO_ENABLED', false),

    'disk' => env('LARAVEL_REPRO_DISK', 'local'),

    'path' => env(
        'LARAVEL_REPRO_PATH',
        'laravel-repro',
    ),

    'test_path' => env(
        'LARAVEL_REPRO_TEST_PATH',
        'tests/Feature/Reproductions',
    ),

    'capture_exception_message' => false,

    'headers' => [
        'accept',
        'content-type',
        'user-agent',
        'x-requested-with',
    ],

    'redact' => [
        'authorization',
        'cookie',
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
        'card_number',
        'cvv',
    ],

    'replacement' => '[REDACTED]',

    'ignore_exceptions' => [
        // Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],
];
```

Enable failure capturing in your environment:

```dotenv
LARAVEL_REPRO_ENABLED=true
```

After changing environment configuration, clear Laravel's configuration cache:

```bash
php artisan config:clear
```

## Middleware

Register the failure-capture middleware in your Laravel application's `bootstrap/app.php`.

```php
use Faveroo\LaravelRepro\Middleware\CaptureFailures;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CaptureFailures::class);
    })
    ->create();
```

Keep any middleware already configured in the existing `withMiddleware` callback.

## Listing captured cases

List the stored reproduction cases:

```bash
php artisan repro:list
```

Limit the number of displayed cases:

```bash
php artisan repro:list --limit=10
```

The command displays information such as:

* Case ID
* Capture date
* HTTP method
* Request URI
* Exception class

## Generating a Pest test

Generate a Pest regression test from a captured case:

```bash
php artisan repro:test <case-id>
```

Example:

```bash
php artisan repro:test 8f93c1a22b771acd
```

Laravel Repro will refuse to overwrite an existing test.

To explicitly replace it, use:

```bash
php artisan repro:test 8f93c1a22b771acd --force
```

Generated tests are written to the configured `test_path`.

Always review a generated test before committing it. Redacted values may need to be replaced with fixtures that are meaningful to your application.

## Pest in the consuming application

Laravel Repro generates Pest tests, but it does not force Pest to be installed in the consuming Laravel application.

If necessary, install Pest and its Laravel plugin:

```bash
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies
php artisan pest:install
```

Make sure the selected Pest and PHPUnit versions are compatible with the Laravel application.

It is recommended to disable capturing while the generated regression suite is running:

```xml
<env name="LARAVEL_REPRO_ENABLED" value="false"/>
```

## Sensitive-data redaction

The redactor replaces configured sensitive values recursively.

```php
use Faveroo\LaravelRepro\Contracts\Redactor;

$redactor = app(Redactor::class);

$result = $redactor->redact([
    'email' => 'gabriel@example.com',
    'password' => 'secret',
    'profile' => [
        'token' => 'private-token',
    ],
]);
```

Result:

```php
[
    'email' => 'gabriel@example.com',
    'password' => '[REDACTED]',
    'profile' => [
        'token' => '[REDACTED]',
    ],
]
```

Keys are compared against the values configured in `repro.redact`.

## Storage

Reproduction cases are serialized as JSON and stored on the configured Laravel filesystem disk.

By default:

```php
'disk' => 'local',
'path' => 'laravel-repro',
```

Captured files must not be committed to version control.

Add the relevant storage directory to `.gitignore` if your configured disk places it inside the project repository.

## Security

Laravel Repro is designed to minimize the amount of sensitive data stored in reproduction cases.

* Capturing is disabled by default.
* Only configured request headers are captured.
* Authorization and cookie headers are excluded by default.
* Passwords, tokens, secrets and API keys are recursively redacted.
* Exception messages are omitted by default.
* Absolute exception paths are converted to project-relative paths.
* Ignored exception classes can be configured.
* Generated tests do not automatically restore redacted secrets.

Even with these protections, captured requests may contain application-specific sensitive information.

Use Laravel Repro only in controlled development or staging environments until the package is production-ready. Protect the configured filesystem, restrict access and define an appropriate retention policy.

## Development

Install the package dependencies:

```bash
composer install
```

Run the test suite:

```bash
vendor/bin/pest
```

On Windows:

```powershell
vendor\bin\pest.bat --no-tia
```

## Roadmap

* [x] Reproduction domain model
* [x] Schema versioning
* [x] Exception snapshots
* [x] Relative exception paths
* [x] Recursive sensitive-data redaction
* [x] Laravel container integration
* [x] Request and exception recorder
* [x] Filesystem JSON storage
* [x] Failure-capture middleware
* [x] Artisan case-listing command
* [x] Artisan test-generation command
* [x] Pest regression-test generation
* [x] Duplicate-test protection
* [x] Laravel 13 rendered-exception capture
* [ ] Request replay
* [ ] Database storage driver
* [ ] External HTTP request capture and mocking
* [ ] Continuous integration across supported PHP and Laravel versions
* [ ] Packagist release

## Contributing

Laravel Repro is still evolving. Bug reports, tests and focused pull requests are welcome.

Before submitting a change, make sure the test suite passes:

```bash
vendor/bin/pest
```

## License

Laravel Repro is open-source software licensed under the [MIT License](LICENSE.md).
