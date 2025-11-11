# Filament Team Management

[![Latest Version on Packagist](https://img.shields.io/packagist/v/stats4sd/filament-team-management.svg?style=flat-square)](https://packagist.org/packages/stats4sd/filament-team-management)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/stats4sd/filament-team-management/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/stats4sd/filament-team-management/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/stats4sd/filament-team-management/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/stats4sd/filament-team-management/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/stats4sd/filament-team-management.svg?style=flat-square)](https://packagist.org/packages/stats4sd/filament-team-management)

Package for an opinionated 'teams' setup, including invites and integration with Laravel Filament + Spatie User Roles.


# Installation

You can install the package via composer:

```bash
composer require stats4sd/filament-team-management
```

Then, run the provided installation script. It's recommended to do a git commit before running this command, as it will update several files in your app. You will want to review these changes after running the command. 

```bash
php artisan filament-team-management:install
```

The script will ask you if you want to use the concept of "programs" in your app. It will then make the following changes: 

1. It will publish the appropriate migration files.
2. It will update your .env file with the appropriate variables. 
3. It will offer to add some example Database Seeders to your main `database/seeders/DatabaseSeeder.php` file.



# Integration into your app

## Models

The package provides 3 Eloquent Models that you can use in your app:
- `Stats4sd\FilamentTeamManagement\Models\Team` - represents a Team. Teams can have many Users.
- `Stats4sd\FilamentTeamManagement\Models\Program` - represents a Program. Programs can have many Teams. This model is optional, depending on whether you chose to use Programs during installation.
- `Stats4sd\FilamentTeamManagement\Models\User` - extends the default Laravel User model to add relationships to Teams (and Programs, if used), and includes traits for Spatie Roles and Filament User functionality.

In theory, you can use these models as-is in your app. However, in practice, you will likely want to extend them to add your own fields and functionality. 
To do this, create your own Models that extend the package Models. For example, you might create `App\Models\Team` like this:

```php
namespace App\Models;
use Stats4sd\FilamentTeamManagement\Models\Team as BaseTeam;

class Team extends BaseTeam
{
    // Add your customizations and overrides here
}
```
Then, update your .env file to point to your own model:

```
FILAMENT_TEAM_MANAGEMENT_TEAM_MODEL=App\Models\Team
```

> [!NOTE] 
> You are not required to call your custom models `Team`, `Program` or `User`. You can name them whatever you like, as long as you update the .env variables to point to your custom models. Use the other .env variables to point to the correct database tables, foreign key column names and pivot tables if you have changed them. 

## Filament Panels

The package does not provide its own Filament Panel (yet). Instead, you are expected to integrate the package's pages and resources into your own Filament Panels. 

The package's resources and pages are namespaced into 3 groups, depending on their intended panel:
- `Stats4sd\FilamentTeamManagement\Filament\Admin` - Resources and pages intended for site-wide administrators to manage users, teams and programs.
- `Stats4sd\FilamentTeamManagement\Filament\Program` - Resources and pages intended for program managers to manage users and teams within their own program. 
- `Stats4sd\FilamentTeamManagement\Filament\App` - Resources and pages intended for general users of the application. This includes pages to manage the current team. 

The default approach is that you will have 2 or 3 different Filament Panels in your app:
- An "Admin" panel for site-wide admins.
- An "App" panel for general users of the application.
- Optionally, a "Program" panel for program managers.

### App Panel

The "App" panel is the main entry point for your application. This is where your users will log in and manage their teams. 

To configure your "App" panel to use the authentication and team management features from this package,, do the following:

```php

return $panel
    ...
    // Make sure this is the default panel
    ->default()
    // Add the authentication pages from the package
    ->login(Stats4sd\FilamentTeamManagement\Filament\Auth\Login::class)
    ->registration(Stats4sd\FilamentTeamManagement\Filament\Auth\Register::class)
    
    // Add multitenancy (using your 'team' model) and the profile + registration pages
    ->tenant(App\Models\Team::class) // your team model
    ->tenantProfile(Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam\ManageTeam::class)
    ->tenantRegistration(Stats4sd\FilamentTeamManagement\Filament\App\Pages\RegisterTeam::class)

    // Add the resources and pages from the App namespace
    ->discoverPages(
        in: base_path('vendor/stats4sd/filament-team-management/src/Filament/App/Pages'), 
        for: 'Stats4sd\FilamentTeamManagement\Filament\App\Pages'
    )
    
    // optionally, add a link to the admin panel
    ->navigationItems([
        NavigationItem::make('admin')
            ->url('/admin')
            ->label('Admin Panel')
            ->icon('heroicon-o-cog')
            ->visible(fn () => auth()->user()->can('viewAdminPanel')),
    ]);
```

You can edit any of the pages or resources from the package by creating new classes and extending the package classes. For example, to customize the `ManageTeam` page, create a new class in your app like this:

```php
namespace App\Filament\Pages\ManageTeam;   

use Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam\ManageTeam as BaseManageTeam;

class ManageTeam extends BaseManageTeam
{
    // Add your customizations and overrides here
}
```

Then, update the `tenantProfile` method in your panel provider to point to your new class.

### Program Panel
If you are using the "program" concept in your app, you may want to create a separate Filament Panel for program managers. This panel will allow program managers to manage users and teams within their own program.

To configure your "Program" panel, do the following:

```php

return $panel
    ...
    // Add multitenancy (using your 'program' model)
    ->tenant(App\Models\Program::class) // your program model
    ->tenantProfile(Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram\ManageProgram::class)
    ->tenantRegistration(Stats4sd\FilamentTeamManagement\Filament\Program\Pages\RegisterProgram::class)

    // Add the resources and pages from the Program namespace
    ->discoverPages(
        in: base_path('vendor/stats4sd/filament-team-management/src/Filament/Program/Pages'), 
        for: 'Stats4sd\FilamentTeamManagement\Filament\Program\Pages'
    )
    
    // the package assumes that all users will register and log in via the 'App' (default) panel, so this panel should not have a `login()` or `registration()` method. To ensure users are redirected to the correct login page, add _replace_ the `Authenticate::class` in authMiddleware with the following:
    ->authMiddleware([
        \Stats4sd\FilamentTeamManagement\Http\Middleware\AuthenticateThroughDefaultPanel::class,
    ])
    
    // optionally, add links to the other panels
    ->navigationItems([
        NavigationItem::make('admin')
            ->url('/admin')
            ->label('Admin Panel')
            ->icon('heroicon-o-cog')
            ->visible(fn() => auth()->user()->can('viewAdminPanel')),
        NavigationItem::make('app')
            ->url('/')
            ->icon('heroicon-o-arrow-left')
            ->label('Back to Front End'),
    ]);

```
    
### Admin Panel
The "Admin" panel is for site-wide administrators to manage users, teams and programs. To configure your "Admin" panel, do the following:

```php

return $panel
    ...
    // Add the resources and pages from the Admin namespace
    ->discoverPages(
        in: base_path('vendor/stats4sd/filament-team-management/src/Filament/Admin/Pages'), 
        for: 'Stats4sd\FilamentTeamManagement\Filament\Admin\Pages'
    )
    ->discoverResources(
        in: base_path('vendor/stats4sd/filament-team-management/src/Filament/Admin/Resources'), 
        for: 'Stats4sd\FilamentTeamManagement\Filament\Admin\Resources'
    )
    
    // the package assumes that all users will register and log in via the 'App' (default) panel, so this panel should not have a `login()` or `registration()` method. To ensure users are redirected to the correct login page, add _replace_ the `Authenticate::class` in authMiddleware with the following:
    ->authMiddleware([
        \Stats4sd\FilamentTeamManagement\Http\Middleware\AuthenticateThroughDefaultPanel::class,
    ])
    
    // optionally, add links to the other panels
    ->navigationItems([
        NavigationItem::make('program')
            ->url('/program')
            ->label('Go to program panel')
            ->icon('heroicon-o-cog')
            ->visible(fn() => auth()->user()->can('viewAdminPanel')),
        NavigationItem::make('app')
            ->url('/')
            ->icon('heroicon-o-arrow-left')
            ->label('Back to Front End'),
    ]);

```

### Invitations and User Registration

This package includes the needed setup to let your users invite other users via email. You can invite a new user to join a specific team, a specific program, or with an assigned site-wide role.  

The setup described above uses the package's Auth pages for login and registration through the defualt 'App' panel. These pages include the needed functionality to handle invitations and user registration. Through this default setup: 

- Users can _only_ register via an invitation. Going to the registration page without an invitation code will redirect to the login page.
- Team members can invite new users to join their team through the "Manage Team" page.
- Program managers can invite new users to join their program through the "Manage Program" page.
- Site-wide admins can invite new users to the system through the "Manage Users" resource in the Admin panel. They can also invite users to join specific teams or programs through the Team and Program resources in the Admin panel.

TODO: check how invitation email customisation can work. 


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
