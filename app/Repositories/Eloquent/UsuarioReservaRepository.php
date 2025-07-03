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
}