# Setup new web platform with this package

The following instructions will guide you through the process of setting up a new web platform using this package, including the installation of Laravel Filament and the required roles and permissions package. They 
 assume you are starting from a fresh Laravel 11 Installation. If you have an existing app, you may need to adapt some of the instructions.


###  1. Initial setup:

```bash

# Install Laravel
laravel new my-app 
### choose none for the 'starter package' ### 



# Change into the new directory + setup
cd my-app
composer require stats4sd/filament-team-management
### this package requires filament and the spatie roles + permissions, so they will be installed as dependencies ###

# Set up the Filament wrapper for the roles and permissions package (https://filamentphp.com/plugins/tharinda-rodrigo-spatie-roles-permissions)
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Set up at least one Filament Panel
php artisan filament:install --panels

```

### 2. Run Installation Script

`php artisan filament-team-management:install`

This will:
- ask if you want to use 'programs' or not.
- publish the required migrations
- add the needed environment variables to your .env and .env.exmaple files (if they don't already exist)
- optionally run the migrations
- optionally add the test DB seeders to your DatabaseSeeder.php file

### 3. Setup Models:

1. Make sure your App\Models\User model extends the Stats4SD\FilamentTeamManagement\Models\User model
2. If you want a custom Team and Program model, create them and extend the Stats4SD\FilamentTeamManagement\Models\Team and Stats4SD\FilamentTeamManagement\Models\Program models respectively.
   - Remember to update the .env variables to point to your custom models

### 4. Make sure you require the needed pages into your Filament Panel

To enable the custom registration pages, you need to explicitly register them into your panel - otherwise you will get a "Class not found" error when trying to load the registration page. 

In your AppPanelProvider (or which-ever panel you use for logins):

```php
$panel
...
->discoverPages(in: app_path('../vendor/stats4sd/filament-team-management/src/Filament/App/Pages'), for: 'Stats4sd\\FilamentTeamManagement\\Filament\\App\\Pages')

```
