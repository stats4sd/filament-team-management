# Filament Team Management

[![Latest Version on Packagist](https://img.shields.io/packagist/v/stats4sd/filament-team-management.svg?style=flat-square)](https://packagist.org/packages/stats4sd/filament-team-management)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/stats4sd/filament-team-management/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/stats4sd/filament-team-management/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/stats4sd/filament-team-management/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/stats4sd/filament-team-management/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/stats4sd/filament-team-management.svg?style=flat-square)](https://packagist.org/packages/stats4sd/filament-team-management)

Package for an opinionated 'teams' setup, including invites and integration with Laravel Filament + Spatie User Roles.


## Installation

You can install the package via composer:

```bash
composer require stats4sd/filament-team-management
```

Then, run the provided installation script. 

```bash
php artisan filament-team-management:install
```

The script will ask you if you want to use the concept of "programs" in your app. Based on your response, it will publish the appropriate migration files and update your .env file with the appropriate variables. It will also offer to add some example Database Seeders to your main `database/seeders/DatabaseSeeder.php` file. 


## Features

This app provides a set of models, Filament Resources and Mail classes that lets you quickly setup a Filament-based application that uses Teams to manage users. 


### Filament Resources

The intention is that this package is used for apps where you have 2 different Filament Panels: 

- An "Admin" panel, for administrators to manage users and teams.
- An "App" panel, which uses multitenancy with teams as the tennant.

You can also optionally use 'programs', which are groups of teams. In this case, you may have a 3rd panel:

- A "Program" panel, for program managers to manage the users and teams within their own program.

The Resources in this package are intended for one of these 3 panels, shown by their namespacing within the package. You can add these Resource classes to any panel in your `PanelProvider` class:

```php
$panel
  ## add all resources in a namespace at once
  ->discoverResources(in: app_path('../vendor/stats4sd/filament-team-management/src/Filament/Admin/Resources'), for: 'Stats4sd\\FilamentTeamManagement\\Admin\\Resources)
  ## OR... add resources individually
  ->resources([
    Stats4sd\\FilamentTeamManagement\\Admin\\Resources\\TeamResource,
    ...
])
```

### Invitations

This package includes the needed setup to let your users invite other users via email. There are 3 different types of invite:

  - `RoleInvite`: The provided UserResource lets you invite users to join the platform with a specific role
  - `Invite`: The provided TeamResource lets you invite users to join a specific team on the platform.
  - `ProgramInvite`: If you use programs, the provided ProgramResource lets you invite users to join a specific program.
 
To make the registration work, you **must** add the pages in the namespace `Stats4sd\\FilamentTeamManagement\\App\\Pages` to one of your Panels. Otherwise the registration pages will not be correctly registered in the app. 

For example: 

```php
$panel
->discoverPages(in: app_path('../vendor/stats4sd/filament-team-management/src/Filament/App/Pages'), for: 'Stats4sd\\FilamentTeamManagement\\App\\Pages)
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
