<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';

    protected $fillable = [
        'usuario_id',
        'vehiculo_id',
        'estacionamiento_id',
        'codigo_ticket',
        'fecha_entrada',
        'fecha_salida',
        'precio_total',
        'estado',
        'tipo_reserva'
    ];

    protected $casts = [
        'fecha_entrada' => 'datetime',
        'fecha_salida' => 'datetime',
        'precio_total' => 'float',
    ];

    protected $attributes = [
        'estado' => 'activo',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(UsuarioReserva::class, 'usuario_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function estacionamiento()
    {
        return $this->belongsTo(EstacionamientoAdmin::class, 'estacionamiento_id');
    }

    // Relación con pagos
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'ticket_id');
    }
}
