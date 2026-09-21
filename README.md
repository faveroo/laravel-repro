# Laravel Repro

Turn real Laravel failures into reproducible regression tests.

> Laravel Repro is currently under active development and is not yet ready for production use.

## About

Laravel Repro is a Laravel package designed to capture failed HTTP requests, remove sensitive information, and transform failures into reproducible test cases.

The long-term goal is to generate Pest regression tests from errors that occurred during development or staging.

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

## Current Features

* Immutable reproduction cases
* Serializable exception snapshots
* Relative and sanitized exception paths
* Recursive sensitive-data redaction
* Configurable sensitive keys
* Laravel Service Provider
* Dependency injection through Laravel's container
* Automated tests with Pest

## Requirements

* PHP 8.2 or newer
* Laravel
* Composer

## Configuration

The package configuration will be published with:

```bash
php artisan vendor:publish --tag=repro-config
```

Example configuration:

```php
return [
    'enabled' => env('LARAVEL_REPRO_ENABLED', false),

    'disk' => env('LARAVEL_REPRO_DISK', 'local'),

    'path' => env('LARAVEL_REPRO_PATH', 'laravel-repro'),

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
];
```

## Sensitive Data Redaction

The redactor removes configured sensitive values recursively:

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

## Security

Laravel Repro is designed to avoid storing sensitive data by default.

* Authorization and cookie headers are not captured.
* Passwords, tokens and API keys are redacted recursively.
* Exception messages are omitted by default.
* Absolute filesystem paths are converted to project-relative paths.

Captured data should still be stored in a protected filesystem and must never be committed to version control.

## Testing

Install the dependencies:

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
* [x] Exception snapshots
* [x] Recursive data redaction
* [x] Laravel container integration
* [ ] Request and exception recorder
* [ ] JSON and database storage drivers
* [ ] Artisan commands
* [ ] Request replay
* [ ] Pest test generation
* [ ] External HTTP request capture
* [ ] Packagist release

## License

Laravel Repro is open-source software licensed under the [MIT license](LICENSE).
