# FluxUI Devices

A FluxUI front-end for managing devices and sessions using [diego-ninja/laravel-devices](https://github.com/diego-ninja/laravel-devices). Designed to cleanly slot into the [Laravel Livewire Starter Kit](https://github.com/laravel/livewire-starter-kit) with [Flux UI](https://fluxui.dev) components.

Inspired by Laravel Jetstream's browser session management, this package provides a beautiful and functional interface for users to manage their authenticated devices and active sessions.

## Features

- 📱 **Device Management** - View all devices that have been used to access the account
- 🔐 **Session Management** - View and manage all active browser sessions
- 🚪 **Remote Sign Out** - Sign out of specific devices or all other devices
- 🌍 **Location Display** - Shows session location information when available
- 🎨 **FluxUI Components** - Beautiful, consistent UI using Flux components
- ⚡ **Livewire Volt** - Modern reactive components with Volt

## Requirements

- PHP 8.2+
- Laravel 11.0+ or 12.0+
- Livewire 3.0+ or 4.0+
- Livewire Volt 1.0+
- [diego-ninja/laravel-devices](https://github.com/diego-ninja/laravel-devices) ^2.0
- [Flux UI](https://fluxui.dev) (requires license)

### Important Notes

- **String User IDs**: If your application uses string-based user IDs (e.g., UUIDs or custom IDs), you'll need to update the published migrations to use `string` instead of `integer` or `bigInteger` for `user_id` columns in:
  - `device_sessions` table (`user_id` and `blocked_by`)
  - `google_2fa` table (`user_id`)
  - `user_devices` table (`user_id`)

- **MaxMind Location Provider**: The `MaxmindLocationProvider` requires a GeoIP2 database file. If you don't have one configured, you should remove it from the `location_providers` array in `config/devices.php` to avoid binding resolution errors.

## Installation

1. Install the package via Composer:

```bash
composer require christhompsontldr/fluxui-devices
```

2. Ensure you have `diego-ninja/laravel-devices` installed and configured:

```bash
composer require diego-ninja/laravel-devices
php artisan vendor:publish --provider="Ninja\DeviceTracker\DeviceTrackerServiceProvider"
php artisan migrate
```

3. Add the `HasDevices` and `Has2FA` traits to your User model:

```php
use Ninja\DeviceTracker\Traits\Has2FA;
use Ninja\DeviceTracker\Traits\HasDevices;

class User extends Authenticatable
{
    use Has2FA;
    use HasDevices;
    // ...
}
```

**Note:** The `Has2FA` trait is required for the package to check if 2FA is enabled for users. If you're not using 2FA, you can still include the trait - it will simply return `false` when checking if 2FA is enabled.

4. Optionally publish the views for customization:

```bash
php artisan vendor:publish --tag=fluxui-devices-views
```

## Usage

### Device Management Component

Add the device management component to your settings page:

```blade
<livewire:fluxui-devices::device-manager />
```

This component displays:
- All devices that have accessed the user's account
- Device type (desktop, tablet, mobile) with appropriate icons
- Browser and platform information
- Last activity time
- Current device indicator
- Sign out button for each device (except current)
- "Sign Out Other Devices" button for bulk sign out

### Session Management Component

Add the session management component to your settings page:

```blade
<livewire:fluxui-devices::session-manager />
```

This component displays:
- All active sessions for the user
- Device information for each session
- IP address and location
- Session status badges (active, blocked, locked, etc.)
- Current session indicator
- End session button for each session (except current)
- "End Other Sessions" button for bulk sign out

### Integration with Laravel Livewire Starter Kit

To add these components to the settings page in the Laravel Livewire Starter Kit:

1. Add a new nav item in `resources/views/components/settings/layout.blade.php`:

```blade
<flux:navlist.item :href="route('devices.show')" wire:navigate>{{ __('Devices') }}</flux:navlist.item>
```

2. Create a new route in `routes/web.php`:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/settings/devices', function () {
        return view('settings.devices');
    })->name('devices.show');
});
```

3. Create the view file `resources/views/settings/devices.blade.php`:

```blade
<x-settings.layout heading="Devices & Sessions" subheading="Manage your devices and active sessions">
    <livewire:fluxui-devices::device-manager />

    <flux:separator class="my-10" />

    <livewire:fluxui-devices::session-manager />
</x-settings.layout>
```

## Customization

### Publishing Views

To customize the component views:

```bash
php artisan vendor:publish --tag=fluxui-devices-views
```

Views will be published to `resources/views/vendor/fluxui-devices/`.

### Action Message Component

The components use an `action-message` component for success feedback. If you don't have this component from the Laravel Livewire Starter Kit, you can create a simple one:

```blade
{{-- resources/views/components/action-message.blade.php --}}
@props(['on'])

<div x-data="{ shown: false }"
     x-init="@this.on('{{ $on }}', () => { shown = true; setTimeout(() => shown = false, 2000) })"
     x-show="shown"
     x-transition
     {{ $attributes }}>
    {{ $slot }}
</div>
```

## Security

All destructive actions (signing out devices, ending sessions) require the user to confirm their password. This follows the same security pattern used in Laravel Jetstream.

## Credits

- [Chris Thompson](https://github.com/christhompsontldr)
- [diego-ninja/laravel-devices](https://github.com/diego-ninja/laravel-devices) - The underlying device tracking package
- [Laravel Jetstream](https://jetstream.laravel.com) - Inspiration for the browser sessions UI pattern
- [Flux UI](https://fluxui.dev) - The beautiful UI component library

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
