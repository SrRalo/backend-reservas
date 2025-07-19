<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstacionamientoAdmin extends Model
{
    use HasFactory;

    protected $table = 'estacionamientoadmin';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'email',
        'direccion',
        'espacios_totales',
        'espacios_disponibles',
        'precio_por_hora',
        'precio_mensual',
        'estado'
    ];

    protected $casts = [
        'precio_por_hora' => 'decimal:2',
        'precio_mensual' => 'decimal:2',
        'espacios_totales' => 'integer',
        'espacios_disponibles' => 'integer'
    ];

    // Valores por defecto para testing
    protected $attributes = [
        'estado' => 'activo'
    ];

    /**
     * Relación con el usuario (registrador)
     */
    public function usuario()
    {
        return $this->belongsTo(UsuarioReserva::class, 'usuario_id');
    }

    /**
     * Relación con tickets
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'estacionamiento_id');
    }
}
