<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;
use Ninja\DeviceTracker\Models\Device;

new class extends Component {
    public bool $confirmingSignOut = false;

    public ?string $deviceUuid = null;

    public string $password = '';

    public function getDevicesProperty()
    {
        $user = Auth::user();

        if (! $user || ! method_exists($user, 'devices')) {
            return collect();
        }

        return $user->devices()
            ->with(['sessions' => function ($query) use ($user) {
                $query->where('user_id', $user->getAuthIdentifier())
                    ->whereNull('finished_at')
                    ->orderByDesc('last_activity_at');
            }])
            ->get()
            ->sortByDesc(fn (Device $device) => $device->isCurrent());
    }

    public function confirmSignOut(string $uuid): void
    {
        $this->deviceUuid = $uuid;
        $this->password = '';
        $this->confirmingSignOut = true;

        $this->dispatch('confirming-sign-out-device');
    }

    public function signOutDevice(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($this->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        $user = Auth::user();

        // Only look up devices belonging to the current user
        if (method_exists($user, 'devices')) {
            $device = $user->devices()
                ->where('uuid', $this->deviceUuid)
                ->first();

            if ($device) {
                // End all sessions for this device belonging to the current user
                $device->sessions()
                    ->where('user_id', $user->getAuthIdentifier())
                    ->whereNull('finished_at')
                    ->each(fn ($session) => $session->end());
            }
        }

        $this->confirmingSignOut = false;
        $this->deviceUuid = null;
        $this->password = '';

        $this->dispatch('device-signed-out');
    }

    public function signOutAllDevices(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($this->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        $user = Auth::user();

        if (method_exists($user, 'devices')) {
            $user->devices()->get()->each(function (Device $device) use ($user) {
                if (! $device->isCurrent()) {
                    $device->sessions()
                        ->where('user_id', $user->getAuthIdentifier())
                        ->whereNull('finished_at')
                        ->each(fn ($session) => $session->end());
                }
            });
        }

        $this->confirmingSignOut = false;
        $this->deviceUuid = null;
        $this->password = '';

        $this->dispatch('all-devices-signed-out');
    }

    public function cancelSignOut(): void
    {
        $this->confirmingSignOut = false;
        $this->deviceUuid = null;
        $this->password = '';
    }
}; ?>

<section class="space-y-6">
    <div class="relative mb-5">
        <flux:heading>{{ __('Device Management') }}</flux:heading>
        <flux:subheading>{{ __('Manage and sign out your active devices.') }}</flux:subheading>
    </div>

    <flux:text class="max-w-xl">
        {{ __('If necessary, you may sign out of all of your other device sessions across all of your devices. Some of your recent devices are listed below. If you feel your account has been compromised, you should also update your password.') }}
    </flux:text>

    @if (count($this->devices) > 0)
        <div class="mt-5 space-y-6">
            @foreach ($this->devices as $device)
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            @if ($device->device_type === 'desktop')
                                <flux:icon.computer-desktop class="size-8 text-zinc-500 dark:text-zinc-400" />
                            @elseif ($device->device_type === 'tablet')
                                <flux:icon.device-tablet class="size-8 text-zinc-500 dark:text-zinc-400" />
                            @elseif ($device->device_type === 'phone' || $device->device_type === 'mobile')
                                <flux:icon.device-phone-mobile class="size-8 text-zinc-500 dark:text-zinc-400" />
                            @else
                                <flux:icon.computer-desktop class="size-8 text-zinc-500 dark:text-zinc-400" />
                            @endif
                        </div>

                        <div class="ms-3">
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $device->platform ?? __('Unknown Platform') }} - {{ $device->browser ?? __('Unknown Browser') }}
                            </div>

                            <div>
                                <div class="text-xs text-zinc-500">
                                    @php
                                        $latestSession = $device->sessions->first();
                                    @endphp

                                    @if ($latestSession)
                                        {{ $latestSession->ip }},
                                    @endif

                                    @if ($device->isCurrent())
                                        <span class="font-semibold text-green-500">{{ __('This device') }}</span>
                                    @elseif ($latestSession && $latestSession->last_activity_at)
                                        {{ __('Last active') }} {{ $latestSession->last_activity_at->diffForHumans() }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (! $device->isCurrent())
                        <flux:button
                            size="sm"
                            variant="ghost"
                            wire:click="confirmSignOut('{{ $device->uuid }}')"
                        >
                            {{ __('Sign out') }}
                        </flux:button>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <flux:text class="text-zinc-500">
            {{ __('No devices found.') }}
        </flux:text>
    @endif

    <div class="flex items-center mt-5 gap-4">
        <flux:modal.trigger name="confirm-sign-out-all-devices">
            <flux:button wire:loading.attr="disabled">
                {{ __('Sign Out Other Devices') }}
            </flux:button>
        </flux:modal.trigger>

        <x-action-message class="me-3" on="all-devices-signed-out">
            {{ __('Done.') }}
        </x-action-message>
    </div>

    {{-- Sign out specific device modal --}}
    <flux:modal wire:model.self="confirmingSignOut" name="confirm-sign-out-device" class="max-w-lg" focusable>
        <form wire:submit="signOutDevice" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Sign Out Device') }}</flux:heading>
                <flux:subheading>
                    {{ __('Please enter your password to confirm you would like to sign out of this device.') }}
                </flux:subheading>
            </div>

            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                autocomplete="current-password"
                x-data="{}"
                x-on:confirming-sign-out-device.window="setTimeout(() => $el.querySelector('input').focus(), 250)"
            />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:button variant="filled" wire:click="cancelSignOut">
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="danger" type="submit">
                    {{ __('Sign Out Device') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Sign out all devices modal --}}
    <flux:modal name="confirm-sign-out-all-devices" class="max-w-lg" focusable>
        <form wire:submit="signOutAllDevices" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Sign Out All Other Devices') }}</flux:heading>
                <flux:subheading>
                    {{ __('Please enter your password to confirm you would like to sign out of all your other devices across all of your browsers.') }}
                </flux:subheading>
            </div>

            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                autocomplete="current-password"
            />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit">
                    {{ __('Sign Out All Devices') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
