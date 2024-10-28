# Package for an opinionated 'teams' setup, including invites and integration with Laravel Filament + Spatie User Roles

[![Latest Version on Packagist](https://img.shields.io/packagist/v/stats4sd/filament-team-management.svg?style=flat-square)](https://packagist.org/packages/stats4sd/filament-team-management)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/stats4sd/filament-team-management/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/stats4sd/filament-team-management/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/stats4sd/filament-team-management/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/stats4sd/filament-team-management/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/stats4sd/filament-team-management.svg?style=flat-square)](https://packagist.org/packages/stats4sd/filament-team-management)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require stats4sd/filament-team-management
```

This package requires Laravel Filament and this package: https://filamentphp.com/plugins/tharinda-rodrigo-spatie-roles-permissions to be installed. Make sure you follow the installation instructions for those packages first:

- https://filamentphp.com/plugins/tharinda-rodrigo-spatie-roles-permissions


You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-team-management-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-team-management-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-team-management-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentTeamManagement = new Stats4sd\FilamentTeamManagement();
echo $filamentTeamManagement->echoPhrase('Hello, Stats4sd!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Dan Tang](https://github.com/stats4sd)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
