<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Interfaces
use App\Repositories\Interfaces\UsuarioReservaRepositoryInterface;
use App\Repositories\Interfaces\EstacionamientoAdminRepositoryInterface;
use App\Repositories\Interfaces\VehiculoRepositoryInterface;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Repositories\Interfaces\PagoRepositoryInterface;
use App\Repositories\Interfaces\PenalizacionRepositoryInterface;

// Implementaciones
use App\Repositories\Eloquent\UsuarioReservaRepository;
use App\Repositories\Eloquent\EstacionamientoAdminRepository;
use App\Repositories\Eloquent\VehiculoRepository;
use App\Repositories\Eloquent\TicketRepository;
use App\Repositories\Eloquent\PagoRepository;
use App\Repositories\Eloquent\PenalizacionRepository;

// Services
use App\Services\ReservaService;
use App\Services\TarifaCalculatorService;
use App\Services\EstacionamientoService;
use App\Services\PenalizacionService;
use App\Services\PagoService;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(UsuarioReservaRepositoryInterface::class, UsuarioReservaRepository::class);
        $this->app->bind(EstacionamientoAdminRepositoryInterface::class, EstacionamientoAdminRepository::class);
        $this->app->bind(VehiculoRepositoryInterface::class, VehiculoRepository::class);
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(PagoRepositoryInterface::class, PagoRepository::class);
        $this->app->bind(PenalizacionRepositoryInterface::class, PenalizacionRepository::class);

        // Service bindings
        $this->app->singleton(TarifaCalculatorService::class);
        $this->app->singleton(EstacionamientoService::class);
        $this->app->singleton(PenalizacionService::class);
        $this->app->singleton(ReservaService::class);
        $this->app->singleton(PagoService::class);
    }

    public function boot(): void
    {
        //
    }
}