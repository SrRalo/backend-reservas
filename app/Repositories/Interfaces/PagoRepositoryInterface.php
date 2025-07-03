<?php


namespace App\Repositories\Interfaces;

use App\Models\Pago;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

interface PagoRepositoryInterface extends BaseRepositoryInterface
{
    public function findByTicket(int $ticketId): Collection;
    public function findByEstado(string $estado): Collection;
    public function findByUsuario(int $usuarioId): Collection;
    public function findByFechaRango(Carbon $fechaInicio, Carbon $fechaFin): Collection;
    public function marcarComoPagado(int $pagoId): bool;
    public function getPagosPendientes(): Collection;
    public function getTotalPagadoPorUsuario(int $usuarioId): float;
}