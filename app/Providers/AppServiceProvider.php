<?php

namespace App\Providers;

use App\Events\TaskAssigned;
use App\Events\TaskCommentAdded;
use App\Events\TaskDelegated;
use App\Events\TaskStatusChanged;
use App\Listeners\SendTaskAssignedNotification;
use App\Listeners\SendTaskCommentNotification;
use App\Listeners\SendTaskDelegatedNotification;
use App\Listeners\SendTaskStatusNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(TaskAssigned::class, SendTaskAssignedNotification::class);
        Event::listen(TaskDelegated::class, SendTaskDelegatedNotification::class);
        Event::listen(TaskStatusChanged::class, SendTaskStatusNotification::class);
        Event::listen(TaskCommentAdded::class, SendTaskCommentNotification::class);
    }
}
