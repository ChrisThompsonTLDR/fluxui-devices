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
- 🆓 **Custom Icons** - Device type icons that work without Flux Pro license

## Requirements

- PHP 8.2+
- Laravel 11.0+ or 12.0+
- Livewire 3.0+
- Livewire Volt 1.0+
- [diego-ninja/laravel-devices](https://github.com/diego-ninja/laravel-devices) ^2.0
- [Flux UI](https://fluxui.dev) (Pro license recommended, but not required for basic functionality)

## Installation

1. Install and configure [diego-ninja/laravel-devices](https://github.com/diego-ninja/laravel-devices)

2. Install the package via Composer:

```bash
composer require christhompsontldr/fluxui-devices
```

3. Add the `HasDevices` trait to your User model:

```php
use Ninja\DeviceTracker\Traits\HasDevices;

class User extends Authenticatable
{
    use HasDevices;
    // ...
}
```

4. Optionally publish the views for customization (includes custom icons):

```bash
php artisan vendor:publish --provider="ChrisThompsonTLDR\\FluxuiDevices\\FluxuiDevicesServiceProvider"
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

1. Publish the package views:

```bash
php artisan vendor:publish --provider="ChrisThompsonTLDR\\FluxuiDevices\\FluxuiDevicesServiceProvider"
```

2. Add a new nav item in `resources/views/components/settings/layout.blade.php`:

```blade
<flux:navlist.item :href="config('devices.device_route')" wire:navigate>{{ __('Devices') }}</flux:navlist.item>
```

3. Add the route to `routes/web.php`:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get(config('devices.device_route'), function () {
        return view('settings.devices');
    })->name('devices.show');
});
```

## Customization

### Publishing Views

To customize the component views:

```bash
php artisan vendor:publish --provider="ChrisThompsonTLDR\\FluxuiDevices\\FluxuiDevicesServiceProvider"
```

### Action Message Component

The components use an `action-message` component for success feedback. This component should be available if you're using the Laravel Livewire Starter Kit. If you don't have it, you can create a simple one:

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

Or simply replace `<x-action-message on="event-name">Success!</x-action-message>` with your own success notification component.

## Security

All destructive actions (signing out devices, ending sessions) require the user to confirm their password. This follows the same security pattern used in Livewire Starter Kit.

## Credits

- [Chris Thompson](https://github.com/christhompsontldr)
- [diego-ninja/laravel-devices](https://github.com/diego-ninja/laravel-devices) - The underlying device tracking package
- [Laravel Jetstream](https://jetstream.laravel.com) - Inspiration for the browser sessions UI pattern
- [Flux UI](https://fluxui.dev) - The beautiful UI component library
- [Lucide](https://lucide.dev) - Icon library used for custom device type icons

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
