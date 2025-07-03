<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class UsuarioReserva extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    // ✅ Especificar tabla (singular)
    protected $table = 'usuario_reserva';

    // ✅ Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'documento',
        'telefono',
        'password',
        'estado',
        'ultimo_acceso'
    ];

    // ✅ Campos ocultos para la serialización
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ✅ Casts para los campos
    protected $casts = [
        'ultimo_acceso' => 'datetime',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ✅ Campos que son fechas
    protected $dates = [
        'ultimo_acceso',
        'created_at',
        'updated_at',
    ];

    // ✅ Valores por defecto
    protected $attributes = [
        'estado' => 'activo',
    ];

    // ✅ Relación con tickets (para el método getUsersWithReservations)
    public function tickets()
    {
        return $this->hasMany(\App\Models\Ticket::class, 'usuario_id');
    }
}
