<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository Bindings
        $this->app->bind(
            \App\Repositories\Interfaces\UsuarioReservaRepositoryInterface::class,
            \App\Repositories\Eloquent\UsuarioReservaRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\VehiculoRepositoryInterface::class,
            \App\Repositories\Eloquent\VehiculoRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\EstacionamientoAdminRepositoryInterface::class,
            \App\Repositories\Eloquent\EstacionamientoAdminRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\TicketRepositoryInterface::class,
            \App\Repositories\Eloquent\TicketRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\PagoRepositoryInterface::class,
            \App\Repositories\Eloquent\PagoRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\PenalizacionRepositoryInterface::class,
            \App\Repositories\Eloquent\PenalizacionRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
