<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;
use Ninja\DeviceTracker\Models\Session;

new class extends Component {
    public bool $confirmingEndSession = false;

    public bool $confirmingEndAllSessions = false;

    public ?string $sessionUuid = null;

    public string $password = '';

    public function getSessionsProperty()
    {
        $user = Auth::user();

        if (! $user || ! method_exists($user, 'sessions')) {
            return collect();
        }

        return $user->sessions()
            ->with('device')
            ->whereNull('finished_at')
            ->orderByDesc('last_activity_at')
            ->get();
    }

    public function confirmEndSession(string $uuid): void
    {
        $this->resetErrorBag();
        $this->sessionUuid = $uuid;
        $this->password = '';
        $this->confirmingEndSession = true;

        $this->dispatch('confirming-end-session');
    }

    public function endSession(): void
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

        // Only look up sessions belonging to the current user
        if (method_exists($user, 'sessions')) {
            $session = $user->sessions()
                ->where('uuid', $this->sessionUuid)
                ->whereNull('finished_at')
                ->first();

            if ($session) {
                $session->end();
            }
        }

        $this->confirmingEndSession = false;
        $this->sessionUuid = null;
        $this->password = '';

        $this->dispatch('session-ended');
    }

    public function endAllSessions(): void
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

        if (method_exists($user, 'sessions')) {
            $user->sessions()
                ->whereNull('finished_at')
                ->get()
                ->reject(fn (Session $session) => $session->isCurrent())
                ->each(fn (Session $session) => $session->end());
        }

        $this->confirmingEndSession = false;
        $this->confirmingEndAllSessions = false;
        $this->sessionUuid = null;
        $this->password = '';

        $this->dispatch('all-sessions-ended');
    }

    public function confirmEndAllSessions(): void
    {
        $this->resetErrorBag();
        $this->sessionUuid = null;
        $this->password = '';
        $this->confirmingEndAllSessions = true;
    }

    public function cancelEndSession(): void
    {
        $this->confirmingEndSession = false;
        $this->confirmingEndAllSessions = false;
        $this->sessionUuid = null;
        $this->password = '';
    }
}; ?>

<section class="space-y-6">
    <div class="relative mb-5">
        <flux:heading>{{ __('Session Management') }}</flux:heading>
        <flux:subheading>{{ __('Manage and end your active browser sessions.') }}</flux:subheading>
    </div>

    <flux:text class="max-w-xl">
        {{ __('If necessary, you may end all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.') }}
    </flux:text>

    @if (count($this->sessions) > 0)
        <div class="mt-5 space-y-6">
            @foreach ($this->sessions as $session)
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            @if ($session->device && $session->device->device_type === 'desktop')
                                <flux:icon name="computer-desktop" class="size-8 text-zinc-500 dark:text-zinc-400" />
                            @elseif ($session->device && $session->device->device_type === 'tablet')
                                <flux:icon name="device-tablet" class="size-8 text-zinc-500 dark:text-zinc-400" />
                            @elseif ($session->device && ($session->device->device_type === 'phone' || $session->device->device_type === 'mobile'))
                                <flux:icon name="device-phone-mobile" class="size-8 text-zinc-500 dark:text-zinc-400" />
                            @else
                                <flux:icon name="computer-desktop" class="size-8 text-zinc-500 dark:text-zinc-400" />
                            @endif
                        </div>

                        <div class="ms-3">
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                @if ($session->device)
                                    {{ $session->device->platform ?? __('Unknown Platform') }} - {{ $session->device->browser ?? __('Unknown Browser') }}
                                @else
                                    {{ __('Unknown Device') }}
                                @endif
                            </div>

                            <div>
                                <div class="text-xs text-zinc-500">
                                    {{ $session->ip }},

                                    @if ($session->isCurrent())
                                        <span class="font-semibold text-green-500">{{ __('This session') }}</span>
                                    @elseif ($session->last_activity_at)
                                        {{ __('Last active') }} {{ $session->last_activity_at->diffForHumans() }}
                                    @endif

                                    @if ($session->location && $session->location->city)
                                        <span class="mx-1">•</span>
                                        {{ $session->location->city }}{{ $session->location->country ? ', ' . $session->location->country : '' }}
                                    @endif
                                </div>
                            </div>

                            @if ($session->status && $session->status->value !== 'active')
                                <div class="mt-1">
                                    <flux:badge
                                        size="sm"
                                        :color="match($session->status->value) {
                                            'blocked' => 'red',
                                            'locked' => 'amber',
                                            'inactive' => 'zinc',
                                            default => 'zinc',
                                        }"
                                    >
                                        {{ ucfirst($session->status->value) }}
                                    </flux:badge>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (! $session->isCurrent())
                        <flux:button
                            size="sm"
                            variant="ghost"
                            wire:click="confirmEndSession('{{ $session->uuid }}')"
                        >
                            {{ __('End session') }}
                        </flux:button>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <flux:text class="text-zinc-500">
            {{ __('No active sessions found.') }}
        </flux:text>
    @endif

    <div class="flex items-center mt-5 gap-4">
        <flux:button wire:click="confirmEndAllSessions" wire:loading.attr="disabled">
            {{ __('End Other Sessions') }}
        </flux:button>

        <x-action-message class="me-3" on="all-sessions-ended">
            {{ __('Done.') }}
        </x-action-message>
    </div>

    {{-- End specific session modal --}}
    <flux:modal wire:model.self="confirmingEndSession" name="confirm-end-session" class="max-w-lg" focusable>
        <form wire:submit="endSession" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('End Session') }}</flux:heading>
                <flux:subheading>
                    {{ __('Please enter your password to confirm you would like to end this session.') }}
                </flux:subheading>
            </div>

            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                autocomplete="current-password"
                x-data="{}"
                x-on:confirming-end-session.window="setTimeout(() => $el.querySelector('input').focus(), 250)"
            />

            <flux:error name="password" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:button variant="filled" wire:click="cancelEndSession">
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="danger" type="submit">
                    {{ __('End Session') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- End all sessions modal --}}
    <flux:modal wire:model.self="confirmingEndAllSessions" name="confirm-end-all-sessions" class="max-w-lg" focusable>
        <form wire:submit="endAllSessions" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('End All Other Sessions') }}</flux:heading>
                <flux:subheading>
                    {{ __('Please enter your password to confirm you would like to end all of your other browser sessions.') }}
                </flux:subheading>
            </div>

            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                autocomplete="current-password"
            />

            <flux:error name="password" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:button variant="filled" wire:click="cancelEndSession">
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="danger" type="submit">
                    {{ __('End All Sessions') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
