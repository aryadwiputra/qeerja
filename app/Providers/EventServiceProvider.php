<?php

namespace App\Providers;

use App\Events\TaskCreated;
use App\Events\TaskFieldUpdated;
use App\Listeners\DispatchAutomationEvents;
use App\Listeners\DispatchTaskCreatedAutomation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TaskFieldUpdated::class => [
            DispatchAutomationEvents::class,
        ],
        TaskCreated::class => [
            DispatchTaskCreatedAutomation::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
