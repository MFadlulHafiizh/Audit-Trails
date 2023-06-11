<?php

namespace Kuncen\Audittrails;

use Console\ClearCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class AudittrailsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    protected $events = [
        'Illuminate\Auth\Events\Login' => [
            'Kuncen\Audittrails\LogLoginListener',
        ],
        'Illuminate\Auth\Events\Logout' => [
            'Kuncen\Audittrails\LogLogoutListener',
        ]
    ];
    public function register(): void
    {
        // if ($this->app->runningInConsole()) {
        //     $this->commands([
        //         ClearCommand::class,
        //     ]);
        // }
    }
    protected function registerEvents()
    {
        $events = $this->app->make(Dispatcher::class);

        foreach ($this->events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerEvents();
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ],'authentication-log-migrations');
        $this->publishes([
            __DIR__.'/../Models' => app_path('Models'),
        ],'authentication-log-model');
        // if (File::exists(__DIR__ . '/../app/generalActivityLog.php')) {
        // }
        require __DIR__ . '/../app/generalActivityLog.php';
    }
}
