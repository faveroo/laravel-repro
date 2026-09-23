# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.1.0]

### Added

- Request and exception recording with sensitive-data redaction.
- Filesystem JSON storage for reproduction cases.
- Artisan commands for listing cases and generating Pest regression tests.
- Duplicate-test protection.
- Continuous integration on PHP 8.3, 8.4 and 8.5.

### Fixed

- Capture of exceptions rendered by Laravel 13's default exception handler (`cd7f885`).
