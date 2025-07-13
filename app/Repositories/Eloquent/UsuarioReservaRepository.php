<?php

namespace App\Repositories\Eloquent;

use App\Models\UsuarioReserva;
use App\Repositories\Interfaces\UsuarioReservaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UsuarioReservaRepository extends BaseRepository implements UsuarioReservaRepositoryInterface
{
    public function __construct(UsuarioReserva $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?UsuarioReserva
    {
        return $this->model->where('email', $email)->first();
    }

    public function findByDocumento(string $documento): ?UsuarioReserva
    {
        return $this->model->where('documento', $documento)->first();
    }

    public function findActiveUsers(): Collection
    {
        return $this->model->where('estado', 'activo')->get();
    }

    public function searchByName(string $name): Collection
    {
        return $this->model->where('nombre', 'like', "%{$name}%")
                          ->orWhere('apellido', 'like', "%{$name}%")
                          ->get();
    }

    public function updateLastLogin(int $id): bool
    {
        $record = $this->find($id);
        if ($record) {
            $record->update(['ultimo_acceso' => now()]);
            return true;
        }
        return false;
    }

    // ✅ MÉTODOS FALTANTES - Estos son los que causan el error
    public function findByEstado(string $estado): Collection
    {
        return $this->model->where('estado', $estado)->get();
    }

    public function getUsersWithReservations(): Collection
    {
        // Assuming you have a relationship with reservations/tickets
        return $this->model->whereHas('tickets')->get();
        
        // Alternative if no relationship exists:
        // return $this->model->whereIn('id', function($query) {
        //     $query->select('usuario_id')
        //           ->from('tickets')
        //           ->distinct();
        // })->get();
    }

    public function getByRole(string $role): Collection
    {
        return $this->model->role($role)->get();
    }

    public function getRoleStatistics(): array
    {
        $total = $this->model->count();
        $admin = $this->model->admins()->count();
        $registrador = $this->model->registradores()->count();
        $reservador = $this->model->reservadores()->count();
        $activos = $this->model->where('estado', 'activo')->count();
        $inactivos = $this->model->where('estado', 'inactivo')->count();

        return [
            'total' => $total,
            'admin' => $admin,
            'registrador' => $registrador,
            'reservador' => $reservador,
            'activos' => $activos,
            'inactivos' => $inactivos,
        ];
    }
}