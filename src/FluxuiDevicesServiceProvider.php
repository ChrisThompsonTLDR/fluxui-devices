<?php

namespace ChrisThompsonTLDR\FluxuiDevices;

use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;

class FluxuiDevicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'fluxui-devices');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/components'),
            ], 'fluxui-devices-views');
        }

        Volt::mount([
            __DIR__.'/../resources/views/livewire' => 'fluxui-devices',
        ]);
    }
}
