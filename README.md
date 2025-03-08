# Socialite Unify

A Laravel package that integrates multiple social platform logins and team management features with Jetstream.

## Features

- **Social Platform Login**: Supports LINE and other social platforms.
- **Multiple Account Binding**: Bind multiple social accounts to a single user.
- **Account Management**: Bind new social accounts, unbind existing ones, and log in with any bound account.
- **Jetstream Integration**: Overrides Jetstream's login, password update, and reset functionality to support `has_password`.
- **Team Management**: Inherits Jetstream's team management features.

## Installation

1. Install the package via Composer:

```bash
composer require bleuren/socialite-unify
```

2. Add the service provider to `config/app.php` (if not using auto-discovery):

```php
'providers' => [
    // ...
    Bleuren\SocialiteUnify\Providers\SocialiteServiceProvider::class,
],
```


3. Important: Comment out or remove the following Fortify actions in your `app/Providers/FortifyServiceProvider.php` to avoid conflicts with the package (if you are using the default Fortify actions in your application):

```php
// app/Providers/FortifyServiceProvider.php
public function boot(): void
{
    // Remove or comment out these lines:
    // Fortify::createUsersUsing(CreateNewUser::class);
    // Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
    // Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

    // Keep other Fortify configurations if needed:
    Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
    // ...
}
```

4. Publish the configurations, migrations and views:

```bash
php artisan vendor:publish --provider="Bleuren\SocialiteUnify\Providers\SocialiteServiceProvider"
```

This will publish:
- Configuration file to `config/socialite-unify.php`
- Migration files to `database/migrations`
- View files to `resources/views/vendor/socialite-unify`

To override Jetstream's password update form (required for social login functionality), you have two options:

1. Using the force publish command:
```bash
php artisan vendor:publish --provider="Bleuren\SocialiteUnify\Providers\SocialiteServiceProvider" --tag=views --force
```

2. Manually update the `resources/views/profile/update-password-form.blade.php` by adding the following conditions:
```blade
<x-slot name="form">
    @if(Auth::user()->has_password)
        <div class="col-span-6 sm:col-span-4">
            <x-label for="current_password" value="{{ __('Current Password') }}" />
            <x-input id="current_password" type="password" class="mt-1 block w-full" wire:model="state.current_password" autocomplete="current-password" />
            <x-input-error for="current_password" class="mt-2" />
        </div>
    @endif
```

4. Run the migrations:

```bash
php artisan migrate
```

5. Configure social platform credentials in your `.env` file and add the following to `config/services.php`:

```env
LINE_CLIENT_ID=your-line-client-id
LINE_CLIENT_SECRET=your-line-client-secret
LINE_REDIRECT_URI=your-line-callback-url
```

```php
// config/services.php
return [
    // ... other services
    'line' => [
        'client_id' => env('LINE_CLIENT_ID'),
        'client_secret' => env('LINE_CLIENT_SECRET'),
        'redirect' => env('LINE_REDIRECT_URI'),
        'bot_prompt' => env('LINE_BOT_PROMPT', 'normal'),
    ],
];
```

6. Update your `User` model to use the `HasSocialiteUnify` trait:

```php
use Bleuren\SocialiteUnify\Traits\HasSocialiteUnify;

class User extends Authenticatable
{
    use HasSocialiteUnify;
    // ...
}
```

7. Add the social accounts form to your profile page (e.g., `resources/views/profile/show.blade.php`):

```blade
<livewire:socialite-unify.social-accounts-form />
```


## Usage

### Social Login

To enable social login, add the login buttons to your login page:

```blade
@foreach(['line'] as $provider)
    <a href="{{ route('socialite.redirect', $provider) }}">
        <x-secondary-button>
            {{ __("Login with " . ucfirst($provider)) }}
        </x-secondary-button>
    </a>
@endforeach
```


### Managing Social Accounts

Users can manage their social accounts from their profile page using the provided Livewire component. They can:

- Bind new social accounts
- Unbind existing social accounts (if they have a password or multiple social accounts)
- View all connected accounts

### Password Management

The package extends Jetstream's password management to handle users who registered via social login:

- Users who registered via social login will have `has_password = false`
- These users can set a password later from their profile
- Password reset and update flows are modified to handle the `has_password` state
- Users must have either a password or at least one social account

### Team Management

The package maintains Jetstream's team management features:

- Each new user gets a personal team
- Team creation and management work as in standard Jetstream
- Social login users are integrated into the team system


## Database Changes (if it's not already done)

This package adds a `social_accounts` table with the following structure:

```php
Schema::create('social_accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->string('provider_name');
    $table->string('provider_id');
    $table->timestamps();
    $table->unique(['provider_name', 'provider_id']);
});
```

And adds a `has_password` boolean column to the users table:

```php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('has_password')->default(true)->after('password');
});
```

## Requirements

- PHP >= 8.2
- Laravel >= 12.0
- Laravel Jetstream >= 5.0
- Laravel Socialite >= 5.0
- Livewire >= 3.0

## Customization

### Adding New Providers

By default, the package supports LINE. To add additional social platforms, you have two options:

#### Option 1: Using Configuration (Recommended)

1. Install the desired provider by following the instructions at [Socialite Providers](https://socialiteproviders.com/). For example, for Facebook:

```bash
composer require socialiteproviders/facebook
```

2. If you haven't published the configuration file yet, publish it first:

```bash
php artisan vendor:publish --provider="Bleuren\SocialiteUnify\Providers\SocialiteServiceProvider" --tag=config
```

3. Add the provider to your `config/socialite-unify.php`:

```php
'providers' => ['line', 'facebook'],
```

4. Register the provider's event listener in your `AppServiceProvider.php` boot method:

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('facebook', \SocialiteProviders\Facebook\Provider::class);
});
```

5. Add the provider's credentials to your `.env` file and `config/services.php`.

6. Add the provider's translation to your language files. You have two options:

   **Option A: Publish all translation files** (recommended for first-time setup):
   ```bash
   php artisan vendor:publish --provider="Bleuren\SocialiteUnify\Providers\SocialiteServiceProvider" --tag=translations
   ```
   Then edit the published files to add your new provider.

   **Option B: Manually add translations** (recommended when adding new providers):
   ```php
   // lang/vendor/socialite-unify/[locale]/socialite.php
   return [
       'providers' => [
           'line' => 'LINE',
           'facebook' => 'Facebook'
       ],
   ];
   ```

#### Option 2: Extending SocialiteManager (Legacy)

If you need more customization, you can extend the SocialiteManager class:

```php
namespace App\Services;

use Bleuren\SocialiteUnify\Services\SocialiteManager;

class CustomSocialiteManager extends SocialiteManager
{
    // Add your custom providers here
    protected array $providers = ['line', 'facebook'];
}
```

Then register your CustomSocialiteManager in `AppServiceProvider`:

```php
use Bleuren\SocialiteUnify\Contracts\SocialiteService;
use App\Services\CustomSocialiteManager;

public function register(): void
{
    $this->app->singleton(SocialiteService::class, CustomSocialiteManager::class);
}
```

### Views

You can publish and customize the views:

```bash
php artisan vendor:publish --provider="Bleuren\SocialiteUnify\Providers\SocialiteServiceProvider" --tag=views
```

This will publish the views to `resources/views/vendor/socialite-unify/`.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
