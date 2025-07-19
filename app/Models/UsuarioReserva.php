<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class UsuarioReserva extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    // ✅ Especificar tabla (ahora usuarios)
    protected $table = 'usuarios';

    // ✅ Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'documento',
        'telefono',
        'password',
        'role',
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
        'role' => 'reservador',
    ];

    // ✅ Métodos para trabajar con roles
    
    /**
     * Verificar si el usuario es administrador
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Verificar si el usuario es registrador
     */
    public function isRegistrador(): bool
    {
        return $this->role === 'registrador';
    }

    /**
     * Verificar si el usuario es reservador
     */
    public function isReservador(): bool
    {
        return $this->role === 'reservador';
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Verificar si el usuario tiene cualquiera de los roles dados
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Scope para filtrar usuarios por rol
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope para obtener solo administradores
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope para obtener solo registradores
     */
    public function scopeRegistradores($query)
    {
        return $query->where('role', 'registrador');
    }

    /**
     * Scope para obtener solo reservadores
     */
    public function scopeReservadores($query)
    {
        return $query->where('role', 'reservador');
    }

    // ✅ Relación con tickets (para el método getUsersWithReservations)
    public function tickets()
    {
        return $this->hasMany(\App\Models\Ticket::class, 'usuario_id');
    }

    // ✅ Relación con estacionamientos (para registradores)
    public function estacionamientos()
    {
        return $this->hasMany(\App\Models\EstacionamientoAdmin::class, 'usuario_id');
    }
}
